<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerException;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Activateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * NoteInitializer class.
 *
 * @since 1.7.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class NoteInitializer implements Activateable, ContainerAwareInterface, Deactivateable, InstallableInterface, Service, Registerable {

	use ContainerAwareTrait;
	use ValidateInterface;

	/**
	 * Hook name for daily cron.
	 */
	protected const CRON_HOOK = 'wc_gla_cron_daily_notes';

	/**
	 * Array of notes to initialize.
	 *
	 * @var Note[]
	 */
	protected $notes;

	/**
	 * Register the service.
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, [ $this, 'add_notes' ] );
	}

	/**
	 * Loop through all notes to add any that should be added.
	 */
	public function add_notes(): void {
		foreach ( $this->get_notes() as $note ) {
			try {
				if ( $note->should_be_added() ) {
					$note->get_entry()->save();
				}
			} catch ( Exception $e ) {
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			}
		}
	}

	/**
	 * Activate the service.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->maybe_add_cron_job();
	}

	/**
	 * Run's when plugin is installed or updated.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		$this->maybe_add_cron_job();
	}

	/**
	 * Add notes cron job if it doesn't already exist.
	 */
	protected function maybe_add_cron_job(): void {
		try {
			$action_scheduler = $this->get_action_scheduler();
			if ( ! $action_scheduler->has_scheduled_action( self::CRON_HOOK ) ) {
				$action_scheduler->schedule_recurring( time(), DAY_IN_SECONDS, self::CRON_HOOK );
			}
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}
	}

	/**
	 * Deactivate the service.
	 *
	 * Delete the notes cron job and all notes.
	 */
	public function deactivate(): void {
		try {
			$this->get_action_scheduler()->cancel( self::CRON_HOOK );
		} catch ( ActionSchedulerException $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}

		// Ensure all note names are deleted
		if ( class_exists( Notes::class ) ) {
			$note_names = [];
			foreach ( $this->get_notes() as $note ) {
				$note_names[] = $note->get_name();
			}

			Notes::delete_notes_with_name( $note_names );
		}
	}

	/**
	 * Get all Note objects.
	 *
	 * @since x.x.x
	 * @return Note[]
	 */
	protected function get_notes(): array {
		try {
			return $this->container->get( Note::class );
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			return [];
		}
	}

	/**
	 * Get the ActionSchedulerInterface object from the container.
	 *
	 * @since x.x.x
	 * @return ActionSchedulerInterface
	 */
	protected function get_action_scheduler(): ActionSchedulerInterface {
		return $this->container->get( ActionSchedulerInterface::class );
	}
}
