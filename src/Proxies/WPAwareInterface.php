<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

defined( 'ABSPATH' ) || exit;

/**
 * Interface WPAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
interface WPAwareInterface {

	/**
	 * Set the WP proxy object.
	 *
	 * @param WP $wp
	 */
	public function set_wp_proxy_object( WP $wp ): void;
}
