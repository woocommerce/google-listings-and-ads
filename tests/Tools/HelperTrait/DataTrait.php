<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

/**
 * Trait DataTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait DataTrait {
	/**
	 * @param string $file_name
	 *
	 * @return string Path to test data file
	 */
	public function get_data_file_path( string $file_name ): string {
		return GLA_TESTS_DATA_DIR . '/' . ltrim( $file_name, '/\\' );
	}
}
