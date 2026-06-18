<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Coupon;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponsNotSyncedEvaluator
 *
 * Fires when at least one supported coupon is in NOT_SYNCED status.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class CouponsNotSyncedEvaluator implements NotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;

	/** @var CouponHelper */
	protected $coupon_helper;

	/**
	 * CouponsNotSyncedEvaluator constructor.
	 *
	 * @param CouponHelper $coupon_helper
	 */
	public function __construct( CouponHelper $coupon_helper ) {
		$this->coupon_helper = $coupon_helper;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'coupons-not-synced';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		foreach ( $this->get_coupons() as $coupon ) {
			if ( ! CouponSyncer::is_coupon_supported( $coupon ) ) {
				continue;
			}

			if ( SyncStatus::NOT_SYNCED === $this->coupon_helper->get_sync_status( $coupon ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::COUPONS_NOT_SYNCED;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::COUPONS_NOT_SYNCED;
	}

	/**
	 * Get all WooCommerce coupons.
	 *
	 * @return WC_Coupon[]
	 */
	protected function get_coupons(): array {
		$coupon_posts = get_posts(
			[
				'post_type'      => 'shop_coupon',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			]
		);

		return array_map(
			static function ( $post ) {
				return new WC_Coupon( $post->ID );
			},
			$coupon_posts
		);
	}
}
