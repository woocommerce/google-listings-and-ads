<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\DBHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use wpdb;

/**
 * Class DBHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 *
 * @property wpdb     $wpdb
 * @property DBHelper $db_helper
 * @property string   $table_name
 */
class DBHelperTest extends ContainerAwareUnitTest {

	public function test_index_exists() {
		$this->wpdb->query( "CREATE TABLE `{$this->table_name}` (`id` bigint(20) NOT NULL);" );
		$this->wpdb->query( "ALTER TABLE `{$this->table_name}` ADD INDEX `id_index` (`id`);" );
		$this->assertTrue( $this->db_helper->index_exists( $this->table_name, 'id_index' ) );
		$this->wpdb->query( "ALTER TABLE `{$this->table_name}` DROP INDEX `id_index`;" );
		$this->assertFalse( $this->db_helper->index_exists( $this->table_name, 'id_index' ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->wpdb->query( "DROP TABLE IF EXISTS `{$this->table_name}`;" );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->wpdb       = $this->container->get( wpdb::class );
		$this->db_helper  = $this->container->get( DBHelper::class );
		$this->table_name = "{$this->wpdb->prefix}test_table_dbhelper";
	}
}
