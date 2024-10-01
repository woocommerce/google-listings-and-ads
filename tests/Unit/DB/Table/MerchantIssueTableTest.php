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
}
