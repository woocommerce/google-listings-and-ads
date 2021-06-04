<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
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

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * ChannelVisibilityMetaBox constructor.
	 *
	 * @param Admin              $admin
	 * @param ProductMetaHandler $meta_handler
	 * @param ProductHelper      $product_helper
	 */
	public function __construct( Admin $admin, ProductMetaHandler $meta_handler, ProductHelper $product_helper ) {
		$this->meta_handler   = $meta_handler;
		$this->product_helper = $product_helper;
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
		$supported_types = ProductSyncer::get_supported_product_types();

		return array_map(
			function ( string $product_type ) {
				return "show_if_{$product_type}";
			},
			$supported_types
		);
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
		$product_id = $post->ID;
		$product    = $this->product_helper->get_wc_product( $product_id );

		return [
			'product_id'  => $product_id,
			'product'     => $product,
			'visibility'  => $this->product_helper->get_visibility( $product ),
			'sync_status' => $this->meta_handler->get_sync_status( $product ),
			'issues'      => $this->product_helper->get_validation_errors( $product ),
		];
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_product', [ $this, 'handle_submission' ] );
		add_action( 'woocommerce_update_product', [ $this, 'handle_submission' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'handle_submission' ], 10, 2 );
	}

	/**
	 * @param int $product_id
	 */
	public function handle_submission( int $product_id ) {
		// phpcs:disable WordPress.Security.NonceVerification
		// nonce is verified by self::verify_nonce
		if ( ! $this->verify_nonce() || ! isset( $_POST['visibility'] ) ) {
			return;
		}

		$product    = $this->product_helper->get_wc_product( $product_id );
		$visibility = empty( $_POST['visibility'] ) ?
			ChannelVisibility::cast( ChannelVisibility::SYNC_AND_SHOW ) :
			ChannelVisibility::cast( sanitize_key( $_POST['visibility'] ) );
		$this->meta_handler->update_visibility( $product, $visibility );
		// phpcs:enable WordPress.Security.NonceVerification
	}
}
