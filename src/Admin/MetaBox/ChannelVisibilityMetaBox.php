<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
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
	 * ChannelVisibilityMetaBox constructor.
	 *
	 * @param Admin              $admin
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( Admin $admin, ProductMetaHandler $meta_handler ) {
		$this->meta_handler = $meta_handler;
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
	 * Returns an array of variables to be used in the view.
	 *
	 * @param WP_Post $post The WordPress post object the box is loaded for.
	 * @param array   $args Array of data passed to the callback. Defined by `get_callback_args`.
	 *
	 * @return array
	 */
	protected function get_view_context( WP_Post $post, array $args ): array {
		$product_id = $post->ID;
		return [
			'product_id'   => $product_id,
			'product'      => wc_get_product( $product_id ),
			'sync_enabled' => wc_string_to_bool( $this->meta_handler->get_sync_enabled( $product_id ) ),
			'synced_at'    => $this->meta_handler->get_synced_at( $product_id ),
			'issues'       => [], // todo: replace this with the list of issues retrieved from Google's Product Statuses API
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
		// todo: fix phpcs complaining about nonce verification
		if ( ! $this->verify_nonce() || ! isset( $_POST['sync_enabled'] ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			$sync_enabled = 'no' !== $_POST['sync_enabled'] ? 'yes' : 'no';
			$this->meta_handler->update_sync_enabled( $product_id, $sync_enabled );
		}
	}
}
