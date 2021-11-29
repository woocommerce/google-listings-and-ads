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
class NoteInitializer implements Activateable, Deactivateable, InstallableInterface, Service, Registerable {

	use ValidateInterface;

	/**
	 * Hook name for daily cron.
	 */
	protected const CRON_HOOK = 'wc_gla_cron_daily_notes';

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * Array of notes to initialize.
	 *
	 * @var Note[]
	 */
	protected $notes;

	/**
	 * Cron constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 * @param Note[]                   $notes
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, array $notes ) {
		foreach ( $notes as $note ) {
			$this->validate_instanceof( $note, Note::class );
		}

		$this->action_scheduler = $action_scheduler;
		$this->notes            = $notes;
	}

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
		foreach ( $this->notes as $note ) {
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
		if ( ! $this->action_scheduler->has_scheduled_action( self::CRON_HOOK ) ) {
			$this->action_scheduler->schedule_recurring( time(), DAY_IN_SECONDS, self::CRON_HOOK );
		}
	}

	/**
	 * Deactivate the service.
	 *
	 * Delete the notes cron job and all notes.
	 */
	public function deactivate(): void {
		try {
			$this->action_scheduler->cancel( self::CRON_HOOK );
		} catch ( ActionSchedulerException $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}

		// Ensure all note names are deleted
		if ( class_exists( Notes::class ) ) {
			$note_names = [];
			foreach ( $this->notes as $note ) {
				$note_names[] = $note->get_name();
			}

			Notes::delete_notes_with_name( $note_names );
		}
	}

}
