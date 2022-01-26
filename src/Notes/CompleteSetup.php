<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class CompleteSetup extends AbstractNote implements MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use PluginHelper;
	use Utilities;

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-complete-setup';
	}

	/**
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();
		$note->set_title( __( 'Reach more shoppers with free listings on Google', 'google-listings-and-ads' ) );
		$note->set_content( __( 'Finish setting up Google Listings & Ads to list your products on Google for free and promote them with paid ads.', 'google-listings-and-ads' ) );
		$note->set_content_data( new stdClass() );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'complete-setup',
			__( 'Finish setup', 'google-listings-and-ads' ),
			$this->get_start_url()
		);

		return $note;
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * Check if setup IS NOT complete
	 * Check if a stores done 5 sales
	 * Send notification
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( $this->has_been_added() ) {
			return false;
		}

		if ( $this->merchant_center->is_setup_complete() ) {
			return false;
		}

		if ( ! $this->has_orders( 5 ) ) {
			return false;
		}

		return true;
	}
}
