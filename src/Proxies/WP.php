<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WP as WPCore;
use function dbDelta;
use function get_locale;
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

	/** @var WPCore $wp */
	protected $wp;

	/**
	 * WP constructor.
	 */
	public function __construct() {
		global $wp;
		$this->wp =& $wp;
	}

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

	/**
	 * Retrieve values from the WP query_vars property.
	 *
	 * @param string $key     The key of the value to retrieve.
	 * @param null   $default The default value to return if the key isn't found.
	 *
	 * @return mixed The query value if found, or the default value.
	 */
	public function get_query_vars( string $key, $default = null ) {
		return $this->wp->query_vars[ $key ] ?? $default;
	}

	/**
	 * Get the locale of the site.
	 *
	 * @return string
	 */
	public function get_locale(): string {
		return get_locale();
	}

	/**
	 * Run the WP dbDelta() function.
	 *
	 * @param string|string[] $sql The query or queries to run.
	 *
	 * @return array Results of the query or queries.
	 */
	public function db_delta( $sql ): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		return dbDelta( $sql );
	}
}
