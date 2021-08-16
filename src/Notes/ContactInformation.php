<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

/**
 * Class ContactInformation
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 *
 * @since 1.4.0
 */
class ContactInformation implements Deactivateable, Service, Registerable, OptionsAwareInterface, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;
	use PluginHelper;
	use Utilities;

	public const NOTE_NAME = 'gla-contact-information';

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
	 */
	public function possibly_add_note(): void {
		if ( ! $this->can_add_note() ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Please add your contact information', 'google-listings-and-ads' ) );
		$note->set_content( __( 'Google requires the phone number and store address for all stores using Google Merchant Center. This is required to verify your store, and it will not be shown to customers. If you do not add your contact information, your listings may not appear on Google.', 'google-listings-and-ads' ) );
		$note->set_content_data( (object) [] );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'contact-information',
			__( 'Add contact information', 'google-listings-and-ads' ),
			$this->get_contact_information_setup_url()
		);
		$note->save();
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * Checks if merchant center has been setup and contact information is valid.
	 * Send notification
	 *
	 * @return bool
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

		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		if ( $this->merchant_center->is_contact_information_setup() ) {
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
