<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
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
	public const DEFAULT_CONCURRENCY      = 10;        // Concurrent Merchant API batch requests during a product sync.

	/**
	 * @var MapiProductInputsService
	 */
	protected $mapi_inputs;

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
	 * @param MapiProductInputsService $mapi_inputs
	 * @param BatchProductHelper       $batch_helper
	 * @param ProductHelper            $product_helper
	 * @param MerchantCenterService    $merchant_center
	 * @param WC                       $wc
	 * @param ProductRepository        $product_repository
	 */
	public function __construct(
		MapiProductInputsService $mapi_inputs,
		BatchProductHelper $batch_helper,
		ProductHelper $product_helper,
		MerchantCenterService $merchant_center,
		WC $wc,
		ProductRepository $product_repository
	) {
		$this->mapi_inputs        = $mapi_inputs;
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

		$entries = $this->batch_helper->generate_mapi_update_entries( $products );

		return $this->update_mapi_entries( $entries );
	}

	/**
	 * Submit MAPI ProductInput entries to Merchant Center via productInputs.insert.
	 *
	 * @param array<int, array{product: WC_Product, country: string, input: ProductInput, hash: string}> $entries
	 *
	 * @return BatchProductResponse Containing both the synced and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while syncing products with Google Merchant Center.
	 */
	protected function update_mapi_entries( array $entries ): BatchProductResponse {
		if ( empty( $entries ) ) {
			return new BatchProductResponse( [], [] );
		}

		$updated_products = [];
		$invalid_products = [];

		foreach ( array_chunk( $entries, GoogleProductService::BATCH_SIZE ) as $batch ) {
			$inputs = array_map(
				static function ( array $entry ): ProductInput {
					return $entry['input'];
				},
				$batch
			);

			try {
				$result = $this->mapi_inputs->insert_many( $inputs, $this->get_sync_concurrency() );
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				throw new ProductSyncerException( sprintf( 'Error updating Google products: %s', $exception->getMessage() ), 0, $exception );
			}

			foreach ( $batch as $index => $entry ) {
				if ( isset( $result['successes'][ $index ] ) ) {
					$synced_entry = new BatchProductEntry(
						$entry['product']->get_id(),
						$this->build_synced_google_product( $result['successes'][ $index ], $entry['country'] )
					);

					$updated_products[] = $synced_entry;
					$this->batch_helper->mark_as_synced( $synced_entry );

					if ( isset( $entry['hash'], $entry['input'] ) ) {
						$this->product_helper->update_sync_hash(
							$entry['product'],
							$entry['hash'],
							$entry['input']->get_content_language(),
							$entry['input']->get_feed_label()
						);
					}
				} elseif ( isset( $result['failures'][ $index ] ) ) {
					$invalid_entry = $this->build_invalid_entry( $entry['product']->get_id(), $result['failures'][ $index ] );

					$invalid_products[] = $invalid_entry;
					$this->batch_helper->mark_as_invalid( $invalid_entry );
				}
			}
		}

		$this->handle_update_errors( $invalid_products );

		do_action(
			'woocommerce_gla_batch_updated_products',
			$updated_products,
			$invalid_products
		);

		return new BatchProductResponse( $updated_products, $invalid_products );
	}

	/**
	 * Build a Google product carrying the synced id and target country,
	 * used to update the product's sync metadata.
	 *
	 * @param ProductInput $input
	 * @param string       $country
	 *
	 * @return GoogleProduct
	 */
	protected function build_synced_google_product( ProductInput $input, string $country ): GoogleProduct {
		$google_product = new GoogleProduct();
		$google_product->setId(
			sprintf( '%s~%s~%s', $input->get_content_language(), $input->get_feed_label(), $input->get_offer_id() )
		);
		$google_product->setTargetCountry( $country );
		// ProductHelper::mark_as_synced() keys the google_ids meta by feed label,
		// which the stale-entry keep-lists are built from; the country alone is
		// wrong for secondary markets whose feed label differs from their country.
		$google_product->setFeedLabel( $input->get_feed_label() );

		return $google_product;
	}

	/**
	 * Build an invalid product entry from a failed MAPI insert. Server errors are
	 * tagged with the internal-error reason so they are retried.
	 *
	 * @param int       $wc_product_id
	 * @param Exception $reason
	 *
	 * @return BatchInvalidProductEntry
	 */
	protected function build_invalid_entry( int $wc_product_id, Exception $reason ): BatchInvalidProductEntry {
		if ( $this->is_retryable_reason( $reason ) ) {
			$errors = [ GoogleProductService::INTERNAL_ERROR_REASON => $reason->getMessage() ];
		} else {
			$errors = [ 'invalid' => $reason->getMessage() ];
		}

		return new BatchInvalidProductEntry( $wc_product_id, null, $errors );
	}

	/**
	 * Whether a Merchant API status should be retried (429 rate limit or 5xx) rather
	 * than recorded as a permanent invalid product.
	 *
	 * @param int $status
	 *
	 * @return bool
	 */
	protected function is_retryable_status( int $status ): bool {
		return 429 === $status || $status >= 500;
	}

	/**
	 * Whether a sync failure should be retried: a transient Merchant API status (429/5xx)
	 * or a connection-level error that outlived the client's retry middleware.
	 *
	 * @param Exception $reason
	 *
	 * @return bool
	 */
	protected function is_retryable_reason( Exception $reason ): bool {
		if ( $reason instanceof ConnectException ) {
			return true;
		}

		if ( $reason instanceof MerchantApiException ) {
			return $this->is_retryable_status( $reason->get_http_status() );
		}

		// A transport error with no HTTP response (connection reset, etc.) is transient.
		return $reason instanceof RequestException && ! $reason->hasResponse();
	}

	/**
	 * Number of concurrent Merchant API product requests to run per batch,
	 * filterable via woocommerce_gla_mapi_product_concurrency.
	 *
	 * @return int
	 */
	protected function get_sync_concurrency(): int {
		return max( 1, (int) apply_filters( 'woocommerce_gla_mapi_product_concurrency', self::DEFAULT_CONCURRENCY ) );
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
		$synced_products = $this->batch_helper->filter_synced_products( $products );
		$entries         = $this->batch_helper->generate_mapi_delete_entries( $synced_products );

		return $this->delete_mapi_entries( $entries );
	}

	/**
	 * Delete the products described by the given id map (`[ google_id => wc_product_id ]`),
	 * the shape produced by ProductIDMap.
	 *
	 * @param array<string, int> $product_id_map
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete_by_id_map( array $product_id_map ): BatchProductResponse {
		$entries = [];
		foreach ( $product_id_map as $google_id => $wc_product_id ) {
			$identity = $this->batch_helper->parse_deletable_identity( (string) $google_id );
			if ( null === $identity ) {
				continue;
			}

			[ $language, $feed, $offer_id ] = $identity;

			$entries[] = [
				'wc_product_id' => (int) $wc_product_id,
				'google_id'     => (string) $google_id,
				'input'         => new ProductInput( $offer_id, $language, $feed ),
			];
		}

		return $this->delete_mapi_entries( $entries );
	}

	/**
	 * Delete the products described by the given request entries from Google Merchant Center,
	 * removing each deleted entry's own Google ID from its product's tracked IDs.
	 *
	 * Only the deleted entry's ID is removed, so a product synced for several markets or
	 * languages keeps the tracking for entries that were not part of the delete request.
	 * Not-found and retry handling matches the other delete flows. Entries whose google id
	 * is not in the MAPI format are skipped.
	 *
	 * @param BatchProductIDRequestEntry[] $request_entries
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete_by_batch_requests( array $request_entries ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		$entries = [];
		foreach ( $request_entries as $request_entry ) {
			$google_id = (string) $request_entry->get_product_id();
			$identity  = $this->batch_helper->parse_mapi_identity( $google_id );
			if ( null === $identity ) {
				continue;
			}

			[ $language, $feed, $offer_id ] = $identity;

			$entries[] = [
				'wc_product_id' => (int) $request_entry->get_wc_product_id(),
				'google_id'     => $google_id,
				'input'         => new ProductInput( $offer_id, $language, $feed ),
			];
		}

		if ( empty( $entries ) ) {
			return new BatchProductResponse( [], [] );
		}

		$deleted_products = [];
		$invalid_products = [];

		foreach ( array_chunk( $entries, GoogleProductService::BATCH_SIZE ) as $batch ) {
			$inputs = array_map(
				static function ( array $entry ): ProductInput {
					return $entry['input'];
				},
				$batch
			);

			try {
				$result = $this->mapi_inputs->delete_many( $inputs );
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				throw new ProductSyncerException( sprintf( 'Error deleting Google products: %s', $exception->getMessage() ), 0, $exception );
			}

			foreach ( $batch as $index => $entry ) {
				if ( isset( $result['successes'][ $index ] ) ) {
					$google_product = new GoogleProduct();
					$google_product->setId( $entry['google_id'] );

					$deleted_entry      = new BatchProductEntry( $entry['wc_product_id'], $google_product );
					$deleted_products[] = $deleted_entry;

					// Remove only the deleted entry's ID so a product synced for
					// several markets or languages keeps the tracking for entries
					// that were not part of this delete request.
					$this->batch_helper->remove_google_id_for_entry( $deleted_entry );
				} elseif ( isset( $result['failures'][ $index ] ) ) {
					$invalid_products[] = $this->build_delete_invalid_entry(
						$entry['wc_product_id'],
						$entry['google_id'],
						$result['failures'][ $index ]
					);
				}
			}
		}

		$this->handle_delete_errors( $invalid_products );

		do_action(
			'woocommerce_gla_batch_deleted_products',
			$deleted_products,
			$invalid_products
		);

		return new BatchProductResponse( $deleted_products, $invalid_products );
	}

	/**
	 * Delete the given MAPI entries via productInputs.delete.
	 *
	 * @param array<int, array{wc_product_id: int, google_id: string, input: ProductInput}> $entries
	 *
	 * @return BatchProductResponse Containing both the deleted and invalid products.
	 *
	 * @throws ProductSyncerException If there are any errors while deleting products from Google Merchant Center.
	 */
	public function delete_mapi_entries( array $entries ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		if ( empty( $entries ) ) {
			return new BatchProductResponse( [], [] );
		}

		$deleted_products = [];
		$invalid_products = [];

		foreach ( array_chunk( $entries, GoogleProductService::BATCH_SIZE ) as $batch ) {
			$inputs = array_map(
				static function ( array $entry ): ProductInput {
					return $entry['input'];
				},
				$batch
			);

			try {
				$result = $this->mapi_inputs->delete_many( $inputs, $this->get_sync_concurrency() );
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				throw new ProductSyncerException( sprintf( 'Error deleting Google products: %s', $exception->getMessage() ), 0, $exception );
			}

			foreach ( $batch as $index => $entry ) {
				if ( isset( $result['successes'][ $index ] ) ) {
					$deleted_entry      = new BatchProductEntry( $entry['wc_product_id'], null );
					$deleted_products[] = $deleted_entry;
					$this->batch_helper->mark_as_unsynced( $deleted_entry );
				} elseif ( isset( $result['failures'][ $index ] ) ) {
					$invalid_products[] = $this->build_delete_invalid_entry(
						$entry['wc_product_id'],
						$entry['google_id'],
						$result['failures'][ $index ]
					);
				}
			}
		}

		$this->handle_delete_errors( $invalid_products );

		do_action(
			'woocommerce_gla_batch_deleted_products',
			$deleted_products,
			$invalid_products
		);

		return new BatchProductResponse( $deleted_products, $invalid_products );
	}

	/**
	 * Build an invalid entry from a failed MAPI delete. 404 is tagged with the
	 * not-found reason so the existing handler removes the stale google_id;
	 * server errors are tagged with the internal-error reason so they are retried.
	 *
	 * @param int       $wc_product_id
	 * @param string    $google_id
	 * @param Exception $reason
	 *
	 * @return BatchInvalidProductEntry
	 */
	protected function build_delete_invalid_entry( int $wc_product_id, string $google_id, Exception $reason ): BatchInvalidProductEntry {
		$status = $reason instanceof MerchantApiException ? $reason->get_http_status() : 0;

		if ( 404 === $status ) {
			$errors = [ GoogleProductService::NOT_FOUND_ERROR_REASON => $reason->getMessage() ];
		} elseif ( $this->is_retryable_reason( $reason ) ) {
			$errors = [ GoogleProductService::INTERNAL_ERROR_REASON => $reason->getMessage() ];
		} else {
			$errors = [ 'invalid' => $reason->getMessage() ];
		}

		return new BatchInvalidProductEntry( $wc_product_id, $google_id, $errors );
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
				'Cannot push any products because your store is not ready for syncing.',
				__METHOD__
			);

			throw new ProductSyncerException(
				__(
					'Pushing products will not run if the store is not ready for syncing.',
					'google-listings-and-ads'
				)
			);
		}
	}
}
