<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupCouponSharing
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
class SetupCouponSharing extends AbstractNote implements MerchantCenterAwareInterface, AdsAwareInterface {

	use AdsAwareTrait;
	use MerchantCenterAwareTrait;
	use PluginHelper;
	use Utilities;

	/** @var MerchantStatuses */
	protected $merchant_statuses;

	/**
	 * SetupCouponSharing constructor.
	 *
	 * @param MerchantStatuses $merchant_statuses
	 */
	public function __construct( MerchantStatuses $merchant_statuses ) {
		$this->merchant_statuses = $merchant_statuses;
	}

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-coupon-optin';
	}

	/**
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();

		$note->set_title( __( 'Show your store coupons on your Google listings', 'google-listings-and-ads' ) );
		$note->set_content(
			__(
				'Sync your store promotions and coupons directly with Google to showcase on your product listings across the Google Shopping tab. <br/><br/>When creating a coupon, you’ll see a Channel Visibility settings box on the right; select "Show coupon on Google" to enable.',
				'google-listings-and-ads'
			)
		);
		$note->set_content_data( (object) [] );
		$note->set_type( NoteEntry::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'coupon-views',
			__( 'Go to coupons', 'google-listings-and-ads' ),
			$this->get_coupons_url()
		);

		return $note;
	}


	/**
	 * Checks if a note can and should be added. We insert the notes only when all the conditions are satisfied:
	 *     1. Store is in promotion supported country
	 *     2. Store has at least one active product in Merchant Center
	 *     3. Store has coupons created, but no coupons synced with Merchant Center
	 *     4. Store has Ads account connected and has been setup for >3 days OR no Ads account and >17 days
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		if ( $this->has_been_added() ) {
			return false;
		}

		if ( ! $this->merchant_center->is_promotion_supported_country() ) {
			return false;
		}

		// Check if there are synced products.
		try {
			$statuses = $this->merchant_statuses->get_product_statistics();
			if ( $statuses['statistics']['active'] <= 0 ) {
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}

		// Check if merchants have created coupons.
		$coupons        = get_posts( [ 'post_type' => 'shop_coupon' ] );
		$shared_coupons = get_posts(
			[
				'post_type'  => 'shop_coupon',
				'meta_key'   => CouponMetaHandler::KEY_VISIBILITY,
				'meta_value' => ChannelVisibility::SYNC_AND_SHOW,
			]
		);
		if ( empty( $coupons ) || ! empty( $shared_coupons ) ) {
			return false;
		}

		if ( $this->ads_service->is_setup_complete() ) {
			if ( ! $this->gla_setup_for( 3 * DAY_IN_SECONDS ) ) {
				return false;
			}
		} elseif ( ! $this->gla_setup_for( 17 * DAY_IN_SECONDS ) ) {
			return false;
		}

		return true;
	}
}
