<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class ContactInformation
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 *
 * @since 1.4.0
 */
class ContactInformation extends Note implements MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use PluginHelper;
	use Utilities;

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_note_name(): string {
		return 'gla-contact-information';
	}

	/**
	 * Possibly add the note
	 */
	public function possibly_add_note(): void {
		if ( ! $this->can_add_note() ) {
			return;
		}

		$note = new NoteEntry();
		$note->set_title( __( 'Please add your contact information', 'google-listings-and-ads' ) );
		$note->set_content( __( 'Google requires the phone number and store address for all stores using Google Merchant Center. This is required to verify your store, and it will not be shown to customers. If you do not add your contact information, your listings may not appear on Google.', 'google-listings-and-ads' ) );
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_note_name() );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'contact-information',
			__( 'Add contact information', 'google-listings-and-ads' ),
			$this->get_settings_url()
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
		if ( $this->has_been_added() ) {
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

}
