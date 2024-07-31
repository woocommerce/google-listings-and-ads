<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReviewAfterConversions
 *
 * Note for requesting a review after one ad conversion.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class ReviewAfterConversions extends AbstractNote implements AdsAwareInterface {

	use AdsAwareTrait;
	use LeaveReviewActionTrait;
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
	 * ReviewAfterConversions constructor.
	 *
	 * @param MerchantMetrics $merchant_metrics
	 * @param WP              $wp
	 */
	public function __construct( MerchantMetrics $merchant_metrics, WP $wp ) {
		$this->merchant_metrics = $merchant_metrics;
		$this->wp               = $wp;
	}

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-review-after-conversions';
	}

	/**
	 * Get ads conversions count.
	 *
	 * @return int
	 *
	 * @throws Exception When unable to get data.
	 */
	protected function get_ads_conversions_count(): int {
		$metrics = $this->merchant_metrics->get_cached_ads_metrics();

		return empty( $metrics ) ? 0 : (int) $metrics['conversions'];
	}

	/**
	 * Get the note entry.
	 *
	 * @throws Exception When unable to get data.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();
		$note->set_title( __( 'You got your first conversion on Google Ads! 🎉', 'google-listings-and-ads' ), );
		$note->set_content(
			__( 'Congratulations! Tell us what you think about Google for WooCommerce by leaving a review. Your feedback will help us make WooCommerce even better for you.', 'google-listings-and-ads' )
		);
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		$this->add_leave_review_note_action( $note );

		return $note;
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * - checks there is at least one ad conversion
	 *
	 * @throws Exception When unable to get data.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( $this->has_been_added() ) {
			return false;
		}

		if ( ! $this->ads_service->is_connected() ) {
			return false;
		}

		if ( $this->get_ads_conversions_count() < 1 ) {
			return false;
		}

		return true;
	}
}
