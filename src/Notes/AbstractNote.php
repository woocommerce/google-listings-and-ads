<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractNote class.
 *
 * @since 1.7.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
abstract class AbstractNote implements Note, OptionsAwareInterface {

	/**
	 * Remove the note from the datastore.
	 *
	 * @since 1.12.5
	 */
	public function delete() {
		if ( class_exists( Notes::class ) ) {
			Notes::delete_notes_with_name( $this->get_name() );
		}
	}

	/**
	 * Get note data store.
	 *
	 * @see \Automattic\WooCommerce\Admin\Notes\DataStore for relavent data store.
	 *
	 * @return WC_Data_Store
	 */
	protected function get_data_store(): WC_Data_Store {
		return WC_Data_Store::load( 'admin-note' );
	}

	/**
	 * Check if the note has already been added.
	 *
	 * @return bool
	 */
	protected function has_been_added(): bool {
		$note_ids = $this->get_data_store()->get_notes_with_name( $this->get_name() );

		return ! empty( $note_ids );
	}
}
