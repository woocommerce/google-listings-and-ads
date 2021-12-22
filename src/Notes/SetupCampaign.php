<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class SetupCampaign extends AbstractSetupCampaign {

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-setup-campaign';
	}

	/**
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();
		$note->set_title( __( 'Create your first campaign to boost sales', 'google-listings-and-ads' ) );
		$note->set_content( __( 'Leverage the power of paid ads to list products on Google Search, Shopping, YouTube, Gmail and the Display Network and drive sales.', 'google-listings-and-ads' ) );
		$this->add_common_note_settings( $note );

		return $note;
	}

	/**
	 * Get the number of days after which to add the note.
	 *
	 * @return int
	 */
	protected function get_gla_setup_days(): int {
		return 3;
	}
}
