<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
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
	 * @param array $items Job arguments, optionally carrying the next_page_token to continue from
	 *                     and the run_started_at of the run it belongs to.
	 *
	 * @throws JobException If the merchant product statuses cannot be retrieved.
	 */
	public function process_items( array $items ) {
		// Disconnected since this page was scheduled: nothing to fetch, and not a failure.
		if ( ! $this->merchant_center->is_connected() ) {
			return;
		}

		try {
			$next_page_token = $items['next_page_token'] ?? null;

			// Start a run when we're starting from the beginning. Strict check: a
			// pagination token is an opaque string, and PHP would treat "0" as falsy.
			// The previous run's statuses and issues are not cleared here: they stay
			// readable until this run replaces them, and only a completed run removes
			// what it did not refresh (MerchantStatuses::handle_complete_mc_statuses_fetching()).
			$run_started_at = isset( $items['run_started_at'] ) ? (int) $items['run_started_at'] : null;
			if ( null === $next_page_token ) {
				$run_started_at = $this->merchant_statuses->start_status_refresh();
				$this->merchant_statuses->refresh_account_and_presync_issues();
			} elseif ( null !== $run_started_at ) {
				// A newer run started since this one: stop here, without fetching, writing
				// or scheduling, so the two runs don't remove each other's results.
				if ( $this->is_superseded( $run_started_at ) ) {
					return;
				}
				$this->merchant_statuses->continue_status_refresh( $run_started_at );
			}

			// One list page per action: the token travels in the action args, exactly
			// like the report page token did, but each page also carries the item-level
			// issues, so the whole refresh costs one request per list page.
			/**
			 * Filters the page size of the status refresh source.
			 *
			 * The hook predates the list-driven refresh: it originally sized the
			 * product_view report pages, and its name is kept on purpose so caps set
			 * by constrained hosts keep working now that the pages come from
			 * products.list instead. The default stays at the report era's 500: a
			 * list entry is far heavier than a report row (productStatus carries the
			 * item-level issues with per-reporting-context country lists), so a full
			 * 1000-entry page can cost hundreds of MB of decode peak on issue-heavy
			 * catalogues. Clamped to the API range 1-1000.
			 *
			 * @param int $page_size Products per list page.
			 */
			$page_size = min( 1000, max( 1, (int) apply_filters( 'woocommerce_gla_product_view_report_page_size', 500 ) ) );

			$page            = $this->mapi_products->list_page( $next_page_token, $page_size );
			$next_page_token = $page['next_page_token'];

			// A newer run may have started while this page was being fetched.
			if ( $this->is_superseded( $run_started_at ) ) {
				return;
			}

			$this->merchant_statuses->process_mapi_products( $page['products'] );

			if ( null !== $next_page_token ) {
				$next_page = [ 'next_page_token' => $next_page_token ];
				if ( null !== $run_started_at ) {
					$next_page['run_started_at'] = $run_started_at;
				}
				$this->schedule( [ $next_page ] );
			} elseif ( ! $this->is_superseded( $run_started_at ) ) {
				// Completing removes what the run did not refresh, so check once more.
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
		if ( ! $this->can_schedule( $args ) ) {
			return;
		}

		// A run's first page (no arguments) is scheduled atomically unique, so two
		// requests starting a refresh at once cannot create two first pages. Later pages
		// carry their own token, and the page scheduling them is still running.
		if ( empty( $args ) && $this->action_scheduler instanceof ActionScheduler ) {
			$this->action_scheduler->schedule_immediate_unique( $this->get_process_item_hook(), $args );
			return;
		}

		$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), $args );
	}

	/**
	 * Whether the run a page belongs to was superseded by a newer run.
	 *
	 * @param int|null $run_started_at Null for pages of runs started before run tracking.
	 *
	 * @return bool
	 */
	protected function is_superseded( ?int $run_started_at ): bool {
		return null !== $run_started_at && ! $this->merchant_statuses->is_current_status_refresh( $run_started_at );
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
