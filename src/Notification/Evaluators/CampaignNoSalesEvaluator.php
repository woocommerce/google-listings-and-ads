<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\InvalidatableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class CampaignNoSalesEvaluator
 *
 * Fires when at least one enabled campaign has recorded zero attributed sales and no raise budget
 * recommendation is available.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class CampaignNoSalesEvaluator implements InvalidatableNotificationEvaluatorInterface, AdsAwareInterface, Service {

	use AdsAwareTrait;
	use CachedNotificationEvaluatorTrait;

	/** @var AdsCampaign */
	private $ads_campaign;

	/** @var AdsReport */
	private $ads_report;

	/** @var AdsRecommendationsService */
	private $ads_recommendations;

	/**
	 * CampaignNoSalesEvaluator constructor.
	 *
	 * @param AdsCampaign               $ads_campaign
	 * @param AdsReport                 $ads_report
	 * @param AdsRecommendationsService $ads_recommendations
	 */
	public function __construct( AdsCampaign $ads_campaign, AdsReport $ads_report, AdsRecommendationsService $ads_recommendations ) {
		$this->ads_campaign        = $ads_campaign;
		$this->ads_report          = $ads_report;
		$this->ads_recommendations = $ads_recommendations;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'campaign-no-sales';
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
				// campaign_id = 0 fetches recommendations account-wide, not filtered to a specific campaign.
				'campaign_id' => 0,
			]
		);

		if ( ! empty( $budget_recommendations ) ) {
			return false;
		}

		try {
			$enabled_campaigns = array_filter(
				$this->ads_campaign->get_campaigns( true, false ),
				static function ( array $campaign ): bool {
					return CampaignStatus::ENABLED === $campaign['status'];
				}
			);
		} catch ( ExceptionWithResponseData $e ) {
			return false;
		}

		if ( empty( $enabled_campaigns ) ) {
			return false;
		}

		try {
			$report_data = $this->ads_report->get_report_data(
				'campaigns',
				[
					'fields' => [ 'conversions' ],
				]
			);
		} catch ( ExceptionWithResponseData $e ) {
			return false;
		}

		$campaign_conversions = [];

		foreach ( $report_data['campaigns'] ?? [] as $campaign_report ) {
			$campaign_conversions[ $campaign_report['id'] ] = $campaign_report['subtotals']['conversions'] ?? 0;
		}

		foreach ( $enabled_campaigns as $campaign ) {
			$conversions = $campaign_conversions[ $campaign['id'] ] ?? 0;

			if ( 0.0 === (float) $conversions ) {
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
		return NotificationPriorities::CAMPAIGN_NO_SALES;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::CAMPAIGN_NO_SALES;
	}

	/**
	 * Creating or deleting a campaign changes which campaigns this evaluates; the "no sales"
	 * side still relies on the cache's hourly refresh.
	 *
	 * @return string[]
	 */
	public function get_invalidation_hooks(): array {
		return [ 'woocommerce_gla_updated_campaign' ];
	}
}
