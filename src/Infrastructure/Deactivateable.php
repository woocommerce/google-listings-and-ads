<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Something that can be deactivated.
 *
 * By implementing a class with this interface, the plugin will automatically hook
 * it up to the WordPress deactivation hook. This means we don't have to worry about
 * manually writing deactivation code.
 */
interface Deactivateable {

	/**
	 * Deactivate the service.
	 *
	 * @return void
	 */
	public function deactivate(): void;
}
