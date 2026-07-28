<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\InvalidatableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Coupon;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponsNotSyncedEvaluator
 *
 * Fires when the merchant has at least one supported WooCommerce coupon but has
 * not synced any coupons to Google, and the target market supports coupon channel
 * visibility.
 *
 * Only coupons that can actually be synced are considered (see
 * CouponSyncer::is_coupon_supported() — excludes virtual, email-restricted, and
 * exclude-sale-items coupons). Markets that do not support coupon channel
 * visibility are excluded too, so the notification is never shown when there is
 * nothing the merchant could sync.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class CouponsNotSyncedEvaluator implements InvalidatableNotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;

	/**
	 * Number of coupon post IDs to fetch per query.
	 */
	private const COUPONS_PER_PAGE = 50;

	/**
	 * Database meta key for coupon sync status.
	 */
	private const SYNC_STATUS_META_KEY = '_wc_gla_' . CouponMetaHandler::KEY_SYNC_STATUS;

	/**
	 * @var MerchantCenterService
	 */
	private $merchant_center;

	/**
	 * @var TargetAudience
	 */
	private $target_audience;

	/**
	 * CouponsNotSyncedEvaluator constructor.
	 *
	 * @param MerchantCenterService $merchant_center
	 * @param TargetAudience        $target_audience
	 */
	public function __construct( MerchantCenterService $merchant_center, TargetAudience $target_audience ) {
		$this->merchant_center = $merchant_center;
		$this->target_audience = $target_audience;
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
	 * The signal fires once the merchant has at least one supported coupon but has
	 * not synced any coupons to Google yet.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		// Coupons can only be synced when the target market supports coupon channel
		// visibility. Bail early for unsupported markets so we never nudge merchants
		// to sync coupons that Google would reject.
		$target_country = $this->target_audience->get_main_target_country();
		if ( ! $this->merchant_center->is_promotion_supported_country( $target_country ) ) {
			return false;
		}

		// If any coupon is already synced to Google, the merchant has started syncing.
		if ( $this->has_synced_coupon() ) {
			return false;
		}

		// Only fire when there is at least one coupon that could actually be synced.
		return $this->has_supported_coupon();
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
	 * The set of unsynced coupons changes both when a coupon is created or edited (a new
	 * supported coupon can make the notification appear) and when one is synced to Google
	 * (which can make it disappear), so all three transitions must bust the cache.
	 *
	 * @return string[]
	 */
	public function get_invalidation_hooks(): array {
		return [
			'woocommerce_new_coupon',
			'woocommerce_update_coupon',
			'woocommerce_gla_updated_coupon',
		];
	}

	/**
	 * Whether the merchant has at least one coupon that is supported for syncing.
	 *
	 * Pages through published coupons and returns true as soon as a supported one
	 * is found (see CouponSyncer::is_coupon_supported()).
	 *
	 * @return bool
	 */
	protected function has_supported_coupon(): bool {
		$page = 1;

		while ( true ) {
			$coupon_post_ids = $this->get_coupon_post_ids( $page );

			if ( empty( $coupon_post_ids ) ) {
				return false;
			}

			foreach ( $coupon_post_ids as $post_id ) {
				if ( CouponSyncer::is_coupon_supported( $this->create_coupon( $post_id ) ) ) {
					return true;
				}
			}

			++$page;
		}
	}

	/**
	 * Get a page of published coupon post IDs.
	 *
	 * @param int $page Page number for paginated queries.
	 *
	 * @return int[]
	 */
	protected function get_coupon_post_ids( int $page = 1 ): array {
		$coupon_posts = get_posts(
			[
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => self::COUPONS_PER_PAGE,
				'paged'          => $page,
				'fields'         => 'ids',
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

	/**
	 * Whether at least one coupon has already been synced to Google.
	 *
	 * @return bool
	 */
	protected function has_synced_coupon(): bool {
		$synced_coupons = get_posts(
			[
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => self::SYNC_STATUS_META_KEY,
						'value' => SyncStatus::SYNCED,
					],
				],
			]
		);

		return ! empty( $synced_coupons );
	}
}
