<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\RunDaily;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cron
 *
 * Responsible for managing the plugin's cron jobs and for running services that implement cron interfaces e.g. RunDaily.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal
 *
 * @since x.x.x
 */
class Cron implements ContainerAwareInterface, InstallableInterface, Registerable, Service {

	use ContainerAwareTrait;

	/**
	 * Hook name for daily cron.
	 */
	protected const DAILY = 'wc_gla_cron_daily';

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * Cron constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler ) {
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Register cron services.
	 */
	public function register(): void {
		add_action(
			self::DAILY,
			function() {
				$services = $this->container->get( RunDaily::class );
				foreach ( $services as $service ) {
					$service->run();
				}
			}
		);
	}

	/**
	 * Ensure cron jobs are created when plugin is first installed or is updated.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		if ( ! $this->action_scheduler->has_scheduled_action( self::DAILY ) ) {
			$this->action_scheduler->schedule_recurring( time(), DAY_IN_SECONDS, self::DAILY );
		}
	}

}
