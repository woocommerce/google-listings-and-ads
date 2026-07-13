<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

defined( 'ABSPATH' ) || exit;

/**
 * Class SkippedCampaignEvaluator
 *
 * Fires when the merchant finished onboarding but skipped campaign creation: onboarding
 * is complete and Ads setup was not completed. Ads setup is marked complete once the
 * merchant creates a campaign through the plugin, so an incomplete Ads setup is a
 * reliable indicator that they skipped campaign creation.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SkippedCampaignEvaluator implements NotificationEvaluatorInterface, AdsAwareInterface, Service {

	use AdsAwareTrait;
	use CachedNotificationEvaluatorTrait;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * SkippedCampaignEvaluator constructor.
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
		return 'skipped-campaign-creation';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		// Only merchants who finished onboarding are candidates for "skipped campaign".
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		// Ads setup is marked complete once the merchant creates a campaign through the
		// plugin, so an incomplete Ads setup means they skipped campaign creation.
		return ! $this->ads_service->is_setup_complete();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SKIPPED_CAMPAIGN_CREATION;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return null;
	}
}
