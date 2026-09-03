<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateMerchantProductStatuses
 *
 * Update Product Stats
 *
 * Note: The job will not start if it is already running or if the Google Merchant Center account is not connected.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 *
 * @since 2.6.4
 */
class UpdateMerchantProductStatuses extends AbstractActionSchedulerJob {
	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var MapiProductsService
	 */
	protected $mapi_products;

	/**
	 * @var MerchantStatuses
	 */

	protected $merchant_statuses;

	/**
	 * UpdateMerchantProductStatuses constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param MerchantCenterService     $merchant_center
	 * @param MapiProductsService       $mapi_products
	 * @param MerchantStatuses          $merchant_statuses
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, MerchantCenterService $merchant_center, MapiProductsService $mapi_products, MerchantStatuses $merchant_statuses ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->merchant_center   = $merchant_center;
		$this->mapi_products     = $mapi_products;
		$this->merchant_statuses = $merchant_statuses;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_merchant_product_statuses';
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return parent::can_schedule( $args ) && $this->merchant_center->is_connected();
	}

	/**
	 * Process the job.
	 *
	 * @param int[] $items An array of job arguments.
	 *
	 * @throws JobException If the merchant product statuses cannot be retrieved..
	 */
	public function process_items( array $items ) {
		try {
			$next_page_token = $items['next_page_token'] ?? null;

			// Clear the cache if we're starting from the beginning.
			if ( ! $next_page_token ) {
				$this->merchant_statuses->clear_product_statuses_cache_and_issues();
				$this->merchant_statuses->refresh_account_and_presync_issues();
			}

			// One list page per action: the token travels in the action args, exactly
			// like the report page token did, but each page also carries the item-level
			// issues, so the whole refresh costs ceil(N/1000) requests in total.
			/**
			 * Filters the page size of the status refresh source.
			 *
			 * The hook predates the list-driven refresh: it originally sized the
			 * product_view report pages, and its name is kept on purpose so caps set
			 * by constrained hosts keep working now that the pages come from
			 * products.list instead. Default 1000 (500 in the report era), clamped
			 * to the API range 1-1000.
			 *
			 * @param int $page_size Products per list page.
			 */
			$page_size = min( 1000, max( 1, (int) apply_filters( 'woocommerce_gla_product_view_report_page_size', 1000 ) ) );

			$page            = $this->mapi_products->list_page( $next_page_token, $page_size );
			$next_page_token = $page['next_page_token'];

			$this->merchant_statuses->process_mapi_products( $page['products'] );

			if ( $next_page_token ) {
				$this->schedule( [ [ 'next_page_token' => $next_page_token ] ] );
			} else {
				$this->merchant_statuses->handle_complete_mc_statuses_fetching();
			}
		} catch ( Throwable $e ) {
			$this->merchant_statuses->handle_failed_mc_statuses_fetching( $e->getMessage() );
			throw new JobException( 'Error updating merchant product statuses: ' . $e->getMessage() );
		}
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args - arguments.
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule( $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), $args );
		}
	}

	/**
	 * The job is considered to be scheduled if the "process_item" action is currently pending or in-progress regardless of the arguments.
	 *
	 * @return bool
	 */
	public function is_scheduled(): bool {
		// We set 'args' to null so it matches any arguments. This is because it's possible to have multiple instances of the job running with different page tokens
		return $this->is_running( null );
	}

	/**
	 * Validate the failure rate of the job.
	 *
	 * @return string|void Returns an error message if the failure rate is too high, otherwise null.
	 */
	public function get_failure_rate_message() {
		try {
			$this->monitor->validate_failure_rate( $this, $this->get_process_item_hook() );
		} catch ( JobException $e ) {
			return $e->getMessage();
		}
	}
}
