<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools;

/**
 * Mockable version of WP_Hook
 *
 * @see \WP_Hook
 */
class WPHookMock {
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
	public function do_action( $args ) {}
	public function has_filter( $hook, $callback ) {}
}
