<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WC_Product;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class ChannelVisibilityMetaBox
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox
 */
class ChannelVisibilityMetaBox extends SubmittableMetaBox {

	use PluginHelper;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * ChannelVisibilityMetaBox constructor.
	 *
	 * @param Admin                 $admin
	 * @param ProductMetaHandler    $meta_handler
	 * @param ProductHelper         $product_helper
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( Admin $admin, ProductMetaHandler $meta_handler, ProductHelper $product_helper, MerchantCenterService $merchant_center ) {
		$this->meta_handler    = $meta_handler;
		$this->product_helper  = $product_helper;
		$this->merchant_center = $merchant_center;
		parent::__construct( $admin );
	}

	/**
	 * Meta box ID (used in the 'id' attribute for the meta box).
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'channel_visibility';
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
		return self::SCREEN_PRODUCT;
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
			function ( string $product_type ) {
				return "show_if_${product_type}";
			},
			ProductSyncer::get_supported_product_types()
		);

		$hidden_types = array_map(
			function ( string $product_type ) {
				return "hide_if_${product_type}";
			},
			ProductSyncer::get_hidden_product_types()
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
		$product_id = absint( $post->ID );
		$product    = $this->product_helper->get_wc_product( $product_id );

		return [
			'field_id'           => $this->get_visibility_field_id(),
			'product_id'         => $product_id,
			'product'            => $product,
			'channel_visibility' => $this->product_helper->get_channel_visibility( $product ),
			'sync_status'        => $this->meta_handler->get_sync_status( $product ),
			'issues'             => $this->product_helper->get_validation_errors( $product ),
			'is_setup_complete'  => $this->merchant_center->is_setup_complete(),
			'get_started_url'    => $this->get_start_url(),
		];
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_product', [ $this, 'handle_submission' ], 10, 2 );
		add_action( 'woocommerce_update_product', [ $this, 'handle_submission' ], 10, 2 );
	}

	/**
	 * @param int        $product_id
	 * @param WC_Product $product
	 */
	public function handle_submission( int $product_id, WC_Product $product ) {
		/**
		 * Array of `true` values for each product IDs already handled by this method. Used to prevent double submission.
		 *
		 * @var bool[] $already_updated
		 */
		static $already_updated = [];

		$field_id = $this->get_visibility_field_id();
		// phpcs:disable WordPress.Security.NonceVerification
		// nonce is verified by self::verify_nonce
		if ( ! $this->verify_nonce() || ! isset( $_POST[ $field_id ] ) || isset( $already_updated[ $product_id ] ) ) {
			return;
		}

		// only update the value for supported product types
		if ( ! in_array( $product->get_type(), ProductSyncer::get_supported_product_types(), true ) ) {
			return;
		}

		try {
			$visibility = empty( $_POST[ $field_id ] ) ?
				ChannelVisibility::cast( ChannelVisibility::SYNC_AND_SHOW ) :
				ChannelVisibility::cast( sanitize_key( $_POST[ $field_id ] ) );
			// phpcs:enable WordPress.Security.NonceVerification

			$this->meta_handler->update_visibility( $product, $visibility );

			$already_updated[ $product_id ] = true;
		} catch ( InvalidValue $exception ) {
			// silently log the exception and do not set the product's visibility if an invalid visibility value is sent.
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
