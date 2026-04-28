<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateEuPoliticalCampaigns
 *
 * Update non-EU campaigns and set the EU Political declaration to false.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since 2.2.0
 */
class UpdateEuPoliticalCampaigns extends AbstractBatchedActionSchedulerJob implements RecurringJobInterface, OptionsAwareInterface {
	use OptionsAwareTrait;

	/**
	 * @var AdsCampaign
	 */
	protected $ads_campaign;

	/**
	 * @var AdsService
	 */
	protected $ads_service;

	/**
	 * CreateYouTubeOrderIdsCache constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param AdsCampaign               $ads_campaign
	 * @param AdsService                $ads_service
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, AdsCampaign $ads_campaign, AdsService $ads_service ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->ads_campaign = $ads_campaign;
		$this->ads_service  = $ads_service;
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		// Stop scheduling once this Ads account has no more campaigns needing the EU political declaration.
		// The flag is cleared on Ads disconnect, so reconnecting a different account re-enables the job.
		if ( $this->options->get( OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE ) ) {
			return false;
		}

		return parent::can_schedule( $args ) && $this->ads_service->is_connected();
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_eu_political_campaigns';
	}

	/**
	 * Get job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		/**
		 * Filters the batch size for the job.
		 *
		 * @param string Job's name
		 */
		return apply_filters( 'woocommerce_gla_batched_job_size', 100, $this->get_name() );
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return int[]
	 */
	public function get_batch( int $batch_number ): array {
		$limit  = $this->get_batch_size();
		$offset = ( $batch_number - 1 ) * $limit;

		$items = $this->ads_campaign->get_campaigns_missing_eu_political_declaration();

		return array_slice( $items, $offset, $limit );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce Order IDs from the get_batch() method.
	 *
	 * @throws \Exception If an error occurs during caching.
	 */
	protected function process_items( array $items ) {
		if ( empty( $items ) ) {
			return;
		}

		$ids = array_column( $items, 'id' );

		$campaigns = $this->ads_campaign->get_campaigns_by_ids( $ids );

		$updates = [];

		foreach ( $campaigns as $campaign ) {
			$countries = $campaign['targeted_locations'] ?? [];

			if ( ! $this->is_eu_targeting( $countries ) ) {
				$updates[] = [
					'id'    => $campaign['id'],
					'value' => false,
				];
			}
		}

		if ( ! empty( $updates ) ) {
			$this->ads_campaign->set_eu_political_campaigns( $updates );
		}
	}

	/**
	 * Determine if a campaign targets an EU country.
	 *
	 * @param array $targeted_locations The campaigns targeted locations
	 * @return boolean True if one or more targeted locations are an EU country.
	 */
	protected function is_eu_targeting( array $targeted_locations ): bool {
		$eu_countries = [
			'AT',
			'BE',
			'BG',
			'HR',
			'CY',
			'CZ',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HU',
			'IE',
			'IT',
			'LV',
			'LT',
			'LU',
			'MT',
			'NL',
			'PL',
			'PT',
			'RO',
			'SK',
			'SI',
			'ES',
			'SE',
			'GB',
		];

		foreach ( $targeted_locations as $country_code ) {
			if ( in_array( strtoupper( $country_code ), $eu_countries, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int
	 */
	public function get_interval(): int {
		return 24 * 60 * 60; // 24 hours
	}
}
