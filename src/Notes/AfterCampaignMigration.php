<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class AfterCampaignMigration
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class AfterCampaignMigration extends AbstractNote implements AdsAwareInterface {

	use AdsAwareTrait;
	use PluginHelper;
	use Utilities;

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
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		// TODO update learn more link once it is confirmed
		$note->add_action(
			'read-more-upgrade-campaign',
			__( 'Read more about this upgrade', 'google-listings-and-ads' ),
			'#'
		);

		return $note;
	}


	/**
	 * Checks if a note can and should be added.
	 *
	 * - checks if the campaign migration is completed
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( $this->has_been_added() ) {
			return false;
		}

		return $this->ads_service->is_migration_completed();

	}
}
