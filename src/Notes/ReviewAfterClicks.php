<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReviewAfterClicks
 *
 * Note for requesting a review after at 100+ clicks.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class ReviewAfterClicks extends Note implements MerchantCenterAwareInterface {

	use LeaveReviewActionTrait;
	use MerchantCenterAwareTrait;
	use PluginHelper;
	use Utilities;

	/**
	 * @var MerchantMetrics
	 */
	protected $merchant_metrics;

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * ReviewAfterClicks constructor.
	 *
	 * @param MerchantMetrics $merchant_metrics
	 * @param WP $wp
	 */
	public function __construct( MerchantMetrics $merchant_metrics, WP $wp ) {
		$this->merchant_metrics = $merchant_metrics;
		$this->wp = $wp;
	}

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_note_name(): string {
		return 'gla-review-after-clicks';
	}

	/**
	 * Possibly add the note.
	 */
	public function possibly_add_note(): void {
		if ( ! $this->can_add_note() ) {
			return;
		}

		// Check "click logic" here to avoid multiple calls to count clicks. Currently not cached.
		$clicks_count = $this->merchant_metrics->get_free_listing_clicks();
		if ( $clicks_count <= 100 ) {
			return;
		}

		// Round to nearest 100
		$clicks_count_rounded = floor( $clicks_count / 100 ) * 100;

		$note = new NoteEntry();
		$note->set_title(
			sprintf(
				/* translators: %s number of clicks */
				__( 'You’ve gotten %s+ clicks on your free listings! 🎉', 'google-listings-and-ads' ),
				$this->wp->number_format_i18n( $clicks_count_rounded )
			)
		);
		$note->set_content(
			__( 'Congratulations! Tell us what you think about Google Listings & Ads by leaving a review. Your feedback will help us make WooCommerce even better for you.', 'google-listings-and-ads' )
		);
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_note_name() );
		$note->set_source( $this->get_slug() );
		$this->add_leave_review_note_action( $note );
		$note->save();
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * - checks that the plugin is setup
	 *
	 * @return bool
	 */
	public function can_add_note(): bool {
		if ( $this->has_been_added() ) {
			return false;
		}

		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		return true;
	}
}
