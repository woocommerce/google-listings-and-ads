<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\InvalidatableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class RecommendationsAvailableEvaluator
 *
 * Fires when the recommendations endpoint returns at least one entry.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class RecommendationsAvailableEvaluator implements InvalidatableNotificationEvaluatorInterface, AdsAwareInterface, Service {

	use AdsAwareTrait;
	use CachedNotificationEvaluatorTrait;

	/** @var AdsRecommendationsService */
	private $ads_recommendations;

	/** @var AdsCampaign */
	private $ads_campaign;

	/**
	 * RecommendationsAvailableEvaluator constructor.
	 *
	 * @param AdsRecommendationsService $ads_recommendations
	 * @param AdsCampaign               $ads_campaign
	 */
	public function __construct( AdsRecommendationsService $ads_recommendations, AdsCampaign $ads_campaign ) {
		$this->ads_recommendations = $ads_recommendations;
		$this->ads_campaign        = $ads_campaign;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'recommendations-available';
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

		$budget_recommendations = $this->ads_recommendations->get_recommendations(
			[
				'types'       => [
					'CAMPAIGN_BUDGET',
					'MARGINAL_ROI_CAMPAIGN_BUDGET',
				],
				'campaign_id' => 0,
			]
		);

		if ( ! empty( $budget_recommendations ) ) {
			return true;
		}

		$campaign = $this->ads_campaign->get_highest_spend_campaign();

		if ( empty( $campaign['id'] ) ) {
			return false;
		}

		$pmax_recommendations = $this->ads_recommendations->get_recommendations(
			[
				'types'       => [ 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH' ],
				'campaign_id' => $campaign['id'],
			]
		);

		return ! empty( $pmax_recommendations );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::RECOMMENDATIONS_AVAILABLE;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::RECOMMENDATIONS_AVAILABLE;
	}

	/**
	 * Recommendations are campaign-derived, so a campaign being created/edited/deleted can
	 * change what Google recommends; Google-side changes are picked up on the hourly refresh.
	 *
	 * @return string[]
	 */
	public function get_invalidation_hooks(): array {
		return [ 'woocommerce_gla_updated_campaign' ];
	}
}
