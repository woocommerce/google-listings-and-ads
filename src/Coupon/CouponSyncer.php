<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiPromotionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\InvalidCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Exception;
use WC_Coupon;
defined( 'ABSPATH' ) || exit();

/**
 * Class CouponSyncer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class CouponSyncer implements Service {

	public const FAILURE_THRESHOLD = 5;

	// Number of failed attempts allowed per FAILURE_THRESHOLD_WINDOW
	public const FAILURE_THRESHOLD_WINDOW = '3 hours';

	/** Error code used to flag promotions that failed with an internal (5xx) error. */
	public const INTERNAL_ERROR_CODE = 500;

	/**
	 * @var MapiPromotionsService
	 */
	protected $promotions_service;

	/**
	 * @var MapiDataSourcesService
	 */
	protected $data_sources;

	/**
	 *
	 * @var CouponHelper
	 */
	protected $coupon_helper;

	/**
	 *
	 * @var ValidatorInterface
	 */
	protected $validator;

	/**
	 *
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var TargetAudience
	 */
	protected $target_audience;

	/**
	 * CouponSyncer constructor.
	 *
	 * @param MapiPromotionsService  $promotions_service
	 * @param MapiDataSourcesService $data_sources
	 * @param CouponHelper           $coupon_helper
	 * @param ValidatorInterface     $validator
	 * @param MerchantCenterService  $merchant_center
	 * @param TargetAudience         $target_audience
	 * @param WC                     $wc
	 */
	public function __construct(
		MapiPromotionsService $promotions_service,
		MapiDataSourcesService $data_sources,
		CouponHelper $coupon_helper,
		ValidatorInterface $validator,
		MerchantCenterService $merchant_center,
		TargetAudience $target_audience,
		WC $wc
	) {
		$this->promotions_service = $promotions_service;
		$this->data_sources       = $data_sources;
		$this->coupon_helper      = $coupon_helper;
		$this->validator          = $validator;
		$this->merchant_center    = $merchant_center;
		$this->target_audience    = $target_audience;
		$this->wc                 = $wc;
	}

	/**
	 * Submit a WooCommerce coupon to Google Merchant Center.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @throws CouponSyncerException If there are any errors while syncing coupon with Google Merchant Center.
	 */
	public function update( WC_Coupon $coupon ) {
		$this->validate_merchant_center_setup();

		if ( ! $this->coupon_helper->is_sync_ready( $coupon ) ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Skipping coupon (ID: %s) because it is not ready to be synced.',
					$coupon->get_id()
				),
				__METHOD__
			);
			return;
		}

		$target_country = $this->target_audience->get_main_target_country();
		if ( ! $this->merchant_center->is_promotion_supported_country( $target_country ) ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Skipping coupon (ID: %s) because it is not supported in main target country %s.',
					$coupon->get_id(),
					$target_country
				),
				__METHOD__
			);
			return;
		}

		$adapted_coupon    = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => $target_country,
			]
		);
		$validation_result = $this->validate_coupon( $adapted_coupon );
		if ( $validation_result instanceof InvalidCouponEntry ) {
			$this->coupon_helper->mark_as_invalid(
				$coupon,
				$validation_result->get_errors()
			);

			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Skipping coupon (ID: %s) because it does not pass validation: %s',
					$coupon->get_id(),
					wp_json_encode( $validation_result )
				),
				__METHOD__
			);

			return;
		}

		$promotion = $adapted_coupon->get_promotion();

		try {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Start to upload coupon (ID: %s) as promotion structure: %s',
					$coupon->get_id(),
					wp_json_encode( $promotion )
				),
				__METHOD__
			);
			$response = $this->insert_promotion( $promotion );
			$this->coupon_helper->mark_as_synced(
				$coupon,
				(string) ( $response['promotionId'] ?? '' ),
				$target_country
			);
			do_action( 'woocommerce_gla_updated_coupon', $adapted_coupon );

			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					"Submitted promotion:\n%s",
					wp_json_encode( $promotion )
				),
				__METHOD__
			);
		} catch ( MerchantApiException $google_exception ) {
			$invalid_promotion = new InvalidCouponEntry(
				$coupon->get_id(),
				[
					$google_exception->getCode() => $google_exception->getMessage(),
				],
				$target_country
			);
			$this->coupon_helper->mark_as_invalid(
				$coupon,
				$invalid_promotion->get_errors()
			);

			$this->handle_update_errors( [ $invalid_promotion ] );

			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					"Promotion failed to sync with Merchant Center:\n%s",
					wp_json_encode( $invalid_promotion )
				),
				__METHOD__
			);
		} catch ( Exception $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

			throw new CouponSyncerException(
				sprintf(
					'Error updating Google promotion: %s',
					$exception->getMessage()
				),
				0,
				$exception
			);
		}
	}

	/**
	 *
	 * @param WCCouponAdapter $coupon
	 *
	 * @return InvalidCouponEntry|true
	 */
	protected function validate_coupon( WCCouponAdapter $coupon ) {
		$violations = $this->validator->validate( $coupon );

		if ( 0 !== count( $violations ) ) {
			$invalid_promotion = new InvalidCouponEntry(
				$coupon->get_wc_coupon_id()
			);
			$invalid_promotion->map_validation_violations( $violations );

			return $invalid_promotion;
		}

		return true;
	}

	/**
	 * Delete a WooCommerce coupon from Google Merchant Center.
	 *
	 * @param DeleteCouponEntry $coupon
	 *
	 * @throws CouponSyncerException If there are any errors while deleting coupon from Google Merchant Center.
	 */
	public function delete( DeleteCouponEntry $coupon ) {
		$this->validate_merchant_center_setup();

		$deleted_promotions = [];
		$invalid_promotions = [];
		$synced_google_ids  = $coupon->get_synced_google_ids();
		$wc_coupon          = $this->wc->maybe_get_coupon(
			$coupon->get_wc_coupon_id()
		);
		$wc_coupon_exist    = $wc_coupon instanceof WC_Coupon;
		foreach ( $synced_google_ids as $target_country => $google_id ) {
			try {
				$promotion                  = $coupon->get_google_promotion();
				$promotion['targetCountry'] = $target_country;

				do_action(
					'woocommerce_gla_debug_message',
					sprintf(
						'Start to delete coupon (ID: %s) as promotion structure: %s',
						$coupon->get_wc_coupon_id(),
						wp_json_encode( $promotion )
					),
					__METHOD__
				);
				// DeleteCouponEntry is generated with the promotion effective date expired
				// when the WC coupon can be deleted. To soft-delete the promotion on the
				// Google side, we upsert it with the expired effective date.
				$response = $this->insert_promotion( $promotion );
				array_push( $deleted_promotions, $response );
				if ( $wc_coupon_exist ) {
					$this->coupon_helper->remove_google_id_by_country(
						$wc_coupon,
						$target_country
					);
				}
			} catch ( MerchantApiException $google_exception ) {
				array_push(
					$invalid_promotions,
					new InvalidCouponEntry(
						$coupon->get_wc_coupon_id(),
						[
							$google_exception->getCode() => $google_exception->getMessage(),
						],
						$target_country,
						$google_id
					)
				);
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				throw new CouponSyncerException(
					sprintf(
						'Error deleting Google promotion: %s',
						$exception->getMessage()
					),
					0,
					$exception
				);
			}
		}

		if ( ! empty( $invalid_promotions ) ) {
			$this->handle_delete_errors( $invalid_promotions );
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					"Failed to delete %s promotions from Merchant Center:\n%s",
					count( $invalid_promotions ),
					wp_json_encode( $invalid_promotions )
				),
				__METHOD__
			);
		} elseif ( $wc_coupon_exist ) {
			$this->coupon_helper->mark_as_unsynced( $wc_coupon );
		}

		do_action(
			'woocommerce_gla_deleted_promotions',
			$deleted_promotions,
			$invalid_promotions
		);

		do_action(
			'woocommerce_gla_debug_message',
			sprintf(
				"Deleted %s promoitons:\n%s",
				count( $deleted_promotions ),
				wp_json_encode( $deleted_promotions )
			),
			__METHOD__
		);
	}

	/**
	 * Return whether coupon is supported as visible on Google.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return bool
	 */
	public static function is_coupon_supported( WC_Coupon $coupon ): bool {
		if ( $coupon->get_virtual() ) {
			return false;
		}
		if ( ! empty( $coupon->get_email_restrictions() ) ) {
			return false;
		}
		if ( ! empty( $coupon->get_exclude_sale_items() ) &&
			$coupon->get_exclude_sale_items() ) {
			return false;
		}
		return true;
	}

	/**
	 * Return the list of supported coupon types.
	 *
	 * @return array
	 */
	public static function get_supported_coupon_types(): array {
		return (array) apply_filters(
			'woocommerce_gla_supported_coupon_types',
			[ 'percent', 'fixed_cart', 'fixed_product' ]
		);
	}

	/**
	 * Return the list of coupon types we will hide functionality for (default none).
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public static function get_hidden_coupon_types(): array {
		return (array) apply_filters( 'woocommerce_gla_hidden_coupon_types', [] );
	}

	/**
	 *
	 * @param InvalidCouponEntry[] $invalid_coupons
	 */
	protected function handle_update_errors( array $invalid_coupons ) {
		// Get a coupon id to country mappings.
		$internal_error_coupon_ids = [];
		foreach ( $invalid_coupons as $invalid_coupon ) {
			if ( $invalid_coupon->has_error(
				self::INTERNAL_ERROR_CODE
			) ) {
				$coupon_id                               = $invalid_coupon->get_wc_coupon_id();
				$internal_error_coupon_ids[ $coupon_id ] = $invalid_coupon->get_target_country();
			}
		}

		if ( ! empty( $internal_error_coupon_ids ) &&
			apply_filters(
				'woocommerce_gla_coupons_update_retry_on_failure',
				true,
				$internal_error_coupon_ids
			) ) {
			do_action(
				'woocommerce_gla_retry_update_coupons',
				$internal_error_coupon_ids
			);

			do_action(
				'woocommerce_gla_error',
				sprintf(
					'Internal API errors while submitting the following coupons: %s',
					join( ', ', $internal_error_coupon_ids )
				),
				__METHOD__
			);
		}
	}

	/**
	 *
	 * @param BatchInvalidCouponEntry[] $invalid_coupons
	 */
	protected function handle_delete_errors( array $invalid_coupons ) {
		// Get all wc coupon id to google id mappings that have internal errors.
		$internal_error_coupon_ids = [];
		foreach ( $invalid_coupons as $invalid_coupon ) {
			if ( $invalid_coupon->has_error(
				self::INTERNAL_ERROR_CODE
			) ) {
				$coupon_id                               = $invalid_coupon->get_wc_coupon_id();
				$internal_error_coupon_ids[ $coupon_id ] = $invalid_coupon->get_google_promotion_id();
			}
		}

		if ( ! empty( $internal_error_coupon_ids ) &&
			apply_filters(
				'woocommerce_gla_coupons_delete_retry_on_failure',
				true,
				$internal_error_coupon_ids
			) ) {
			do_action(
				'woocommerce_gla_retry_delete_coupons',
				$internal_error_coupon_ids
			);

			do_action(
				'woocommerce_gla_error',
				sprintf(
					'Internal API errors while deleting the following coupons: %s',
					join( ', ', $internal_error_coupon_ids )
				),
				__METHOD__
			);
		}
	}

	/**
	 * Upsert a promotion into its (contentLanguage, targetCountry) data source, retrying once with a
	 * re-resolved data source when the cached one is rejected as missing. Mirrors the product path:
	 * a data source can be deleted on the Google side after it was resolved.
	 *
	 * @param array $promotion The Promotion resource to write.
	 *
	 * @return array The stored Promotion resource.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	private function insert_promotion( array $promotion ): array {
		$data_source = $this->data_sources->ensure_promotion_data_source_for(
			$promotion['contentLanguage'],
			$promotion['targetCountry']
		);

		try {
			return $this->promotions_service->insert_promotion( $data_source, $promotion );
		} catch ( MerchantApiException $exception ) {
			if ( ! MapiDataSourcesService::is_missing_data_source_failure( $exception ) ) {
				throw $exception;
			}

			$this->data_sources->forget_promotion_data_source_for(
				$promotion['contentLanguage'],
				$promotion['targetCountry']
			);

			return $this->promotions_service->insert_promotion(
				$this->data_sources->ensure_promotion_data_source_for(
					$promotion['contentLanguage'],
					$promotion['targetCountry']
				),
				$promotion
			);
		}
	}

	/**
	 * Validates whether Merchant Center is connected and ready for pushing data.
	 *
	 * @throws CouponSyncerException If Google Merchant Center is not set up and connected or is not ready for pushing data.
	 */
	protected function validate_merchant_center_setup(): void {
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			do_action(
				'woocommerce_gla_error',
				'Cannot sync any coupons before setting up Google Merchant Center.',
				__METHOD__
			);

			throw new CouponSyncerException(
				__(
					'Google Merchant Center has not been set up correctly. Please review your configuration.',
					'google-listings-and-ads'
				)
			);
		}

		if ( ! $this->merchant_center->should_push() ) {
			do_action(
				'woocommerce_gla_error',
				'Cannot push any coupons because your store is not ready for syncing.',
				__METHOD__
			);

			throw new CouponSyncerException(
				__(
					'Pushing coupons will not run if the store is not ready for syncing.',
					'google-listings-and-ads'
				)
			);
		}
	}
}
