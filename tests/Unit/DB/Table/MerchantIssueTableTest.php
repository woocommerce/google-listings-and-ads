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

	public function test_count_account_issues_counts_only_merchant_center_account_issues_with_given_severities() {
		$table = $this->get_installed_table();

		$this->insert_issue( $table, 0, 'error', 'mc', 'account' );
		$this->insert_issue( $table, 0, 'critical', 'mc', 'account' );
		$this->insert_issue( $table, 0, 'suggestion', 'mc', 'account' );
		$this->insert_issue( $table, 0, 'error', 'custom', 'account' );
		$this->insert_issue( $table, 10, 'error', 'mc', 'product' );

		$this->assertSame( 2, $table->count_account_issues( [ 'error', 'critical' ] ) );
	}

	public function test_count_account_issues_returns_zero_without_issues() {
		$table = $this->get_installed_table();

		$this->assertSame( 0, $table->count_account_issues( [ 'error', 'critical' ] ) );
	}

	public function test_count_products_with_issues_excludes_given_severities() {
		$table   = $this->get_installed_table();
		$error   = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$warning = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $error, 'error', 'mc' );
		$this->insert_issue( $table, $warning, 'DEMOTED', 'mc' );
		$this->insert_issue( $table, $warning, 'NOT_IMPACTED', 'mc' );

		$this->assertSame( 1, $table->count_products_with_issues( 'mc', [ 'warning', 'suggestion', 'DEMOTED', 'NOT_IMPACTED' ] ) );
	}

	public function test_count_products_with_issues_counts_a_product_once_across_issues() {
		$table   = $this->get_installed_table();
		$product = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $product, 'error', 'mc' );
		$this->insert_issue( $table, $product, 'critical', 'mc' );

		$this->assertSame( 1, $table->count_products_with_issues( 'mc' ) );
	}

	public function test_count_products_with_issues_counts_variations_as_their_parent() {
		$table       = $this->get_installed_table();
		$parent      = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$variation_a = $this->factory()->post->create(
			[
				'post_type'   => 'product_variation',
				'post_parent' => $parent,
			]
		);
		$variation_b = $this->factory()->post->create(
			[
				'post_type'   => 'product_variation',
				'post_parent' => $parent,
			]
		);
		$simple      = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $variation_a, 'error', 'mc' );
		$this->insert_issue( $table, $variation_b, 'error', 'mc' );
		$this->insert_issue( $table, $parent, 'error', 'mc' );
		$this->insert_issue( $table, $simple, 'error', 'mc' );

		$this->assertSame( 2, $table->count_products_with_issues( 'mc' ) );
	}

	public function test_count_products_with_issues_filters_by_source() {
		$table    = $this->get_installed_table();
		$mc       = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$pre_sync = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$custom   = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $mc, 'error', 'mc' );
		$this->insert_issue( $table, $pre_sync, 'error', 'pre-sync' );
		$this->insert_issue( $table, $custom, 'error', 'custom' );
		$this->insert_issue( $table, 0, 'error', 'pre-sync', 'account' );

		$this->assertSame( 1, $table->count_products_with_issues( 'mc' ) );
		$this->assertSame( 1, $table->count_products_with_issues( 'pre-sync' ) );
	}

	public function test_count_products_with_issues_returns_zero_without_issues() {
		$table = $this->get_installed_table();

		$this->assertSame( 0, $table->count_products_with_issues( 'pre-sync' ) );
	}

	/**
	 * Install the real table and start each test from an empty one.
	 *
	 * @return MerchantIssueTable
	 */
	protected function get_installed_table(): MerchantIssueTable {
		global $wpdb;

		$table = new MerchantIssueTable( new WP(), $wpdb );
		$table->install();
		$table->truncate();

		return $table;
	}

	/**
	 * Insert an issue row.
	 *
	 * @param MerchantIssueTable $table
	 * @param int                $product_id
	 * @param string             $severity
	 * @param string             $source
	 * @param string             $type
	 */
	protected function insert_issue( MerchantIssueTable $table, int $product_id, string $severity, string $source, string $type = 'product' ): void {
		global $wpdb;

		$wpdb->insert(
			$table->get_name(),
			[
				'product_id'           => $product_id,
				'issue'                => 'Issue ' . wp_generate_password( 8, false ),
				'code'                 => 'code',
				'severity'             => $severity,
				'product'              => 'Product',
				'action'               => '',
				'action_url'           => '',
				'applicable_countries' => '[]',
				'source'               => $source,
				'type'                 => $type,
				'created_at'           => '2026-10-27 00:00:00',
			]
		);
	}
}
