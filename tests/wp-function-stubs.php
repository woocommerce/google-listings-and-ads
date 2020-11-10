<?php

// This namespace is deliberately different from other files in the tests directory.
namespace Automattic\WooCommerce\GoogleListingsAndAds;

/**
 * Retrieve a mock URL for a path to a plugin
 * @param string $path
 * @param string $plugin
 *
 * @return string
 */
function plugins_url( $path = '', $plugin = '' ): string {
	$base = basename( dirname( $plugin ) );
	$path = '/' . ltrim( $path, '/' );

	return "://google-listings-and-ads.test/wp-content/{$base}{$path}";
}
