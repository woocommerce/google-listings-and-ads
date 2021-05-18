<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\DataStore;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WC_Data_Store;

/**
 * Class SetupCampaignTwoWeeks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class SetupCampaignTwoWeeks implements Deactivateable, Service, Registerable, OptionsAwareInterface, AdsAwareInterface {

	use AdsAwareTrait;
	use PluginHelper;
	use Utilities;

	public const NOTE_NAME = 'gla-setup-campaign-two-weeks';

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_init',
			function() {
				$this->possibly_add_note();
			}
		);
	}

	/**
	 * Possibly add the note
	 */
	public function possibly_add_note(): void {
		if ( ! $this->can_add_note() ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Launch your first ad in a few steps', 'google-listings-and-ads' ) );
		$note->set_content( __( 'You’re just a few steps away from reaching new shoppers across Google. Create your first paid ad campaign today.', 'google-listings-and-ads' ) );
		$note->set_content_data( (object) [] );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'setup-campaign',
			__( 'Get started', 'google-listings-and-ads' ),
			$this->get_setup_ads_url()
		);
		$note->save();
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * Check if ads setup IS NOT complete
	 * Check if it is > 14 days ago from DATE OF SETUP COMPLETION
	 * Send notification
	 *
	 * @return bool
	 */
	public function can_add_note(): bool {
		if ( ! class_exists( WC_Data_Store::class ) ) {
			return false;
		}

		/** @var DataStore $data_store */
		$data_store = WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		if ( ! empty( $note_ids ) ) {
			return false;
		}

		if ( $this->ads_service->is_setup_complete() ) {
			return false;
		}

		if ( ! $this->gla_setup_for( 14 * DAY_IN_SECONDS ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Deactivate the service.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( ! class_exists( Notes::class ) ) {
			return;
		}

		Notes::delete_notes_with_name( self::NOTE_NAME );
	}
}
