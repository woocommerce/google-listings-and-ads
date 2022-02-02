<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Exception;
use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Class AbstractSetupCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 * @since 1.11.0
 */
abstract class AbstractSetupCampaign extends AbstractNote implements AdsAwareInterface {

	use AdsAwareTrait;
	use PluginHelper;
	use Utilities;

	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * AbstractSetupCampaign constructor.
	 *
	 * @param MerchantStatuses $merchant_statuses
	 */
	public function __construct( MerchantStatuses $merchant_statuses ) {
		$this->merchant_statuses = $merchant_statuses;
	}

	/**
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();
		$this->set_title_and_content( $note );
		$this->add_common_note_settings( $note );

		return $note;
	}

	/**
	 * @param NoteEntry $note
	 *
	 * @return void
	 */
	protected function add_common_note_settings( NoteEntry $note ): void {
		$note->set_content_data( new stdClass() );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * Check if ads setup IS NOT complete
	 * Check if it is > $this->get_gla_setup_days() days ago from DATE OF SETUP COMPLETION
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

		if ( ! $this->gla_setup_for( $this->get_gla_setup_days() * DAY_IN_SECONDS ) ) {
			return false;
		}

		// We don't need to process exceptions here, as we're just determining whether to add a note.
		try {
			if ( $this->has_account_issues() ) {
				return false;
			}

			if ( ! $this->has_at_least_one_synced_product() ) {
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether there are any account-level issues.
	 *
	 * @since 1.11.0
	 * @return bool
	 */
	protected function has_account_issues(): bool {
		$issues = $this->merchant_statuses->get_issues( MerchantStatuses::TYPE_ACCOUNT );

		return isset( $issues['issues'] ) && count( $issues['issues'] ) >= 1;
	}

	/**
	 * Determine whether there is at least one synced product.
	 *
	 * @since 1.11.0
	 * @return bool
	 */
	protected function has_at_least_one_synced_product(): bool {
		$statuses = $this->merchant_statuses->get_product_statistics();

		return $statuses['statistics']['active'] >= 1;
	}

	/**
	 * Get the number of days after which to add the note.
	 *
	 * @since 1.11.0
	 *
	 * @return int
	 */
	abstract protected function get_gla_setup_days(): int;

	/**
	 * Set the title and content of the Note.
	 *
	 * @since 1.11.0
	 *
	 * @param NoteEntry $note
	 *
	 * @return void
	 */
	abstract protected function set_title_and_content( NoteEntry $note ): void;
}
