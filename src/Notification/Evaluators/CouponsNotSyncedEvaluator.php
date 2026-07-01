<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponsNotSyncedEvaluator
 *
 * Fires when the merchant has at least one WooCommerce coupon but has not synced
 * any coupons to Google, and the target market supports coupon channel visibility.
 *
 * Markets that do not support coupon channel visibility are excluded here so the
 * notification is never shown when there is nothing the merchant could sync.
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
	 * The signal fires once the merchant has at least one coupon but has not
	 * synced any coupons to Google yet.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		// Coupons can only be synced when the target market supports coupon channel
		// visibility. Bail early for unsupported markets so we never nudge merchants
		// to sync coupons that Google would reject.
		if ( ! $this->is_target_market_supported() ) {
			return false;
		}

		// The merchant needs at least one coupon before we can prompt them to sync.
		if ( ! $this->has_coupon() ) {
			return false;
		}

		// Only fire while none of the coupons have been synced to Google yet.
		return ! $this->has_synced_coupon();
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
	 * Whether the merchant's main target country supports coupon channel visibility.
	 *
	 * @return bool
	 */
	protected function is_target_market_supported(): bool {
		return $this->merchant_center->is_promotion_supported_country(
			$this->target_audience->get_main_target_country()
		);
	}

	/**
	 * Whether the merchant has at least one published coupon.
	 *
	 * @return bool
	 */
	protected function has_coupon(): bool {
		$coupons = get_posts(
			[
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);

		return ! empty( $coupons );
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
