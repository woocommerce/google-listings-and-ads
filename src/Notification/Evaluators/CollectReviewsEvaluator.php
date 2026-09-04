<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class CollectReviewsEvaluator
 *
 * Fires after onboarding completes when the merchant has a connected Merchant Center
 * account and post-purchase Google review collection is not yet enabled.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class CollectReviewsEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, OptionsAwareInterface, Service {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	/**
	 * Key of the post-purchase review collection flag within OptionsInterface::GOOGLE_CUSTOMER_REVIEWS.
	 */
	private const SETTING_KEY = 'gcr_collect_reviews_after_purchase';

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * CollectReviewsEvaluator constructor.
	 *
	 * @param OnboardingCompleted $onboarding_completed
	 */
	public function __construct( OnboardingCompleted $onboarding_completed ) {
		$this->onboarding_completed = $onboarding_completed;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'google-customer-reviews-collect-reviews';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		$settings = $this->options->get( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, [] );

		return empty( $settings[ self::SETTING_KEY ] );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::COLLECT_REVIEWS;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::COLLECT_REVIEWS;
	}
}
