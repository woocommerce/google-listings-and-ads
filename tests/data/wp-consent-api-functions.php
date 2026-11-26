<?php
declare( strict_types=1 );

/**
 * Stand-in for the WP Consent API plugin's global function, so a test can model
 * a store with that plugin active. Load it only from a test that runs in a
 * separate process: a global function cannot be removed once declared.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_has_consent' ) ) {
	/**
	 * @param string $category Consent category.
	 *
	 * @return bool
	 */
	function wp_has_consent( $category ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return true;
	}
}
