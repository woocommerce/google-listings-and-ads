<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\ViewHelperTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WC_Coupon;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponBulkEdit
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit
 */
class CouponBulkEdit implements BulkEditInterface, Registerable {

	use AdminConditional;
	use ViewHelperTrait;

	protected const VIEW_PATH     = 'views/bulk-edit/shop_coupon.php';
	protected const TARGET_COLUMN = 'usage';

	/**
	 * @var CouponMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * AbstractMetaBox constructor.
	 *
	 * @param CouponMetaHandler     $meta_handler
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( CouponMetaHandler $meta_handler, MerchantCenterService $merchant_center ) {
		$this->meta_handler    = $meta_handler;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'bulk_edit_custom_box', [ $this, 'render_view' ], 10, 2 );
		add_action( 'bulk_edit_save_post', [ $this, 'handle_submission' ], 10, 2 );
	}

	/**
	 * The screen on which to show the bulk edit view.
	 *
	 * @return string
	 */
	public function get_screen(): string {
		return self::SCREEN_COUPON;
	}

	/**
	 * Render the coupon bulk edit view.
	 *
	 * @param string $column_name Column being shown.
	 * @param string $post_type Post type being shown.
	 */
	public function render_view( $column_name, $post_type ) {
		if ( $this->get_screen() !== $post_type || self::TARGET_COLUMN !== $column_name ) {
			return;
		}

		if ( ! $this->merchant_center->is_setup_complete() ) {
			return;
		}

		$target_country = $this->merchant_center->get_main_target_country();
		if ( ! $this->merchant_center->is_promotion_supported_country( $target_country ) ) {
			return;
		}

		include path_join( dirname( __DIR__, 3 ), self::VIEW_PATH );
	}

	/**
	 * Handle the coupon bulk edit submission.
	 *
	 * @param int    $post_id Post ID being saved.
	 * @param object $post Post object being saved.
	 *
	 * @return int $post_id
	 */
	public function handle_submission( int $post_id, $post ): int {
		$request_data = $this->request_data();

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( Constants::is_true( 'DOING_AUTOSAVE' ) ) {
			return $post_id;
		}

		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || $this->get_screen() !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check nonce.
	    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! isset( $request_data['woocommerce_gla_bulk_edit'] ) || ! wp_verify_nonce( $request_data['woocommerce_gla_bulk_edit_nonce'], 'woocommerce_gla_bulk_edit_nonce' ) ) {
			return $post_id;
		}

		if ( ! empty( $request_data['change_channel_visibility'] ) ) {
			// Get the coupon and save.
			$coupon     = new WC_Coupon( $post_id );
			$visibility =
			ChannelVisibility::cast( sanitize_key( $request_data['change_channel_visibility'] ) );

			if ( $this->meta_handler->get_visibility( $coupon ) !== $visibility ) {
				$this->meta_handler->update_visibility( $coupon, $visibility );
				do_action( 'woocommerce_gla_bulk_update_coupon', $post_id );
			}
		}

		return $post_id;
	}

	/**
	 * Get the current request data ($_REQUEST superglobal).
	 * This method is added to ease unit testing.
	 *
	 * @return array The $_REQUEST superglobal.
	 */
	protected function request_data(): array {
		// Nonce must be verified manually.
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $_REQUEST;
	}
}
