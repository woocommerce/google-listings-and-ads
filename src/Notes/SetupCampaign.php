<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class SetupCampaign extends AbstractNote implements AdsAwareInterface {

	use AdsAwareTrait;
	use PluginHelper;
	use Utilities;

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
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'setup-campaign',
			__( 'Get started', 'google-listings-and-ads' ),
			$this->get_setup_ads_url()
		);

		return $note;
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * Check if ads setup IS NOT complete
	 * Check if it is > 3 days ago from DATE OF SETUP COMPLETION
	 * Send notification
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( $this->has_been_added() ) {
			return false;
		}

		if ( $this->ads_service->is_setup_complete() ) {
			return false;
		}

		if ( ! $this->gla_setup_for( 3 * DAY_IN_SECONDS ) ) {
			return false;
		}

		return true;
	}

}
