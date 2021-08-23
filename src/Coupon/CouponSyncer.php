<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
//use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
//use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
//use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
//use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Exception;
use WC_Coupon;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponSyncer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class CouponSyncer implements Service {

	public const FAILURE_THRESHOLD        = 5;         // Number of failed attempts allowed per FAILURE_THRESHOLD_WINDOW
	public const FAILURE_THRESHOLD_WINDOW = '3 hours'; // PHP supported Date and Time format: https://www.php.net/manual/en/datetime.formats.php

	/**
	 * @var GoogleProductService
	 */
	protected $google_service;

	/**
	 * @var CouponHelper
	 */
	protected $coupon_helper;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * CouponSyncer constructor.
	 *
	 * @param GoogleProductService  $google_service
	 * @param CouponHelper          $coupon_helper
	 * @param MerchantCenterService $merchant_center
	 * @param WC                    $wc
	 */
	public function __construct(
		GoogleProductService $google_service,
		CouponHelper $coupon_helper,
		MerchantCenterService $merchant_center,
		WC $wc
	) {
		$this->google_service  = $google_service;
		$this->coupon_helper   = $coupon_helper;
		$this->merchant_center = $merchant_center;
		$this->wc              = $wc;
	}

	/**
	 * Submit a WooCommerce coupon to Google Merchant Center.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @throws CouponSyncerException If there are any errors while syncing coupon with Google Merchant Center.
	 */
	public function update( WC_Coupon $coupon ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		// Prepare and validate coupons
		// TODO: Process coupon update.
		return new BatchProductResponse([],[]);
	}

	/**
	 * Delete a WooCommerce coupon from Google Merchant Center.
	 *
	 * @param int $coupon_id
	 *
	 * @throws CouponSyncerException If there are any errors while deleting coupon from Google Merchant Center.
	 */
	public function delete( int $coupon_id ): BatchProductResponse {
		$this->validate_merchant_center_setup();

		// Prepare and check to only delete synched coupon.
		// TODO: Process coupon update.
		return new BatchProductResponse([],[]);
	}


	/**
	 * Return whether coupon is supported as visible on Google.
	 *
	 * @return bool
	 */
	public static function is_coupon_supported(WC_Coupon $coupon): bool {
	    if ( ! empty( $coupon->get_email_restrictions() ) ) {
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
	    return (array) apply_filters( 'woocommerce_gla_supported_coupon_types', [ 'percent', 'fixed_cart', 'fixed_product' ] );
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
	 * @param BatchInvalidCouponEntry[] $invalid_coupons
	 */
	protected function handle_update_errors( array $invalid_coupons ) {
	    // TODO: Handle update errors.
	}

	/**
	 * @param BatchInvalidCouponEntry[] $invalid_coupons
	 */
	protected function handle_delete_errors( array $invalid_coupons ) {
		// TODO: handle delete errors.
	}

	/**
	 * Validates whether Merchant Center is set up and connected.
	 *
	 * @throws CouponSyncerException If Google Merchant Center is not set up and connected.
	 */
	protected function validate_merchant_center_setup(): void {
		if ( ! $this->merchant_center->is_connected() ) {
			do_action( 'woocommerce_gla_error', 'Cannot sync any coupons before setting up Google Merchant Center.', __METHOD__ );

			throw new CouponSyncerException( __( 'Google Merchant Center has not been set up correctly. Please review your configuration.', 'google-listings-and-ads' ) );
		}
	}
}
