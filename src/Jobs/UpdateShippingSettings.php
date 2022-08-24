<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings as GoogleSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;

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
class UpdateShippingSettings extends AbstractActionSchedulerJob {
	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var GoogleSettings
	 */
	protected $google_settings;

	/**
	 * UpdateShippingSettings constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param MerchantCenterService     $merchant_center
	 * @param GoogleSettings            $google_settings
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, MerchantCenterService $merchant_center, GoogleSettings $google_settings ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->merchant_center = $merchant_center;
		$this->google_settings = $google_settings;
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
	 */
	public function process_items( array $items ) {
		if ( ! $this->can_sync_shipping() ) {
			throw new JobException( 'Cannot sync shipping settings. Confirm that the merchant center account is connected and the option to automatically sync the shipping settings is selected.' );
		}

		$this->google_settings->sync_shipping();
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
		// Confirm that the Merchant Center account is connected and the user has chosen for the shipping rates to be synced from WooCommerce settings.
		return $this->merchant_center->is_connected() && $this->google_settings->should_get_shipping_rates_from_woocommerce();
	}
}
