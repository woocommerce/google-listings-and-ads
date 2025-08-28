<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\AdsRecommendationsTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class AdsRecommendationsTableTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table
 */
class AdsRecommendationsTableTest extends UnitTest {
	/**
	 * Test installing the DB table to ensure there are no errors during install.
	 */
	public function test_db_install() {
		global $wpdb;

		$table = new AdsRecommendationsTable( new WP(), $wpdb );
		$table->install();

		$this->assertEmpty( $wpdb->last_error );
	}
}
