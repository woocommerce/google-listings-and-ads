<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WP as WPCore;
use WP_Error;
use WP_Post;
use WP_Term;
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
	 * Get the locale of the current user.
	 *
	 * @return string
	 */
	public function get_user_locale(): string {
		return get_user_locale();
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

	/**
	 * Retrieves the edit post link for post.
	 *
	 * @param int|WP_POST $id Post ID or post object.
	 * @param string      $context How to output the '&' character.
	 *
	 * @return string The edit post link for the given post. Null if the post type does not exist or does not allow an editing UI.
	 */
	public function get_edit_post_link( $id, string $context = 'display' ): string {
		return get_edit_post_link( $id, $context );
	}

	/**
	 * Retrieves the terms of the taxonomy that are attached to the post.
	 *
	 * @param int|WP_Post $post     Post ID or object.
	 * @param string      $taxonomy Taxonomy name.
	 *
	 * @return WP_Term[]|false|WP_Error Array of WP_Term objects on success, false if there are no terms
	 *                                  or the post does not exist, WP_Error on failure.
	 */
	public function get_the_terms( $post, string $taxonomy ) {
		return get_the_terms( $post, $taxonomy );
	}

	/**
	 * Checks whether the given variable is a WordPress Error.
	 *
	 * Returns whether `$thing` is an instance of the `WP_Error` class.
	 *
	 * @param mixed $thing The variable to check.
	 *
	 * @return bool Whether the variable is an instance of WP_Error.
	 */
	public function is_wp_error( $thing ): bool {
		return is_wp_error( $thing );
	}

	/**
	 * Retrieves the timezone from site settings as a string.
	 *
	 * Uses the `timezone_string` option to get a proper timezone if available,
	 * otherwise falls back to an offset.
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 *
	 * @since 1.5.0
	 */
	public function wp_timezone_string(): string {
		return wp_timezone_string();
	}
}
