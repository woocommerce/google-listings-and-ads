<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantPriceBenchmarksTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class MerchantPriceBenchmarksTableTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table
 */
class MerchantPriceBenchmarksTableTest extends UnitTest {
	/**
	 * Test installing the DB table to ensure there are no errors during install.
	 */
	public function test_db_install() {
		global $wpdb;

		$table = new MerchantPriceBenchmarksTable( new WP(), $wpdb );
		$table->install();

		$this->assertEmpty( $wpdb->last_error );
	}
}
