<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Something that can be activated.
 *
 * By implementing a class with this interface, the plugin will automatically hook
 * it up to the WordPress activation hook. This means we don't have to worry about
 * manually writing activation code.
 */
interface Activateable {

	/**
	 * Activate the service.
	 *
	 * @return void
	 */
	public function activate(): void;
}
