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
	 * Get the number of days after which to add the note.
	 *
	 * @since 1.11.0
	 *
	 * @return int
	 */
	protected function get_gla_setup_days(): int {
		return 14;
	}

	/**
	 * Set the title and content of the Note.
	 *
	 * @since 1.11.0
	 *
	 * @param NoteEntry $note
	 *
	 * @return void
	 */
	protected function set_title_and_content( NoteEntry $note ): void {
		if ( ! $this->ads_service->is_setup_started() ) {
			$note->set_title( __( 'Reach more shoppers with paid listings on Google', 'google-listings-and-ads' ) );
			$note->set_content(
				__(
					'Your products are ready for Google Ads! Connect with the right shoppers at the right moment when they’re searching for products like yours. Connect your Google Ads account to create your first paid ad campaign.',
					'google-listings-and-ads'
				)
			);
			$note->add_action(
				'setup-campaign',
				__( 'Set up Google Ads', 'google-listings-and-ads' ),
				$this->get_setup_ads_url(),
				NoteEntry::E_WC_ADMIN_NOTE_ACTIONED,
				true
			);
		} else {
			$note->set_title(
				__( 'Finish setting up your ads campaign and boost your sales', 'google-listings-and-ads' )
			);
			$note->set_content(
				__(
					"You're just a few steps away from reaching new shoppers across Google. Finish connecting your account, create your campaign, pick your budget, and easily measure the impact of your ads.",
					'google-listings-and-ads'
				)
			);
			$note->add_action(
				'setup-campaign',
				__( 'Complete Setup', 'google-listings-and-ads' ),
				$this->get_setup_ads_url(),
				NoteEntry::E_WC_ADMIN_NOTE_ACTIONED,
				true
			);
		}
	}
}
