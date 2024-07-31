<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Exception;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSyncer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductSyncer implements Service {

	public const FAILURE_THRESHOLD        = 5;         // Number of failed attempts allowed per FAILURE_THRESHOLD_WINDOW
	public const FAILURE_THRESHOLD_WINDOW = '3 hours'; // PHP supported Date and Time format: https://www.php.net/manual/en/datetime.formats.php

	/**
	 * @var GoogleProductService
	 */
	protected $google_service;

	/**
	 * @var BatchProductHelper
	 */
	protected $batch_helper;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * ProductSyncer constructor.
	 *
	 * @param GoogleProductService  $google_service
	 * @param BatchProductHelper    $batch_helper
	 * @param ProductHelper         $product_helper
	 * @param MerchantCenterService $merchant_center
	 * @param WC                    $wc
	 * @param ProductRepository     $product_repository
	 */
	public function __construct(
		GoogleProductService $google_service,
		BatchProductHelper $batch_helper,
		ProductHelper $product_helper,
		MerchantCenterService $merchant_center,
		WC $wc,
		ProductRepository $product_repository
	) {
		$this->google_service     = $google_service;
		$this->batch_helper       = $batch_helper;
		$this->product_helper     = $product_helper;
		$this->merchant_center    = $merchant_center;
		$this->wc                 = $wc;
		$this->product_repository = $product_repository;
	}

	/**
	 * Submits an array of WooCommerce products to Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductResponse Containing both the synced and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while syncing products with Google Merchant Center.
	 */
	public function update( array $products ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		// prepare and validate products
		$product_entries = $this->batch_helper->validate_and_generate_update_request_entries( $products );

		return $this->update_by_batch_requests( $product_entries );
	}

	/**
	 * Submits an array of WooCommerce products to Google Merchant Center.
	 *
	 * @param BatchProductRequestEntry[] $product_entries
	 *
	 * @return BatchProductResponse Containing both the synced and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while syncing products with Google Merchant Center.
	 */
	public function update_by_batch_requests( array $product_entries ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		// bail if no valid products provided
		if ( empty( $product_entries ) ) {
			return new BatchProductResponse( [], [] );
		}

		$updated_products = [];
		$invalid_products = [];
		foreach ( array_chunk( $product_entries, GoogleProductService::BATCH_SIZE ) as $batch_entries ) {
			try {
				$response = $this->google_service->insert_batch( $batch_entries );

				$updated_products = array_merge( $updated_products, $response->get_products() );
				$invalid_products = array_merge( $invalid_products, $response->get_errors() );

				// update the meta data for the synced and invalid products
				array_walk( $updated_products, [ $this->batch_helper, 'mark_as_synced' ] );
				array_walk( $invalid_products, [ $this->batch_helper, 'mark_as_invalid' ] );
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				throw new ProductSyncerException( sprintf( 'Error updating Google products: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		$this->handle_update_errors( $invalid_products );

		do_action(
			'woocommerce_gla_batch_updated_products',
			$updated_products,
			$invalid_products
		);

		do_action(
			'woocommerce_gla_debug_message',
			sprintf(
				"Submitted %s products:\n%s",
				count( $updated_products ),
				json_encode( $updated_products )
			),
			__METHOD__
		);
		if ( ! empty( $invalid_products ) ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					"%s products failed to sync with Merchant Center:\n%s",
					count( $invalid_products ),
					json_encode( $invalid_products )
				),
				__METHOD__
			);
		}

		return new BatchProductResponse( $updated_products, $invalid_products );
	}

	/**
	 * Deletes an array of WooCommerce products from Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete( array $products ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		$synced_products = $this->batch_helper->filter_synced_products( $products );
		$product_entries = $this->batch_helper->generate_delete_request_entries( $synced_products );

		return $this->delete_by_batch_requests( $product_entries );
	}

	/**
	 * Deletes an array of WooCommerce products from Google Merchant Center.
	 *
	 * Note: This method does not automatically delete variations of a parent product. They each must be provided via the $product_entries argument.
	 *
	 * @param BatchProductIDRequestEntry[] $product_entries
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products (including their variation).
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete_by_batch_requests( array $product_entries ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		// return empty response if no synced product found
		if ( empty( $product_entries ) ) {
			return new BatchProductResponse( [], [] );
		}

		$deleted_products = [];
		$invalid_products = [];
		foreach ( array_chunk( $product_entries, GoogleProductService::BATCH_SIZE ) as $batch_entries ) {
			try {
				$response = $this->google_service->delete_batch( $batch_entries );

				$deleted_products = array_merge( $deleted_products, $response->get_products() );
				$invalid_products = array_merge( $invalid_products, $response->get_errors() );

				array_walk( $deleted_products, [ $this->batch_helper, 'mark_as_unsynced' ] );
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				throw new ProductSyncerException( sprintf( 'Error deleting Google products: %s', $exception->getMessage() ), 0, $exception );
			}
		}

		$this->handle_delete_errors( $invalid_products );

		do_action(
			'woocommerce_gla_batch_deleted_products',
			$deleted_products,
			$invalid_products
		);

		do_action(
			'woocommerce_gla_debug_message',
			sprintf(
				"Deleted %s products:\n%s",
				count( $deleted_products ),
				json_encode( $deleted_products ),
			),
			__METHOD__
		);
		if ( ! empty( $invalid_products ) ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					"Failed to delete %s products from Merchant Center:\n%s",
					count( $invalid_products ),
					json_encode( $invalid_products )
				),
				__METHOD__
			);
		}

		return new BatchProductResponse( $deleted_products, $invalid_products );
	}

	/**
	 * Return the list of supported product types.
	 *
	 * @return array
	 */
	public static function get_supported_product_types(): array {
		return (array) apply_filters( 'woocommerce_gla_supported_product_types', [ 'simple', 'variable', 'variation' ] );
	}

	/**
	 * @param BatchInvalidProductEntry[] $invalid_products
	 */
	protected function handle_update_errors( array $invalid_products ) {
		$error_products = [];
		foreach ( $invalid_products as $invalid_product ) {
			if ( $invalid_product->has_error( GoogleProductService::INTERNAL_ERROR_REASON ) ) {
				$wc_product_id = $invalid_product->get_wc_product_id();
				$wc_product    = $this->wc->maybe_get_product( $wc_product_id );
				// Only schedule for retry if the failure threshold has not been reached.
				if (
					$wc_product instanceof WC_Product &&
					! $this->product_helper->is_update_failed_threshold_reached( $wc_product )
				) {
					$error_products[ $wc_product_id ] = $wc_product_id;
				}
			}
		}

		if ( ! empty( $error_products ) && apply_filters( 'woocommerce_gla_products_update_retry_on_failure', true, $invalid_products ) ) {
			do_action( 'woocommerce_gla_batch_retry_update_products', $error_products );

			do_action(
				'woocommerce_gla_error',
				sprintf( 'Internal API errors while submitting the following products: %s', join( ', ', $error_products ) ),
				__METHOD__
			);
		}
	}

	/**
	 * @param BatchInvalidProductEntry[] $invalid_products
	 */
	protected function handle_delete_errors( array $invalid_products ) {
		$internal_error_ids = [];
		foreach ( $invalid_products as $invalid_product ) {
			$google_product_id = $invalid_product->get_google_product_id();
			$wc_product_id     = $invalid_product->get_wc_product_id();
			$wc_product        = $this->wc->maybe_get_product( $wc_product_id );
			if ( ! $wc_product instanceof WC_Product || empty( $google_product_id ) ) {
				continue;
			}

			// not found
			if ( $invalid_product->has_error( GoogleProductService::NOT_FOUND_ERROR_REASON ) ) {
				do_action(
					'woocommerce_gla_error',
					sprintf(
						'Attempted to delete product "%s" (WooCommerce Product ID: %s) but it did not exist in Google Merchant Center, removing the synced product ID from database.',
						$google_product_id,
						$wc_product_id
					),
					__METHOD__
				);

				$this->product_helper->remove_google_id( $wc_product, $google_product_id );
			}

			// internal error
			if ( $invalid_product->has_error( GoogleProductService::INTERNAL_ERROR_REASON ) ) {
				$this->product_helper->increment_failed_delete_attempt( $wc_product );

				// Only schedule for retry if the failure threshold has not been reached.
				if ( ! $this->product_helper->is_delete_failed_threshold_reached( $wc_product ) ) {
					$internal_error_ids[ $google_product_id ] = $wc_product_id;
				}
			}
		}

		// Exclude any ID's which are not ready to delete or are not available in the DB.
		$product_ids        = array_values( $internal_error_ids );
		$ready_ids          = $this->product_repository->find_delete_product_ids( $product_ids );
		$internal_error_ids = array_intersect( $internal_error_ids, $ready_ids );

		// call an action to retry if any products with internal errors exist
		if ( ! empty( $internal_error_ids ) && apply_filters( 'woocommerce_gla_products_delete_retry_on_failure', true, $invalid_products ) ) {
			do_action( 'woocommerce_gla_batch_retry_delete_products', $internal_error_ids );

			do_action(
				'woocommerce_gla_error',
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				sprintf( 'Internal API errors while deleting the following products: %s', print_r( $internal_error_ids, true ) ),
				__METHOD__
			);
		}
	}

	/**
	 * Validates whether Merchant Center is connected and ready for pushing data.
	 *
	 * @throws ProductSyncerException If the Google Merchant Center connection is not ready or cannot push data.
	 */
	protected function validate_merchant_center_setup(): void {
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			do_action( 'woocommerce_gla_error', 'Cannot sync any products before setting up Google Merchant Center.', __METHOD__ );

			throw new ProductSyncerException( __( 'Google Merchant Center has not been set up correctly. Please review your configuration.', 'google-listings-and-ads' ) );
		}

		if ( ! $this->merchant_center->should_push() ) {
			do_action(
				'woocommerce_gla_error',
				'Cannot push any products because they are being fetched automatically.',
				__METHOD__
			);

			throw new ProductSyncerException(
				__(
					'Pushing products will not run if the automatic data fetching is enabled. Please review your configuration in Google Listing and Ads settings.',
					'google-listings-and-ads'
				)
			);
		}
	}
}
