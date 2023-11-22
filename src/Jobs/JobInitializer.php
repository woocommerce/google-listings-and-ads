<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobInitializer
 *
 * Initializes all jobs when certain conditions are met (e.g. the request is async or initiated by CRON, CLI, etc.).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class JobInitializer implements Registerable, Conditional {

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * JobInitializer constructor.
	 *
	 * @param JobRepository            $job_repository
	 * @param ActionSchedulerInterface $action_scheduler
	 */
	public function __construct( JobRepository $job_repository, ActionSchedulerInterface $action_scheduler ) {
		$this->job_repository   = $job_repository;
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Initialize all jobs.
	 */
	public function register(): void {
		foreach ( $this->job_repository->list() as $job ) {
			$job->init();

			if ( $job instanceof StartOnHookInterface ) {
				add_action(
					$job->get_start_hook()->get_hook(),
					function ( ...$args ) use ( $job ) {
						$job->schedule( $args );
					},
					10,
					$job->get_start_hook()->get_argument_count()
				);
			}

			if (
				$job instanceof RecurringJobInterface &&
				! $this->action_scheduler->has_scheduled_action( $job->get_start_hook()->get_hook() ) &&
				$job->can_schedule()
			) {

				$recurring_date_time = new DateTime( 'tomorrow 3am', wp_timezone() );
				$schedule            = '0 3 * * *'; // 3 am every day
				$this->action_scheduler->schedule_cron( $recurring_date_time->getTimestamp(), $schedule, $job->get_start_hook()->get_hook() );
			}
		}
	}

	/**
	 * Check whether this object is currently needed.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) || ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() );
	}
}
