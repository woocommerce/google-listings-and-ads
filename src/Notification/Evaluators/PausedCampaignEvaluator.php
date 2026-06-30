<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class PausedCampaignEvaluator
 *
 * Fires when at least one campaign is paused.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class PausedCampaignEvaluator implements NotificationEvaluatorInterface, AdsAwareInterface, Service {

	use AdsAwareTrait;
	use CachedNotificationEvaluatorTrait;

	/** @var AdsCampaign */
	private $ads_campaign;

	/**
	 * PausedCampaignEvaluator constructor.
	 *
	 * @param AdsCampaign $ads_campaign
	 */
	public function __construct( AdsCampaign $ads_campaign ) {
		$this->ads_campaign = $ads_campaign;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'paused-campaign';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		if ( ! $this->ads_service->is_setup_complete() ) {
			return false;
		}

		try {
			foreach ( $this->ads_campaign->get_campaigns( true, false ) as $campaign ) {
				if ( CampaignStatus::PAUSED === $campaign['status'] ) {
					return true;
				}
			}
		} catch ( ExceptionWithResponseData $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::PAUSED_CAMPAIGN;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::PAUSED_CAMPAIGN;
	}
}
