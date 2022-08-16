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
 * Class BeforeCampaignMigration
 *
 * Shows an inbox notification related to the campaign migration before the migration starts
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 *
 * @since 2.0.3
 */
class BeforeCampaignMigration extends AbstractNote implements OptionsAwareInterface, AdsAwareInterface {

	use AdsAwareTrait;
	use PluginHelper;
	use OptionsAwareTrait;

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-before-campaign-migration';
	}

	/**
	 * Get the note entry.
	 *
	 * @return NoteEntry
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();

		$note->set_title( __( 'Your Google Listings & Ads campaigns will soon be automatically upgraded', 'google-listings-and-ads' ) );
		$note->set_content(
			__(
				'From July through September, Google will be upgrading your existing campaigns from Smart Shopping to Performance Max, giving you the same benefits, plus expanded reach. There will be no impact to your spend or campaign settings due to this upgrade.',
				'google-listings-and-ads'
			)
		);

		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		// TODO update learn more link once it is confirmed
		$note->add_action(
			'learn-more-upgrade-campaign',
			__( 'Learn more about this upgrade', 'google-listings-and-ads' ),
			'https://support.google.com/google-ads/answer/11576060'
		);

		return $note;
	}


	/**
	 * Checks if a note should be added and the campaign migration is not completed
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		return ! $this->has_been_added() && ! $this->ads_service->is_migration_completed();

	}
}
