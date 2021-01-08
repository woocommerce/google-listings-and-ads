<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchDeleteProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchUpdateProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google_Exception;
use Google_Service_ShoppingContent_Product as GoogleProduct;
use WC_Product;

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
	 * ProductSyncer constructor.
	 *
	 * @param GoogleProductService $google_service
	 * @param ProductMetaHandler   $meta_handler
	 * @param ProductHelper        $product_helper
	 */
	public function __construct( GoogleProductService $google_service, ProductMetaHandler $meta_handler, ProductHelper $product_helper ) {
		$this->google_service = $google_service;
		$this->meta_handler   = $meta_handler;
		$this->product_helper = $product_helper;
	}

	/**
	 * Uploads a WooCommerce product to Google.
	 *
	 * @param WC_Product $product
	 *
	 * @throws ProductSyncerException If there are any errors while syncing with Google.
	 */
	public function update( WC_Product $product ) {
		$google_product = ProductHelper::generate_adapted_product( $product );

		try {
			$updated_product = $this->google_service->insert( $google_product );

			$this->update_metas( $product->get_id(), $updated_product );
		} catch ( Google_Exception $exception ) {
			throw new ProductSyncerException( sprintf( 'Error updating Google product: %s', $exception->getMessage() ), 0, $exception );
		}
	}

	/**
	 * Uploads an array of WooCommerce products to Google.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchUpdateProductResponse Containing both the synced and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while syncing with Google.
	 */
	public function update_many( array $products ) {
		$updated_products = [];
		$invalid_products = [];

		$products = ProductHelper::expand_variations( $products );
		foreach ( array_chunk( $products, GoogleProductService::BATCH_SIZE ) as $products ) {
			$google_products = array_map( [ ProductHelper::class, 'generate_adapted_product' ], $products );

			try {
				$response = $this->google_service->insert_batch( $google_products );

				$updated_products = array_merge( $updated_products, $response->get_updated_products() );
				$invalid_products = array_merge( $invalid_products, $response->get_invalid_products() );

				// update the meta data for the synced products
				array_walk(
					$updated_products,
					function ( $updated_product ) {
						$this->update_metas( $updated_product->getOfferId(), $updated_product );
					}
				);
			} catch ( Google_Exception $exception ) {
				throw new ProductSyncerException( sprintf( 'Error updating Google product: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		return new BatchUpdateProductResponse( $updated_products, $invalid_products );
	}

	/**
	 * Uploads a WooCommerce product to Google.
	 *
	 * @param WC_Product $product
	 *
	 * @throws ProductSyncerException If there are any errors while syncing with Google.
	 */
	public function delete( WC_Product $product ) {
		$google_product = ProductHelper::generate_adapted_product( $product );

		try {
			$this->google_service->insert( $google_product );

			$this->delete_metas( $product->get_id() );
		} catch ( Google_Exception $exception ) {
			throw new ProductSyncerException( sprintf( 'Error deleting Google product: %s', $exception->getMessage() ), 0, $exception );
		}
	}

	/**
	 * Uploads an array of WooCommerce products to Google.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchDeleteProductResponse Containing both the deleted and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while syncing with Google.
	 */
	public function delete_many( array $products ) {
		$deleted_product_ids = [];
		$invalid_products    = [];

		$products = ProductHelper::expand_variations( $products );
		foreach ( array_chunk( $products, GoogleProductService::BATCH_SIZE ) as $products ) {
			// filter the synced products
			$synced_products = array_filter( $products, [ $this->product_helper, 'is_product_synced' ] );

			// fetch the Google/WooCommerce product ID map
			$products_id_map     = $this->generate_id_map( $synced_products );
			$google_products_ids = array_keys( $products_id_map );

			try {
				$response = $this->google_service->delete_batch( $google_products_ids );

				$deleted_product_ids = array_merge( $deleted_product_ids, $response->get_deleted_product_ids() );
				$invalid_products    = array_merge( $invalid_products, $response->get_invalid_products() );

				$deleted_wc_products = array_intersect_key( $products_id_map, array_flip( $deleted_product_ids ) );
				array_walk( $deleted_wc_products, [ $this, 'delete_metas' ] );
			} catch ( Google_Exception $exception ) {
				throw new ProductSyncerException( sprintf( 'Error deleting Google product: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		return new BatchDeleteProductResponse( $deleted_product_ids, $invalid_products );
	}

	/**
	 * @param int           $wc_product_id WooCommerce product ID
	 * @param GoogleProduct $google_product
	 */
	protected function update_metas( int $wc_product_id, GoogleProduct $google_product ) {
		$this->meta_handler->update( $wc_product_id, ProductMetaHandler::KEY_SYNCED_AT, time() );
		$this->meta_handler->update( $wc_product_id, ProductMetaHandler::KEY_GOOGLE_ID, $google_product->getId() );
	}

	/**
	 * @param int $wc_product_id WooCommerce product ID
	 */
	protected function delete_metas( int $wc_product_id ) {
		$this->meta_handler->delete( $wc_product_id, ProductMetaHandler::KEY_SYNCED_AT );
		$this->meta_handler->delete( $wc_product_id, ProductMetaHandler::KEY_GOOGLE_ID );
	}

	/**
	 * Generates an array map containing the Google product IDs as key and the WooCommerce product IDs as values.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array
	 */
	protected function generate_id_map( array $products ): array {
		$product_id_map = [];

		foreach ( $products as $product ) {
			$google_product_id = $this->product_helper->get_synced_google_product_id( $product );
			$wc_product_id     = $product->get_id();

			$product_id_map[ $google_product_id ] = $wc_product_id;
		}

		return $product_id_map;
	}
}
