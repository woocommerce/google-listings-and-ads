<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\BulkEdit;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WP_Post;

defined( 'ABSPATH' ) || exit;

interface BulkEditInterface extends Service, Conditional {
	public const SCREEN_COUPON = 'shop_coupon';

	/**
	 * Function that renders view of custom bulk edit fields.
	 *
	 * @param string $column_name Column being shown.
	 * @param string $post_type Post type being shown.
	 */
	public function render_view( string $column_name, string $post_type );

	/**
	 * The screen or screens on which to show the box (such as a post type, 'link', or 'comment').
	 *
	 * Default is the current screen.
	 *
	 * @return string
	 */
	public function get_screen(): string;

	/**
	 * Handle the bulk edit submission.
	 *
	 * @param int     $post_id Post ID being saved.
	 * @param WP_Post $post Post object being saved.
	 *
	 * @return int $post_id
	 */
	public function handle_submission( int $post_id, WP_Post $post ): int;
}
