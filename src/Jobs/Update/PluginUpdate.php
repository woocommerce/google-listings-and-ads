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
 * isn't ready to sync with Merchant Center) is kept in an option and retried from later admin
 * requests, at most once per RETRY_INTERVAL, until it is scheduled. The installer advances the
 * DB version regardless, so without this the job would never run.
 *
 * @since 1.1.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update
 */
class PluginUpdate implements Service, Registerable, InstallableInterface, OptionsAwareInterface, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	/**
	 * Minimum time between two attempts to schedule a pending job.
	 *
	 * @var int
	 */
	protected const RETRY_INTERVAL = HOUR_IN_SECONDS;

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
	 * Also attempts the jobs still pending from earlier upgrades, and creates the pending jobs
	 * option on stores that don't have it yet, so retry_pending_jobs() reads it from the
	 * autoloaded options instead of querying the database for a missing option.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		$jobs = array_keys( $this->get_pending_jobs() );
		foreach ( $this->updates as $version => $version_jobs ) {
			if ( version_compare( $old_version, $version, '<' ) ) {
				$jobs = array_merge( $jobs, $version_jobs );
			}
		}

		$this->schedule_jobs( $jobs );
	}

	/**
	 * Retry the update jobs that couldn't be scheduled during a previous upgrade.
	 *
	 * Runs on every admin request. With nothing pending it costs one read of the autoloaded
	 * option (created by install()). A pending job is retried at most once per RETRY_INTERVAL.
	 *
	 * @since x.x.x
	 */
	public function retry_pending_jobs(): void {
		if ( defined( 'IFRAME_REQUEST' ) || wp_doing_ajax() ) {
			return;
		}

		$stored = $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS );
		if ( empty( $stored ) ) {
			return;
		}

		$now     = time();
		$pending = $this->get_pending_jobs();
		$due     = array_keys(
			array_filter(
				$pending,
				function ( int $attempted_at ) use ( $now ) {
					return $now - $attempted_at >= self::RETRY_INTERVAL;
				}
			)
		);

		// Nothing due, and the stored value is valid: nothing to do until the next interval.
		if ( empty( $due ) && $pending === $stored ) {
			return;
		}

		$this->schedule_jobs( $due );
	}

	/**
	 * Schedules a list of jobs, tracking those that can't be scheduled yet.
	 *
	 * Each job is attempted at most once, even when several versions ask for it. A job that
	 * can't be scheduled is stored with the time of the attempt, to be retried later. Nothing
	 * is kept when Merchant Center setup isn't complete: no products were synced that need
	 * migrating, and completing setup runs a full sync.
	 *
	 * @param array $jobs List of jobs
	 */
	protected function schedule_jobs( array $jobs ): void {
		$jobs           = array_values( array_unique( $jobs ) );
		$pending        = $this->get_pending_jobs();
		$setup_complete = $this->merchant_center->is_setup_complete();
		$now            = time();

		// Stamp the pending jobs about to be attempted first, so a concurrent admin request
		// sees them as not due and doesn't schedule them a second time.
		$updated = $pending;
		foreach ( array_intersect( $jobs, array_keys( $pending ) ) as $job ) {
			$updated[ $job ] = $now;
		}
		$this->save_pending_jobs( $updated );

		foreach ( $jobs as $job ) {
			try {
				$scheduled = $this->schedule_job( $job );
			} catch ( JobException $e ) {
				// The job doesn't exist (anymore), so retrying it can't succeed.
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
				unset( $updated[ $job ] );
				continue;
			}

			if ( $scheduled ) {
				if ( isset( $updated[ $job ] ) ) {
					$this->log( sprintf( 'Upgrade job %s was scheduled after waiting for the store to be ready to sync.', $job ) );
				}
				unset( $updated[ $job ] );
			} elseif ( $setup_complete ) {
				if ( ! isset( $updated[ $job ] ) ) {
					$this->log( sprintf( 'Upgrade job %s could not be scheduled yet (the store is not ready to sync, or the job is already running). It will be retried from admin pages, at most once an hour.', $job ) );
				}
				$updated[ $job ] = $now;
			}
		}

		if ( ! $setup_complete && ! empty( $updated ) ) {
			$this->log( sprintf( 'Discarded pending upgrade jobs because Merchant Center setup is not complete: %s.', implode( ', ', array_keys( $updated ) ) ) );
			$updated = [];
		}

		$this->save_pending_jobs( $updated );
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
	 * @return bool Whether the job was scheduled. A job that isn't an Action Scheduler job
	 *              can't report this, so it is scheduled and treated as scheduled.
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
	 * @return int[] Time of the last attempt to schedule each job, keyed by job class.
	 */
	protected function get_pending_jobs(): array {
		$value = $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS );
		if ( ! is_array( $value ) ) {
			return [];
		}

		$known = array_merge( ...array_values( $this->updates ) );
		$jobs  = [];
		foreach ( $value as $job => $attempted_at ) {
			if ( in_array( $job, $known, true ) ) {
				$jobs[ $job ] = (int) $attempted_at;
			}
		}

		return $jobs;
	}

	/**
	 * Store the update jobs still waiting to be scheduled.
	 *
	 * The option is kept even when empty, so it stays autoloaded and the check in
	 * retry_pending_jobs() doesn't query the database for a missing option. It is only written
	 * when it changes: Options::update() fires an options-updated hook on every call, and this
	 * runs from admin requests while a job is pending.
	 *
	 * @since x.x.x
	 *
	 * @param int[] $jobs Time of the last attempt to schedule each job, keyed by job class.
	 */
	protected function save_pending_jobs( array $jobs ): void {
		if ( $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ) !== $jobs ) {
			$this->options->update( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $jobs );
		}
	}

	/**
	 * Log a message about the upgrade jobs.
	 *
	 * @param string $message
	 */
	protected function log( string $message ): void {
		do_action( 'woocommerce_gla_debug_message', $message, __CLASS__ );
	}
}
