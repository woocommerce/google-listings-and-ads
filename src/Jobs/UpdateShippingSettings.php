<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings as GoogleSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use DateTime;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateShippingSettings
 *
 * Submits WooCommerce shipping settings to Google Merchant Center replacing the existing shipping settings.
 *
 * Note: The job will not start if it is already running or if the Google Merchant Center account is not connected.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 *
 * @since 2.1.0
 */
class UpdateShippingSettings extends AbstractActionSchedulerJob implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var GoogleSettings
	 */
	protected $google_settings;

	/**
	 * @var MarketService
	 */
	protected $market_service;

	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * UpdateShippingSettings constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param MerchantCenterService     $merchant_center
	 * @param GoogleSettings            $google_settings
	 * @param MarketService             $market_service
	 * @param MerchantStatuses          $merchant_statuses
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		MerchantCenterService $merchant_center,
		GoogleSettings $google_settings,
		MarketService $market_service,
		MerchantStatuses $merchant_statuses
	) {
		parent::__construct( $action_scheduler, $monitor );
		$this->merchant_center   = $merchant_center;
		$this->google_settings   = $google_settings;
		$this->market_service    = $market_service;
		$this->merchant_statuses = $merchant_statuses;
	}

	/**
	 * Initialize the job's hooks.
	 *
	 * Alongside the process hook, registers the stored sync failure as a
	 * Merchant Center issue so the failure is shown in the plugin instead of
	 * only in Scheduled Actions, where the failure-rate block hides the job
	 * after repeated failures.
	 */
	public function init(): void {
		parent::init();

		add_filter(
			'woocommerce_gla_custom_merchant_issues',
			[ $this, 'add_shipping_sync_failure_issue' ],
			10,
			2
		);
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_shipping_settings';
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return parent::can_schedule( $args ) && $this->can_sync_shipping();
	}

	/**
	 * Process the job.
	 *
	 * @param int[] $items An array of job arguments.
	 *
	 * @throws JobException If the shipping settings cannot be synced.
	 * @throws Exception If the shipping settings sync fails. The failure is
	 *                   stored first so it can be shown in the plugin as a
	 *                   Merchant Center issue.
	 */
	public function process_items( array $items ) {
		if ( ! $this->can_sync_shipping() ) {
			throw new JobException( 'Cannot sync shipping settings. Confirm that the merchant center account is connected and the option to automatically sync the shipping settings is selected.' );
		}

		try {
			$this->google_settings->sync_shipping();
		} catch ( Exception $exception ) {
			$this->options->update(
				OptionsInterface::SHIPPING_SYNC_FAILURE,
				[
					'message'   => $exception->getMessage(),
					'failed_at' => gmdate( 'Y-m-d H:i:s' ),
				]
			);
			$this->merchant_statuses->clear_cache();

			throw $exception;
		}

		$this->clear_reported_failure();
	}

	/**
	 * Turns the stored sync failure into a Merchant Center issue row.
	 *
	 * @hooked woocommerce_gla_custom_merchant_issues
	 *
	 * @param array    $issues             The custom issues collected so far.
	 * @param DateTime $cache_created_time The issue cache creation time.
	 *
	 * @return array
	 */
	public function add_shipping_sync_failure_issue( array $issues, DateTime $cache_created_time ): array {
		$failure = $this->options->get( OptionsInterface::SHIPPING_SYNC_FAILURE );

		if ( empty( $failure['message'] ) ) {
			return $issues;
		}

		$issues[] = [
			'product_id' => 0,
			'product'    => 'All products',
			'code'       => 'shipping_settings_sync_failed',
			'issue'      => __( 'The shipping settings could not be synced to Google Merchant Center.', 'google-listings-and-ads' ),
			'action'     => sprintf(
				/* translators: %s: the error reported by the failed sync. */
				__( 'Resolve the reported problem, then save your shipping settings to retry the sync. Last error: %s', 'google-listings-and-ads' ),
				$failure['message']
			),
			'action_url' => $this->get_settings_url(),
			'created_at' => $cache_created_time->format( 'Y-m-d H:i:s' ),
			'type'       => MerchantStatuses::TYPE_ACCOUNT,
			'severity'   => 'error',
			'source'     => 'filter',
		];

		return $issues;
	}

	/**
	 * Clears a previously stored sync failure after a successful sync, and
	 * refreshes the cached statuses so the issue row disappears promptly.
	 */
	protected function clear_reported_failure(): void {
		if ( empty( $this->options->get( OptionsInterface::SHIPPING_SYNC_FAILURE ) ) ) {
			return;
		}

		$this->options->delete( OptionsInterface::SHIPPING_SYNC_FAILURE );
		$this->merchant_statuses->clear_cache();
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args - arguments.
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule() ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook() );
		}
	}

	/**
	 * Can the WooCommerce shipping settings be synced to Google Merchant Center.
	 *
	 * @return bool
	 */
	protected function can_sync_shipping(): bool {
		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		return $this->market_service->has_syncable_markets();
	}
}
