<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use DateTimeZone;
use WP as WPCore;
use WP_Error;
use WP_Post;
use WP_Term;
use WP_Taxonomy;
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

	/**
	 * Retrieves the timezone from site settings as a `DateTimeZone` object.
	 *
	 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
	 *
	 * @return DateTimeZone Timezone object.
	 *
	 * @since 1.7.0
	 */
	public function wp_timezone(): DateTimeZone {
		return wp_timezone();
	}

	/**
	 * Convert float number to format based on the locale.
	 *
	 * @param float $number   The number to convert based on locale.
	 * @param int   $decimals Optional. Precision of the number of decimal places. Default 0.
	 *
	 * @return string Converted number in string format.
	 *
	 * @since 1.7.0
	 */
	public function number_format_i18n( float $number, int $decimals = 0 ): string {
		return number_format_i18n( $number, $decimals );
	}

	/**
	 * Determines whether the current request is a WordPress Ajax request.
	 *
	 * @return bool True if it's a WordPress Ajax request, false otherwise.
	 *
	 * @since 1.10.0
	 */
	public function wp_doing_ajax(): bool {
		return wp_doing_ajax();
	}

	/**
	 * Retrieves an array of the latest posts, or posts matching the given criteria.
	 *
	 * @since 2.4.0
	 *
	 * @see WP_Query
	 * @see WP_Query::parse_query()
	 *
	 * @param array $args {
	 *     Arguments to retrieve posts. See WP_Query::parse_query() for all available arguments.
	 * }
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	public function get_posts( array $args ): array {
		return get_posts( $args );
	}

	/**
	 * Gets a list of all registered post type objects.
	 *
	 * @since 2.4.0
	 *
	 * @param array|string $args     Optional. An array of key => value arguments to match against
	 *                               the post type objects. Default empty array.
	 * @param string       $output   Optional. The type of output to return. Accepts post type 'names'
	 *                               or 'objects'. Default 'names'.
	 * @param string       $operator Optional. The logical operation to perform. 'or' means only one
	 *                               element from the array needs to match; 'and' means all elements
	 *                               must match; 'not' means no elements may match. Default 'and'.
	 * @return string[]|WP_Post_Type[] An array of post type names or objects.
	 */
	public function get_post_types( $args = [], string $output = 'names', string $operator = 'and' ): array {
		return get_post_types( $args, $output, $operator );
	}

	/**
	 * Retrieves a list of registered taxonomy names or objects.
	 *
	 * @since 2.4.0
	 *
	 * @param array  $args     Optional. An array of `key => value` arguments to match against the taxonomy objects.
	 *                         Default empty array.
	 * @param string $output   Optional. The type of output to return in the array. Accepts either taxonomy 'names'
	 *                         or 'objects'. Default 'names'.
	 * @param string $operator Optional. The logical operation to perform. Accepts 'and' or 'or'. 'or' means only
	 *                         one element from the array needs to match; 'and' means all elements must match.
	 *                         Default 'and'.
	 * @return string[]|WP_Taxonomy[] An array of taxonomy names or objects.
	 */
	public function get_taxonomies( array $args = [], string $output = 'names', string $operator = 'and' ): array {
		return get_taxonomies( $args, $output, $operator );
	}

	/**
	 * Retrieves the terms in a given taxonomy or list of taxonomies.
	 *
	 * @since 2.4.0
	 *
	 * @param array|string $args       Optional. Array or string of arguments. See WP_Term_Query::__construct()
	 *                                 for information on accepted arguments. Default empty array.
	 * @param array|string $deprecated Optional. Argument array, when using the legacy function parameter format.
	 *                                 If present, this parameter will be interpreted as `$args`, and the first
	 *                                 function parameter will be parsed as a taxonomy or array of taxonomies.
	 *                                 Default empty.
	 * @return WP_Term[]|int[]|string[]|string|WP_Error Array of terms, a count thereof as a numeric string,
	 *                                                  or WP_Error if any of the taxonomies do not exist.
	 *                                                  See the function description for more information.
	 */
	public function get_terms( $args = [], $deprecated = '' ) {
		return get_terms( $args, $deprecated );
	}

	/**
	 * Get static homepage
	 *
	 * @since 2.4.0
	 *
	 * @see https://wordpress.org/support/article/creating-a-static-front-page/
	 *
	 * @return WP_Post|null Returns the Homepage post if it is set as a static otherwise null.
	 */
	public function get_static_homepage() {
		$post_id = (int) get_option( 'page_on_front' );

		// The front page contains a static home page
		if ( $post_id > 0 ) {
			return get_post( $post_id );
		}

		return null;
	}

	/**
	 * Get Shop page
	 *
	 * @since 2.4.0
	 *
	 * @return WP_Post|null Returns the Homepage post if it is set as a static otherwise null.
	 */
	public function get_shop_page() {
		$post_id = wc_get_page_id( 'shop' );

		if ( $post_id > 0 ) {
			return get_post( $post_id );
		}

		return null;

	}

	/**
	 * If any of the currently registered image sub-sizes are missing,
	 * create them and update the image meta data.
	 *
	 * @since 2.4.0
	 *
	 * @param int $attachment_id The image attachment post ID.
	 * @return array|WP_Error The updated image meta data array or WP_Error object
	 *                        if both the image meta and the attached file are missing.
	 */
	public function wp_update_image_subsizes( int $attachment_id ) {
		// It is required as wp_update_image_subsizes is not loaded automatically.
		if ( ! function_exists( 'wp_update_image_subsizes' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		return wp_update_image_subsizes( $attachment_id );

	}

	/**
	 * Performs an HTTP request using the GET method and returns its response.
	 *
	 * @since 2.4.0
	 *
	 * @see wp_remote_request() For more information on the response array format.
	 * @see WP_Http::request() For default arguments information.
	 *
	 * @param string $url  URL to retrieve.
	 * @param array  $args Optional. Request arguments. Default empty array.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	public function wp_remote_get( string $url, array $args = [] ) {
		return wp_remote_get( $url, $args );
	}
}
