<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Trait WooAdminNavigationTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
trait WooAdminNavigationTrait {
	/**
	 * Whether the new WooCommerce Navigation should be used.
	 *
	 * @var bool
	 */
	private $woo_nav_enabled;

	/**
	 * Check the class availability of new WooCommerce Navigation and is it enabled.
	 *
	 * @return bool
	 */
	protected function is_woo_nav_enabled() {
		if ( ! isset( $this->woo_nav_enabled ) ) {
			$this->woo_nav_enabled = class_exists( Features::class ) && Features::is_enabled( 'navigation' );
		}
		return $this->woo_nav_enabled;
	}
}
