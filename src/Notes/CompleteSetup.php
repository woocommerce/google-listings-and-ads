<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Psr\Container\ContainerInterface;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class CompleteSetup implements Deactivateable, Service, Registerable {

	use PluginHelper;

	const NOTE_NAME = 'gla-complete-setup-202102';

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * CompleteSetup constructor.
	 *
	 * @param ContainerInterface $container The container object.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->options = $container->get( OptionsInterface::class );
	}

	/**
	 * Register a service.
	 */
	public function register(): void {

		add_action(
			'admin_init',
			function() {
				$this->possibly_add_note();
			}
		);
	}

	/**
	 * Add the note
	 *
	 * @return void
	 */
	public function possibly_add_note(): void {

		if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\Note' ) ) {
			return;
		}

		if ( ! class_exists( '\WC_Data_Store' ) ) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );

		// First, see if we've already created this kind of note so we don't do it again.
		$note_ids = $data_store->get_notes_with_name( self::NOTE_NAME );
		foreach( (array) $note_ids as $note_id ) {
			$note         = Notes::get_note( $note_id );
			$content_data = $note->get_content_data();
 			if ( property_exists( $content_data, 'getting_started' ) ) {
				return;
			}
		}

		// Otherwise, add the note
		$activated_time = current_time( 'timestamp', 0 );
		$activated_time_formatted = date( 'F jS', $activated_time );

		$note = new Note();
		$note->set_title( __( 'Complete Setup', 'wapi-example-plugin-one' ) );
		$note->set_content(
			sprintf(
				/* translators: a date, e.g. November 1st */
				__( 'Plugin activated on %s.', 'wapi-example-plugin-one' ),
				$activated_time_formatted
			)
		);
		$note->set_content_data( (object) array(
			'getting_started'     => true,
			'activated'           => $activated_time,
			'activated_formatted' => $activated_time_formatted,
		) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout('plain');
		$note->set_image('');
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'wapi-example-plugin-one' );
		$note->set_layout('plain');
		$note->set_image('');
		// This example has two actions. A note can have 0 or 1 as well.
		$note->add_action(
			'settings',
			__( 'Open Settings', 'wapi-example-plugin-one' ),
			'?page=wc-settings&tab=general'
		);
		$note->add_action(
			'settings',
			__( 'Learn More', 'wapi-example-plugin-one' ),
			'https://github.com/woocommerce/woocommerce-admin/tree/main/docs'
		);
		$note->save();

	}

	/**
	 * Deactivate the service.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\Notes' ) ) {
			return;
		}

		Notes::delete_notes_with_name( self::NOTE_NAME );
	}
}
