<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use PHPUnit\Framework\MockObject\MockObject;
use wpdb;

/**
 * Class MerchantIssueTableTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table
 */
class MerchantIssueTableTest extends UnitTest {

	/** @var MockObject|MerchantIssueTable $mock_merchant_issue */
	protected $mock_merchant_issue;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var MockObject|wpdb $wpdb */
	protected $wpdb;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp   = $this->createMock( WP::class );
		$this->wpdb = $this->getMockBuilder( wpdb::class )
			->onlyMethods( [ 'query', 'prepare' ] )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->getMock();

		$this->mock_merchant_issue = $this->getMockBuilder( MerchantIssueTable::class )
						->onlyMethods( [ 'exists', 'truncate', 'get_sql_safe_name' ] )
						->setConstructorArgs(
							[
								$this->wp,
								$this->wpdb,
							]
						)
						->getMock();
	}


	public function test_delete_specific_product_issues_with_empty_array() {
		$this->wpdb->expects( $this->never() )
		->method( 'query' );

		$this->mock_merchant_issue->delete_specific_product_issues( [] );
	}

	public function test_delete_specific_product_issues_with_products() {
		$this->wpdb->expects( $this->exactly( 1 ) )
		->method( 'query' );

		$this->mock_merchant_issue->delete_specific_product_issues( [ 1 ] );
	}

	public function test_delete_specific_product_issues_prepares_ids_and_source() {
		$source = "mc' OR 1=1 --";

		$this->mock_merchant_issue->method( 'get_sql_safe_name' )->willReturn( 'wp_gla_merchant_issues' );
		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->with(
				'DELETE FROM %i WHERE `product_id` IN (%d,%d) AND `source` = %s',
				[ 'wp_gla_merchant_issues', 5, 8, $source ]
			)
			->willReturn( 'prepared delete query' );
		$this->wpdb->expects( $this->once() )
			->method( 'query' )
			->with( 'prepared delete query' );

		$this->mock_merchant_issue->delete_specific_product_issues( [ 5, 8 ], $source );
	}

	public function test_delete_by_ids_with_empty_array() {
		$this->wpdb->expects( $this->never() )
		->method( 'query' );

		$this->mock_merchant_issue->delete_by_ids( [] );
	}

	public function test_delete_by_ids_deletes_the_given_rows() {
		$this->mock_merchant_issue->method( 'get_sql_safe_name' )->willReturn( 'wp_gla_merchant_issues' );

		$this->wpdb->method( 'prepare' )
		->willReturnCallback(
			function ( $query, $args ) {
				$query = str_replace( '%i', '`%s`', $query );
				$query = str_replace( '%d', '%s', $query );

				return vsprintf( $query, (array) $args );
			}
		);

		$this->wpdb->expects( $this->once() )
		->method( 'query' )
		->with( 'DELETE FROM `wp_gla_merchant_issues` WHERE `id` IN (5,8)' );

		$this->mock_merchant_issue->delete_by_ids( [ 5, 8 ] );
	}

	/**
	 * Test installing the DB table to ensure there are no errors during install.
	 */
	public function test_db_install() {
		global $wpdb;

		$table = new MerchantIssueTable( new WP(), $wpdb );
		$table->install();

		$this->assertEmpty( $wpdb->last_error );
	}
}
