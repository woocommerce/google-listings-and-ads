<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
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
 * is complete, Ads setup was not completed, and the account has no Performance Max
 * campaigns (enabled or paused) — including any created outside the onboarding flow.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SkippedCampaignEvaluator implements NotificationEvaluatorInterface, AdsAwareInterface, Service {

	use AdsAwareTrait;
	use CachedNotificationEvaluatorTrait;

	/** @var AdsCampaign */
	private $ads_campaign;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * SkippedCampaignEvaluator constructor.
	 *
	 * @param AdsCampaign         $ads_campaign
	 * @param OnboardingCompleted $onboarding_completed
	 */
	public function __construct( AdsCampaign $ads_campaign, OnboardingCompleted $onboarding_completed ) {
		$this->ads_campaign         = $ads_campaign;
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

		// If Ads setup was completed, the merchant did not skip campaign creation.
		if ( $this->ads_service->is_setup_complete() ) {
			return false;
		}

		try {
			// Any Performance Max campaign (enabled or paused, including ones created
			// outside the onboarding flow) means the merchant did not skip campaigns.
			foreach ( $this->ads_campaign->get_campaigns( true, false ) as $campaign ) {
				if ( CampaignType::PERFORMANCE_MAX === $campaign['type'] ) {
					return false;
				}
			}
		} catch ( ExceptionWithResponseData $e ) {
			return false;
		}

		return true;
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
