<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\GoogleForWCPlugin;

/**
 * PluginFactory class.
 *
 * This is responsible for instantiating a Plugin object and returning the same object.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
final class PluginFactory {

	/**
	 * Get the instance of the Plugin object.
	 *
	 * @return GoogleForWCPlugin
	 */
	public static function instance() {
		static $plugin = null;
		if ( null === $plugin ) {
			$plugin = new GoogleForWCPlugin( woogle_get_container() );
		}

		return $plugin;
	}
}
