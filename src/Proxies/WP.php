<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use function plugins_url;

/**
 * Class WP.
 *
 * This class provides proxy methods to wrap around WP functions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class WP {

	use PluginHelper;

	/**
	 * Get the plugin URL, possibly with an added path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function plugins_url( string $path = '' ): string {
		return plugins_url( $path, $this->get_main_file() );
	}
}
