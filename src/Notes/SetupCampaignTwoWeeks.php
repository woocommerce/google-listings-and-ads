<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupCampaignTwoWeeks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class SetupCampaignTwoWeeks extends AbstractSetupCampaign {

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-setup-campaign-two-weeks';
	}

	/**
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();
		$note->set_title( __( 'Launch your first ad in a few steps', 'google-listings-and-ads' ) );
		$note->set_content( __( 'You’re just a few steps away from reaching new shoppers across Google. Create your first paid ad campaign today.', 'google-listings-and-ads' ) );
		$this->add_common_note_settings( $note );

		return $note;
	}

	/**
	 * Get the number of days after which to add the note.
	 *
	 * @return int
	 */
	protected function get_gla_setup_days(): int {
		return 14;
	}
}
