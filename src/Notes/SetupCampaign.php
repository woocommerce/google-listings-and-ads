<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\MerchantCenterTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Psr\Container\ContainerInterface;

/**
 * Class SetupCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class SetupCampaign implements Deactivateable, Service, Registerable, OptionsAwareInterface {

	use MerchantCenterTrait;
	use OptionsAwareTrait;
	use PluginHelper;
	use Utilities;

	public const NOTE_NAME = 'gla-setup-campaign-2021-02';

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
	 * Possibly add the note
	 *
	 * @return Note
	 */
	public function possibly_add_note(): void {
		if ( ! $this->can_add_note() ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Get more customers with easy advertising on Google', 'google-listings-and-ads' ) );
		$note->set_content( __( "People search for what they want. Smart Shopping campaigns will put your products right in front of shoppers looking for what you sell on Google. The best part? You won't pay unless you get results.", 'google-listings-and-ads' ) );
		$note->set_content_data( (object) [] );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'setup-campaign',
			__( 'Set up paid campaign', 'google-listings-and-ads' ),
			'www.to-do.com'
		);
		$note->save();
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * Check if a stores done 5 sales
	 * Check if setup IS complete
	 * Check if it is > 3 days ago from DATE OF SETUP COMPLETION
	 * Send notification
	 *
	 * @return Note
	 */
	public function can_add_note(): bool {
		if ( ! class_exists( '\WC_Data_Store' ) ) {
			return false;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		if ( ! empty( $note_ids ) ) {
			return false;
		}

		if ( ! $this->setup_complete() ) {
			return false;
		}

		if ( ! $this->gla_setup_for( 3 * DAY_IN_SECONDS ) ) {
			return false;
		}

		if ( ! $this->has_orders( 5 ) ) {
			return false;
		}

		return true;
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
