<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\BatchedActionSchedulerJobInterface;
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
		'3.11.0' => [
			// Delete conversion report CSVs left in the web-reachable export folder by earlier versions.
			DeleteYouTubeConversionReports::class,
			// Products in exchange-rate secondary markets were marked invalid with a null
			// price and are excluded from the expiring-products resync; re-sync them once.
			UpdateAllProducts::class,
		],
	];

	/**
	 * Register a service.
	 *
	 * The retry runs after the installer's admin_init callback (default priority), so an
	 * upgrade's own attempt always comes first in the same request.
	 *
	 * @since 3.11.0
	 */
	public function register(): void {
		add_action(
			'admin_init',
			function () {
				$this->retry_pending_jobs();
			},
			20
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
	 * @since 3.11.0
	 */
	public function retry_pending_jobs(): void {
		if ( defined( 'IFRAME_REQUEST' ) || wp_doing_ajax() ) {
			return;
		}

		$stored = $this->options->get( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS );

		// Deleted, e.g. by a Merchant Center disconnect: create it again, empty, so later
		// requests read it from the autoloaded options instead of querying for a missing row.
		if ( null === $stored ) {
			$this->options->update( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, [] );
			return;
		}

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
	 * can't be scheduled is kept pending with the time of the attempt, to be retried later.
	 *
	 * Nothing is kept when Merchant Center setup isn't complete. A full disconnect already
	 * cleared what was synced (and the pending jobs, see AccountService::disconnect()), and
	 * completing setup again queues a full sync. Like any sync, that one only runs once the
	 * store is ready to sync.
	 *
	 * @param array $jobs List of jobs
	 */
	protected function schedule_jobs( array $jobs ): void {
		$jobs           = array_values( array_unique( $jobs ) );
		$setup_complete = $this->merchant_center->is_setup_complete();
		$pending        = $this->stamp_pending_jobs( $jobs );

		foreach ( $jobs as $job ) {
			try {
				$scheduled = $this->schedule_job( $job );
			} catch ( JobException $e ) {
				// The job doesn't exist (anymore), so retrying it can't succeed.
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
				unset( $pending[ $job ] );
				continue;
			}

			$pending = $scheduled
				? $this->mark_scheduled( $job, $pending )
				: $this->mark_deferred( $job, $pending, $setup_complete );
		}

		if ( ! $setup_complete ) {
			$pending = $this->discard_pending_jobs( $pending );
		}

		$this->save_pending_jobs( $pending );
	}

	/**
	 * Stamp the pending jobs about to be attempted with the time of this attempt, and save them
	 * before attempting anything, so a concurrent admin request sees them as not due.
	 *
	 * @since 3.11.0
	 *
	 * @param string[] $jobs Job class names about to be attempted.
	 *
	 * @return int[] The pending jobs, with the stamped ones updated.
	 */
	protected function stamp_pending_jobs( array $jobs ): array {
		$pending = $this->get_pending_jobs();
		foreach ( array_intersect( $jobs, array_keys( $pending ) ) as $job ) {
			$pending[ $job ] = time();
		}

		$this->save_pending_jobs( $pending );

		return $pending;
	}

	/**
	 * Remove a job that was scheduled from the pending jobs.
	 *
	 * @since 3.11.0
	 *
	 * @param string $job     Job class name.
	 * @param int[]  $pending Pending jobs.
	 *
	 * @return int[] The pending jobs without this one.
	 */
	protected function mark_scheduled( string $job, array $pending ): array {
		if ( isset( $pending[ $job ] ) ) {
			$this->log( sprintf( 'Upgrade job %s was scheduled after waiting for the store to be ready to sync.', $job ), __METHOD__ );
			unset( $pending[ $job ] );
		}

		return $pending;
	}

	/**
	 * Keep a job that couldn't be scheduled yet as pending, stamped with this attempt.
	 *
	 * @since 3.11.0
	 *
	 * @param string $job            Job class name.
	 * @param int[]  $pending        Pending jobs.
	 * @param bool   $setup_complete Whether Merchant Center setup is complete.
	 *
	 * @return int[] The pending jobs, including this one when setup is complete.
	 */
	protected function mark_deferred( string $job, array $pending, bool $setup_complete ): array {
		if ( ! $setup_complete ) {
			return $pending;
		}

		if ( ! isset( $pending[ $job ] ) ) {
			$this->log( sprintf( 'Upgrade job %s could not be scheduled yet (the store is not ready to sync, or the job is already running). It will be retried from admin pages, at most once an hour.', $job ), __METHOD__ );
		}
		$pending[ $job ] = time();

		return $pending;
	}

	/**
	 * Discard the pending jobs, for a store whose Merchant Center setup isn't complete.
	 *
	 * @since 3.11.0
	 *
	 * @param int[] $pending Pending jobs.
	 *
	 * @return int[] No pending jobs.
	 */
	protected function discard_pending_jobs( array $pending ): array {
		if ( ! empty( $pending ) ) {
			$this->log( sprintf( 'Discarded pending upgrade jobs because Merchant Center setup is not complete: %s.', implode( ', ', array_keys( $pending ) ) ), __METHOD__ );
		}

		return [];
	}

	/**
	 * Schedule a single job if it can be scheduled now.
	 *
	 * `schedule()` silently does nothing when the job can't be scheduled, so ask the job first
	 * through `can_schedule()`. The `null` args match the job running with any arguments.
	 *
	 * @since 3.11.0
	 *
	 * @param string $job Job class name.
	 *
	 * @return bool Whether the update's work is now queued: the job was scheduled, or a run that
	 *              will process everything with the current code is already queued. A job that
	 *              isn't an Action Scheduler job can't say whether it can be scheduled, so it is
	 *              not scheduled here, and is reported as done so it isn't kept pending forever.
	 *
	 * @throws JobException If the job doesn't exist.
	 */
	protected function schedule_job( string $job ): bool {
		$job = $this->job_repository->get( $job );

		if ( ! $job instanceof ActionSchedulerJobInterface ) {
			return true;
		}

		if ( $job->can_schedule( null ) ) {
			$job->schedule();
			return true;
		}

		return $this->is_queued_from_first_batch( $job );
	}

	/**
	 * Whether a run of the job is queued that hasn't processed any batch yet.
	 *
	 * Such a run, e.g. one queued by a concurrent request running the same upgrade, processes
	 * every item with the current code, so it does the update's work. A run already past its
	 * first batch may have started before the upgrade, so the job stays pending and runs in
	 * full once that run finishes.
	 *
	 * @since 3.11.0
	 *
	 * @param ActionSchedulerJobInterface $job The job.
	 *
	 * @return bool
	 */
	protected function is_queued_from_first_batch( ActionSchedulerJobInterface $job ): bool {
		// Batched jobs start every run at batch 1 (see AbstractBatchedActionSchedulerJob::schedule()).
		if ( ! $job instanceof BatchedActionSchedulerJobInterface || ! $this->merchant_center->is_ready_for_syncing() ) {
			return false;
		}

		// The store is ready, so the job can't be scheduled only because a run is queued: check
		// whether that run is still at its first batch.
		return ! $job->can_schedule( [ 1 ] );
	}

	/**
	 * Get the update jobs still waiting to be scheduled.
	 *
	 * Only job classes listed in the updates are returned, so a stale or unexpected
	 * value in the option is never passed on to the job repository.
	 *
	 * @since 3.11.0
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
	 * @since 3.11.0
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
	 * @param string $method  The method logging the message.
	 */
	protected function log( string $message, string $method ): void {
		do_action( 'woocommerce_gla_debug_message', $message, $method );
	}
}
