<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\BudgetRecommendationTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use wpdb;
/**
 * Class MigratorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration
 */
class BudgetRecommendationTableTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp   = $this->createMock( WP::class );
		$this->wpdb = $this->getMockBuilder( wpdb::class )
						 ->setMethods( [ 'query', 'prepare' ] )
						 ->disableOriginalConstructor()
						 ->disableOriginalClone()
						 ->disableArgumentCloning()
						 ->disallowMockingUnknownTypes()
						 ->getMock();

		$this->mock_budget_recommendation = $this->getMockBuilder( BudgetRecommendationTable::class )
						->setMethods( [ 'exists', 'truncate', 'get_sql_safe_name' ] )
						->setConstructorArgs(
							[
								$this->wp,
								$this->wpdb,
							]
						)
						->getMock();
	}


	public function test_reload_data_when_table_exists() {
		$this->mock_budget_recommendation->expects( $this->once() )
						 ->method( 'exists' )
						 ->willReturn( true );

		$this->mock_budget_recommendation->expects( $this->once() )
						 ->method( 'truncate' );

		$this->wpdb->expects( $this->atLeastOnce() )
		->method( 'query' );

		$this->mock_budget_recommendation->reload_data();

		$this->assertTrue( $this->mock_budget_recommendation->has_loaded_initial_data );
	}

	public function test_reload_data_when_table_does_not_exist() {
		$this->mock_budget_recommendation->expects( $this->once() )
						 ->method( 'exists' )
						 ->willReturn( false );

		$this->mock_budget_recommendation->expects( $this->exactly( 0 ) )
						 ->method( 'truncate' );

		$this->wpdb->expects( $this->exactly( 0 ) )
						 ->method( 'query' );

		$this->mock_budget_recommendation->reload_data();

		$this->assertFalse( $this->mock_budget_recommendation->has_loaded_initial_data );

	}

	public function test_reload_data_when_table_exists_but_data_was_loaded_earlier() {
		$this->mock_budget_recommendation->expects( $this->once() )
						 ->method( 'exists' )
						 ->willReturn( true );

		$this->mock_budget_recommendation->expects( $this->exactly( 0 ) )
						 ->method( 'truncate' );

		$this->wpdb->expects( $this->exactly( 0 ) )
						 ->method( 'query' );

		// If the table has been deleted or if it is a first installation, the BudgetRecommendationTable::install loads the data
		$this->mock_budget_recommendation->has_loaded_initial_data = true;

		$this->mock_budget_recommendation->reload_data();

	}

}
