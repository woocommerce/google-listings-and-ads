<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WC_Coupon;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponChannelVisibilityMetaBox
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox
 */
class CouponChannelVisibilityMetaBox extends SubmittableMetaBox {

	/**
	 * @var CouponMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var CouponHelper
	 */
	protected $coupon_helper;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * CouponChannelVisibilityMetaBox constructor.
	 *
	 * @param Admin                 $admin
	 * @param CouponMetaHandler     $meta_handler
	 * @param CouponHelper          $coupon_helper
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( Admin $admin, CouponMetaHandler $meta_handler, CouponHelper $coupon_helper, MerchantCenterService $merchant_center ) {
		$this->meta_handler    = $meta_handler;
		$this->coupon_helper   = $coupon_helper;
		$this->merchant_center = $merchant_center;
		parent::__construct( $admin );
	}

	/**
	 * Meta box ID (used in the 'id' attribute for the meta box).
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'coupon_channel_visibility';
	}

	/**
	 * Title of the meta box.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Channel visibility', 'google-listings-and-ads' );
	}

	/**
	 * The screen on which to show the box (such as a post type, 'link', or 'comment').
	 *
	 * Default is the current screen.
	 *
	 * @return string
	 */
	public function get_screen(): string {
		return self::SCREEN_COUPON;
	}

	/**
	 * The context within the screen where the box should display. Available contexts vary from screen to
	 * screen. Post edit screen contexts include 'normal', 'side', and 'advanced'. Comments screen contexts
	 * include 'normal' and 'side'. Menus meta boxes (accordion sections) all use the 'side' context.
	 *
	 * Global default is 'advanced'.
	 *
	 * @return string
	 */
	public function get_context(): string {
		return self::CONTEXT_SIDE;
	}

	/**
	 * Returns an array of CSS classes to apply to the box.
	 *
	 * @return array
	 */
	public function get_classes(): array {
		$shown_types = array_map(
			function ( string $coupon_type ) {
				return "show_if_${coupon_type}";
			},
			CouponSyncer::get_supported_coupon_types()
		);

		$hidden_types = array_map(
			function ( string $coupon_type ) {
				return "hide_if_${coupon_type}";
			},
			CouponSyncer::get_hidden_coupon_types()
		);

		return array_merge( $shown_types, $hidden_types );
	}

	/**
	 * Returns an array of variables to be used in the view.
	 *
	 * @param WP_Post $post The WordPress post object the box is loaded for.
	 * @param array   $args Array of data passed to the callback. Defined by `get_callback_args`.
	 *
	 * @return array
	 */
	protected function get_view_context( WP_Post $post, array $args ): array {
		$coupon_id      = absint( $post->ID );
		$coupon         = $this->coupon_helper->get_wc_coupon( $coupon_id );
		$target_country = $this->merchant_center->get_main_target_country();

		return [
			'field_id'             => $this->get_visibility_field_id(),
			'coupon_id'            => $coupon_id,
			'coupon'               => $coupon,
			'channel_visibility'   => $this->coupon_helper->get_channel_visibility( $coupon ),
			'sync_status'          => $this->meta_handler->get_sync_status( $coupon ),
			'issues'               => $this->coupon_helper->get_validation_errors( $coupon ),
			'is_setup_complete'    => $this->merchant_center->is_setup_complete(),
			'is_channel_supported' => $this->merchant_center->is_promotion_supported_country( $target_country ),
			'get_started_url'      => $this->get_start_url(),
		];
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_coupon', [ $this, 'handle_submission' ], 10, 2 );
		add_action( 'woocommerce_update_coupon', [ $this, 'handle_submission' ], 10, 2 );
	}

	/**
	 * @param int       $coupon_id
	 * @param WC_Coupon $coupon
	 */
	public function handle_submission( int $coupon_id, WC_Coupon $coupon ) {
		/**
		 * Array of `true` values for each coupon IDs already handled by this method. Used to prevent double submission.
		 *
		 * @var bool[] $already_updated
		 */
		static $already_updated = [];

		$field_id = $this->get_visibility_field_id();
        // phpcs:disable WordPress.Security.NonceVerification
		// nonce is verified by self::verify_nonce
		if ( ! $this->verify_nonce() || ! isset( $_POST[ $field_id ] ) || isset( $already_updated[ $coupon_id ] ) ) {
			return;
		}

		// Only update the value for supported coupon types
		if ( ! CouponSyncer::is_coupon_supported( $coupon ) ) {
			return;
		}

		try {
			$visibility = empty( $_POST[ $field_id ] ) ?
			ChannelVisibility::cast( ChannelVisibility::DONT_SYNC_AND_SHOW ) :
			ChannelVisibility::cast( sanitize_key( $_POST[ $field_id ] ) );
            // phpcs:enable WordPress.Security.NonceVerification

			$this->meta_handler->update_visibility( $coupon, $visibility );

			$already_updated[ $coupon_id ] = true;
		} catch ( InvalidValue $exception ) {
			// silently log the exception and do not set the coupon's visibility if an invalid visibility value is sent.
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}

	/**
	 * @return string
	 *
	 * @since 1.1.0
	 */
	protected function get_visibility_field_id(): string {
		return $this->prefix_field_id( 'visibility' );
	}
}
