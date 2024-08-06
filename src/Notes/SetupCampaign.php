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
	 * Get the number of days after which to add the note.
	 *
	 * @since 1.11.0
	 *
	 * @return int
	 */
	protected function get_gla_setup_days(): int {
		return 3;
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
			$note->set_title( __( 'Launch ads to drive traffic and grow sales', 'google-listings-and-ads' ) );
			$note->set_content(
				__(
					'Your products are ready for Google Ads! Get your products shown on Google exactly when shoppers are searching for the products you offer. For new Google Ads accounts, get $500 in ad credit when you spend $500 within your first 60 days. T&Cs apply.',
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
			$note->set_title( __( 'Finish connecting your Google Ads account', 'google-listings-and-ads' ) );
			$note->set_content(
				__(
					'Your products are ready for Google Ads! Finish connecting your account, create your campaign, pick your budget, and easily measure the impact of your ads. Plus, Google will give you $500 USD in ad credit when you spend $500 for new accounts. T&Cs apply.',
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

		$note->add_action(
			'setup-campaign-learn-more',
			__( 'Learn more', 'google-listings-and-ads' ),
			'https://woocommerce.com/document/google-for-woocommerce/get-started/google-performance-max-campaigns'
		);
	}
}
