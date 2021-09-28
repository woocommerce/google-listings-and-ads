<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
defined( 'ABSPATH' ) || exit();

/**
 * Class DeleteCouponEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class DeleteCouponEntry {

	/**
	 *
	 * @var WCCouponAdapter
	 */
	protected $coupon;

	/**
	 *
	 * @var array List of country to google promotion id mappings
	 */
	protected $synced_google_ids;

	/**
	 * BatchProductRequestEntry constructor.
	 *
	 * @param WCCouponAdapter $coupon
	 * @param array           $synced_google_ids
	 */
	public function __construct(
		WCCouponAdapter $coupon,
		array $synced_google_ids ) {
		$this->coupon            = $coupon;
		$this->synced_google_ids = $synced_google_ids;
	}

	/**
	 *
	 * @return WCCouponAdapter
	 */
	public function get_coupon(): WCCouponAdapter {
		return $this->coupon;
	}

	/**
	 *
	 * @return array
	 */
	public function get_synced_google_ids(): array {
		return $this->synced_google_ids;
	}
}
