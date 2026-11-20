<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Interface FreshReadOptionsInterface
 *
 * Options that can be read directly from the database, bypassing request caches.
 *
 * @since n.e.x.t
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
interface FreshReadOptionsInterface extends OptionsInterface {

	/**
	 * Get an option, bypassing the plugin's request cache and the WordPress object cache.
	 *
	 * Use this before modifying and re-saving an option that other requests may
	 * have updated after this request first read it.
	 *
	 * @param string $name          The option name.
	 * @param mixed  $default_value A default value for the option.
	 *
	 * @return mixed
	 */
	public function get_fresh( string $name, $default_value = null );
}
