<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReconnectWordPress
 *
 * @since 1.12.5
 *
 * Note for prompting to reconnect the WordPress.com account.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class ReconnectWordPress extends AbstractNote implements MerchantCenterAwareInterface {

	use PluginHelper;
	use Utilities;
	use MerchantCenterAwareTrait;

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * ReconnectWordPress constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-reconnect-wordpress';
	}

	/**
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();
		$note->set_title(
			__( 'Re-connect your store to Google for WooCommerce', 'google-listings-and-ads' )
		);
		$note->set_content(
			__( 'Your WordPress.com account has been disconnected from Google for WooCommerce. Connect your WordPress.com account again to ensure your products stay listed on Google through the Google for WooCommerce extension.<br/><br/>If you do not re-connect, any existing listings may be removed from Google.', 'google-listings-and-ads' )
		);
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'reconnect-wordpress',
			__( 'Go to Google for WooCommerce', 'google-listings-and-ads' ),
			add_query_arg( 'subpath', '/reconnect-wpcom-account', $this->get_settings_url() )
		);

		return $note;
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * - Triggers a status check if not already disconnected.
	 * - Checks if Jetpack is disconnected.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( $this->has_been_added() || ! $this->merchant_center->is_setup_complete() ) {
			return false;
		}

		$this->maybe_check_status();

		return ! $this->is_jetpack_connected();
	}

	/**
	 * Trigger a status check if we are not already disconnected.
	 * A request to the server must be sent to detect a disconnect.
	 */
	protected function maybe_check_status() {
		if ( ! $this->is_jetpack_connected() ) {
			return;
		}

		try {
			$this->connection->get_status();
		} catch ( Exception $e ) {
			return;
		}
	}
}
