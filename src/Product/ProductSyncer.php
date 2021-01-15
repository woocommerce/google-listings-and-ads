<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google\Exception as GoogleException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSyncer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductSyncer implements Service {

	/**
	 * @var GoogleProductService
	 */
	protected $google_service;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var ValidatorInterface
	 */
	protected $validator;

	/**
	 * ProductSyncer constructor.
	 *
	 * @param GoogleProductService $google_service
	 * @param ProductMetaHandler   $meta_handler
	 * @param ProductHelper        $product_helper
	 * @param ValidatorInterface   $validator
	 */
	public function __construct(
		GoogleProductService $google_service,
		ProductMetaHandler $meta_handler,
		ProductHelper $product_helper,
		ValidatorInterface $validator
	) {
		$this->google_service = $google_service;
		$this->meta_handler   = $meta_handler;
		$this->product_helper = $product_helper;
		$this->validator      = $validator;
	}

	/**
	 * Submits an array of WooCommerce products to Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductResponse Containing both the synced and invalid products (including their variation).
	 *
	 * @throws ProductSyncerException If there are any errors while syncing products with Google Merchant Center.
	 */
	public function update( array $products ): BatchProductResponse {
		// prepare and validate products
		$products         = ProductHelper::expand_variations( $products );
		$updated_products = [];
		$invalid_products = [];
		$product_entries  = [];
		foreach ( $products as $product ) {
			$adapted_product   = ProductHelper::generate_adapted_product( $product );
			$validation_result = $this->validate_product( $adapted_product );
			if ( $validation_result instanceof BatchInvalidProductEntry ) {
				$invalid_products[] = $validation_result;
			} else {
				$product_entries[] = new BatchProductRequestEntry( $product->get_id(), $adapted_product );
			}
		}

		// bail if no valid products provided
		if ( empty( $product_entries ) ) {
			return new BatchProductResponse( [], $invalid_products );
		}

		foreach ( array_chunk( $product_entries, GoogleProductService::BATCH_SIZE ) as $product_entries ) {
			try {
				$response = $this->google_service->insert_batch( $product_entries );

				$updated_products = array_merge( $updated_products, $response->get_products() );
				$invalid_products = array_merge( $invalid_products, $response->get_errors() );

				// update the meta data for the synced products
				array_walk( $updated_products, [ $this, 'mark_as_synced' ] );
			} catch ( GoogleException $exception ) {
				throw new ProductSyncerException( sprintf( 'Error updating Google product: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		return new BatchProductResponse( $updated_products, $invalid_products );
	}

	/**
	 * Deletes an array of WooCommerce products from Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products (including their variation).
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete( array $products ): BatchProductResponse {
		$deleted_products = [];
		$invalid_products = [];

		$products = ProductHelper::expand_variations( $products );

		// filter the synced products
		$synced_products = array_filter( $products, [ $this->product_helper, 'is_product_synced' ] );

		// return empty response if no synced product found
		if ( empty( $synced_products ) ) {
			return new BatchProductResponse( [], [] );
		}

		foreach ( array_chunk( $synced_products, GoogleProductService::BATCH_SIZE ) as $products_batch ) {
			$product_entries = $this->generate_delete_request_entries( $products_batch );
			try {
				$response = $this->google_service->delete_batch( $product_entries );

				$deleted_products = array_merge( $deleted_products, $response->get_products() );
				$invalid_products = array_merge( $invalid_products, $response->get_errors() );

				array_walk( $deleted_products, [ $this, 'mark_as_unsynced' ] );
			} catch ( GoogleException $exception ) {
				throw new ProductSyncerException( sprintf( 'Error deleting Google products: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		return new BatchProductResponse( $deleted_products, $invalid_products );
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	protected function mark_as_synced( BatchProductEntry $product_entry ) {
		$wc_product_id  = $product_entry->get_wc_product_id();
		$google_product = $product_entry->get_google_product();

		$this->meta_handler->update_synced_at( $wc_product_id, time() );

		// merge and update all google product ids
		$current_google_ids = $this->meta_handler->get_google_ids( $wc_product_id );
		$current_google_ids = ! empty( $current_google_ids ) ? $current_google_ids : [];
		$google_ids         = array_unique( array_merge( $current_google_ids, [ $google_product->getId() ] ) );
		$this->meta_handler->update_google_ids( $wc_product_id, $google_ids );
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	protected function mark_as_unsynced( BatchProductEntry $product_entry ) {
		$wc_product_id = $product_entry->get_wc_product_id();
		$this->meta_handler->delete_synced_at( $wc_product_id );
		$this->meta_handler->delete_google_ids( $wc_product_id );
	}

	/**
	 * Generates an array map containing the Google product IDs as key and the WooCommerce product IDs as values.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array
	 */
	protected function generate_delete_request_entries( array $products ): array {
		$request_entries = [];
		foreach ( $products as $product ) {
			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			$product_entries = array_map(
				function ( string $google_id ) use ( $product ) {
					return new BatchProductRequestEntry(
						$product->get_id(),
						$google_id
					);
				},
				$google_ids
			);
			$request_entries = array_merge( $request_entries, $product_entries );
		}

		return $request_entries;
	}

	/**
	 * @param WCProductAdapter $product
	 *
	 * @return BatchInvalidProductEntry|true
	 */
	protected function validate_product( WCProductAdapter $product ) {
		$violations = $this->validator->validate( $product );

		if ( 0 !== count( $violations ) ) {
			$invalid_product = new BatchInvalidProductEntry( $product->get_wc_product()->get_id() );
			$invalid_product->map_validation_violations( $violations );

			return $invalid_product;
		}

		return true;
	}
}
