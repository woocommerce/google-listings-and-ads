<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
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
	 * @var BatchProductHelper
	 */
	protected $batch_helper;

	/**
	 * @var ValidatorInterface
	 */
	protected $validator;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * ProductSyncer constructor.
	 *
	 * @param GoogleProductService $google_service
	 * @param BatchProductHelper   $batch_helper
	 * @param ProductHelper        $product_helper
	 * @param ValidatorInterface   $validator
	 */
	public function __construct(
		GoogleProductService $google_service,
		BatchProductHelper $batch_helper,
		ProductHelper $product_helper,
		ValidatorInterface $validator
	) {
		$this->google_service = $google_service;
		$this->batch_helper   = $batch_helper;
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
		$products         = BatchProductHelper::expand_variations( $products );
		$updated_products = [];
		$invalid_products = [];
		$product_entries  = [];
		foreach ( $products as $product ) {
			if ( ChannelVisibility::DONT_SYNC_AND_SHOW === $this->product_helper->get_visibility( $product ) ) {
				continue;
			}

			$adapted_product   = ProductHelper::generate_adapted_product( $product );
			$validation_result = $this->validate_product( $adapted_product );
			if ( $validation_result instanceof BatchInvalidProductEntry ) {
				$invalid_products[] = $validation_result;
			} else {
				$product_entries[ $product->get_id() ] = new BatchProductRequestEntry( $product->get_id(), $adapted_product );
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
				array_walk( $updated_products, [ $this->batch_helper, 'mark_as_synced' ] );
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
		$products = BatchProductHelper::expand_variations( $products );

		// filter the synced products
		$synced_products = $this->batch_helper->filter_synced_products( $products );

		// return empty response if no synced product found
		if ( empty( $synced_products ) ) {
			return new BatchProductResponse( [], [] );
		}

		$product_entries = $this->batch_helper->generate_delete_request_entries( $synced_products );

		return $this->delete_by_batch_requests( $product_entries );
	}

	/**
	 * Deletes an array of WooCommerce products from Google Merchant Center.
	 *
	 * Note: This method does not automatically delete variations of a parent product. They each must be provided via the $product_entries argument.
	 *
	 * @param BatchProductRequestEntry[] $product_entries
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products (including their variation).
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete_by_batch_requests( array $product_entries ): BatchProductResponse {
		$deleted_products = [];
		$invalid_products = [];
		foreach ( array_chunk( $product_entries, GoogleProductService::BATCH_SIZE ) as $product_entries ) {
			try {
				$response = $this->google_service->delete_batch( $product_entries );

				$deleted_products = array_merge( $deleted_products, $response->get_products() );
				$invalid_products = array_merge( $invalid_products, $response->get_errors() );

				array_walk( $deleted_products, [ $this->batch_helper, 'mark_as_unsynced' ] );
			} catch ( GoogleException $exception ) {
				throw new ProductSyncerException( sprintf( 'Error deleting Google products: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		return new BatchProductResponse( $deleted_products, $invalid_products );
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
