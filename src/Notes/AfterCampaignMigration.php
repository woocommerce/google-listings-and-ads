<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AfterCampaignMigration
 *
 * Shows an inbox notification related to the campaign migration after the migration starts
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 *
 * @since 2.0.3
 */
class AfterCampaignMigration extends AbstractNote implements OptionsAwareInterface, AdsAwareInterface {

	use AdsAwareTrait;
	use PluginHelper;
	use OptionsAwareTrait;

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-after-campaign-migration';
	}

	/**
	 * Get the note entry.
	 *
	 * @return NoteEntry
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();

		$note->set_title( __( 'Your Google Listings & Ads campaigns have been automatically upgraded', 'google-listings-and-ads' ) );
		$note->set_content(
			__(
				'Google has auto-upgraded your existing campaigns from Smart Shopping to Performance Max, giving you the same benefits plus extended reach across the Google network. No changes were made to your campaign settings and metrics from previous campaigns will continue to be available in Reports for historical purposes.',
				'google-listings-and-ads'
			)
		);

		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		// TODO update learn more link once it is confirmed
		$note->add_action(
			'read-more-upgrade-campaign',
			__( 'Read more about this upgrade', 'google-listings-and-ads' ),
			'https://support.google.com/google-ads/answer/11576060'
		);

		return $note;
	}


	/**
	 * Checks if a note should be added and the campaign migration is completed
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		return ! $this->has_been_added() && $this->ads_service->is_migration_completed();
	}
}
