<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Runs update jobs when the plugin is updated.
 *
 * A job that can't be scheduled during the upgrade (e.g. a product re-sync while the store
 * isn't ready to sync with Merchant Center) is kept in an option and retried on later admin
 * requests until it is scheduled. The installer advances the DB version regardless, so
 * without this the job would never run.
 *
 * @since 1.1.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update
 */
class PluginUpdate implements Service, Registerable, InstallableInterface, OptionsAwareInterface, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * PluginUpdate constructor.
	 *
	 * @param JobRepository $job_repository
	 */
	public function __construct( JobRepository $job_repository ) {
		$this->job_repository = $job_repository;
	}

	/**
	 * Update Jobs that need to be run per version.
	 *
	 * @var array
	 */
	private $updates = [
		'1.0.1'  => [
			CleanupProductTargetCountriesJob::class,
			UpdateAllProducts::class,
		],
		'1.12.6' => [
			UpdateAllProducts::class,
		],
		// Merchant API release: re-sync all products so ids synced under the legacy Content API
		// (colon format, rejected by the Merchant API) are rewritten to the Merchant API format.
		'3.8.0'  => [
			UpdateAllProducts::class,
		],
	];

	/**
	 * Register a service.
	 *
	 * @since x.x.x
	 */
	public function register(): void {
		add_action(
			'admin_init',
			function () {
				$this->retry_pending_jobs();
			}
		);
	}

	/**
	 * Run installation logic for this class.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		$jobs = [];
		foreach ( $this->updates as $version => $version_jobs ) {
			if ( version_compare( $old_version, $version, '<' ) ) {
				$jobs = array_merge( $jobs, $version_jobs );
			}
		}

		if ( ! empty( $jobs ) ) {
			$this->schedule_jobs( $jobs );
		}
	}

	/**
	 * Retry the update jobs that couldn't be scheduled during a previous upgrade.
	 *
	 * Cheap when nothing is pending: a single (autoloaded) option read.
	 *
	 * @since x.x.x
	 */
	public function retry_pending_jobs(): void {
		if ( defined( 'IFRAME_REQUEST' ) || wp_doing_ajax() ) {
			return;
		}

		if ( empty( $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ) ) ) {
			return;
		}

		// Also clears an option left with no usable jobs.
		$this->schedule_jobs( [] );
	}

	/**
	 * Schedules a list of jobs, together with any still pending from a previous upgrade.
	 *
	 * Each job is scheduled at most once, even when several versions ask for it. Jobs that
	 * can't be scheduled yet are stored to be retried later, unless Merchant Center isn't
	 * connected: then nothing was synced that needs migrating, and connecting runs a full sync.
	 *
	 * @param array $jobs List of jobs
	 */
	protected function schedule_jobs( array $jobs ): void {
		$pending = array_values( array_unique( array_merge( $this->get_pending_jobs(), $jobs ) ) );

		$unscheduled = [];
		foreach ( $pending as $job ) {
			try {
				if ( ! $this->schedule_job( $job ) ) {
					$unscheduled[] = $job;
				}
			} catch ( JobException $e ) {
				// The job doesn't exist (anymore), so retrying it can't succeed.
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			}
		}

		if ( ! empty( $unscheduled ) && ! $this->merchant_center->is_connected() ) {
			$unscheduled = [];
		}

		$this->set_pending_jobs( $unscheduled );
	}

	/**
	 * Schedule a single job if it can be scheduled now.
	 *
	 * `schedule()` silently does nothing when the job can't be scheduled, so ask the job first
	 * through `can_schedule()`. The `null` args match the job running with any arguments: a job
	 * that is already running is retried once it finishes, so the update still runs in full on
	 * the new version.
	 *
	 * @since x.x.x
	 *
	 * @param string $job Job class name.
	 *
	 * @return bool Whether the job was scheduled.
	 *
	 * @throws JobException If the job doesn't exist.
	 */
	protected function schedule_job( string $job ): bool {
		$job = $this->job_repository->get( $job );

		if ( $job instanceof ActionSchedulerJobInterface ) {
			if ( ! $job->can_schedule( null ) ) {
				return false;
			}

			$job->schedule();
		}

		return true;
	}

	/**
	 * Get the update jobs still waiting to be scheduled.
	 *
	 * Only job classes listed in the updates are returned, so a stale or unexpected
	 * value in the option is never passed on to the job repository.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	protected function get_pending_jobs(): array {
		$pending = $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, [] );
		if ( ! is_array( $pending ) || empty( $pending ) ) {
			return [];
		}

		$known = array_merge( ...array_values( $this->updates ) );

		$pending = array_filter( $pending, 'is_string' );

		return array_values( array_intersect( array_unique( $pending ), $known ) );
	}

	/**
	 * Store the update jobs still waiting to be scheduled, deleting the option when there are none.
	 *
	 * @since x.x.x
	 *
	 * @param string[] $jobs Job class names.
	 */
	protected function set_pending_jobs( array $jobs ): void {
		$stored = $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, [] );

		if ( empty( $jobs ) ) {
			if ( ! empty( $stored ) ) {
				$this->options->delete( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS );
			}
			return;
		}

		if ( $stored !== $jobs ) {
			$this->options->update( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $jobs );
		}
	}
}
