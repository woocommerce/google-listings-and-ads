<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
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

	/**
	 * Database meta key for coupon sync status.
	 */
	private const SYNC_STATUS_META_KEY = '_wc_gla_' . CouponMetaHandler::KEY_SYNC_STATUS;

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
		$page = 1;

		while ( true ) {
			$coupon_post_ids = $this->get_not_synced_coupon_post_ids( $page );

			if ( empty( $coupon_post_ids ) ) {
				return false;
			}

			if ( CouponSyncer::is_coupon_supported( $this->create_coupon( $coupon_post_ids[0] ) ) ) {
				return true;
			}

			++$page;
		}
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
	 * Get a page of post IDs for published coupons with a NOT_SYNCED sync status.
	 *
	 * @param int $page Page number for paginated queries.
	 *
	 * @return int[]
	 */
	protected function get_not_synced_coupon_post_ids( int $page = 1 ): array {
		$coupon_posts = get_posts(
			[
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'paged'          => $page,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => self::SYNC_STATUS_META_KEY,
						'value' => SyncStatus::NOT_SYNCED,
					],
				],
			]
		);

		return array_map( 'intval', $coupon_posts );
	}

	/**
	 * Create a coupon object for a post ID.
	 *
	 * @param int $post_id Coupon post ID.
	 *
	 * @return WC_Coupon
	 */
	protected function create_coupon( int $post_id ): WC_Coupon {
		return new WC_Coupon( $post_id );
	}
}
