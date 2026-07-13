<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit();

/**
 * Class DeleteCouponEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class DeleteCouponEntry {

	/** @var int */
	protected $wc_coupon_id;

	/** @var array The Merchant API promotion resource (expired, for soft-delete). */
	protected $google_promotion;

	/** @var array List of country to google promotion id mappings. */
	protected $synced_google_ids;

	/**
	 * DeleteCouponEntry constructor.
	 *
	 * @param int   $wc_coupon_id
	 * @param array $google_promotion  The Merchant API promotion resource.
	 * @param array $synced_google_ids
	 */
	public function __construct( int $wc_coupon_id, array $google_promotion, array $synced_google_ids ) {
		$this->wc_coupon_id      = $wc_coupon_id;
		$this->google_promotion  = $google_promotion;
		$this->synced_google_ids = $synced_google_ids;
	}

	/**
	 * @return int
	 */
	public function get_wc_coupon_id(): int {
		return $this->wc_coupon_id;
	}

	/**
	 * @return array The Merchant API promotion resource.
	 */
	public function get_google_promotion(): array {
		return $this->google_promotion;
	}

	/**
	 * @return array
	 */
	public function get_synced_google_ids(): array {
		return $this->synced_google_ids;
	}
}
