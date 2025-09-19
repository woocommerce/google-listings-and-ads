<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

defined( 'ABSPATH' ) || exit;

/**
 * Trait WPAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
trait WPAwareTrait {

	/**
	 * The WP proxy object.
	 *
	 * @var WP
	 */
	protected $wp;

	/**
	 * Set the WP proxy object.
	 *
	 * @param WP $wp
	 */
	public function set_wp_proxy_object( WP $wp ): void {
		$this->wp = $wp;
	}
}
