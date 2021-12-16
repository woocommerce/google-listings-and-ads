<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class BulkEditInitializer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit
 */
class BulkEditInitializer implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'save_post', [ $this, 'bulk_edit_hook' ], 10, 2 );
	}

	/**
	 * Offers a way to hook into save post without causing an infinite loop
	 * when bulk saving info.
	 *
	 * @since 3.0.0
	 * @param int     $post_id Post ID being saved.
	 * @param WP_Post $post Post object being saved.
	 */
	public function bulk_edit_hook( int $post_id, WP_Post $post ): void {
		remove_action( 'save_post', [ $this, 'bulk_edit_hook' ] );
		do_action( 'bulk_edit_save_post', $post_id, $post );
		add_action( 'save_post', [ $this, 'bulk_edit_hook' ], 10, 2 );
	}
}
