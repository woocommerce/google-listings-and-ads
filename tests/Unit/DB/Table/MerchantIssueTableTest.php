<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
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
			->onlyMethods( [ 'query', 'prepare', 'get_var', 'get_results' ] )
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

	public function test_counts_return_null_when_the_query_fails() {
		$this->wpdb->method( 'get_var' )->willReturn( null );

		$this->assertNull( $this->mock_merchant_issue->count_account_issues( [ 'error' ] ) );
		$this->assertNull( $this->mock_merchant_issue->count_products_with_issues( 'mc' ) );
	}

	/**
	 * @dataProvider count_result_provider
	 * @param mixed    $result   Database result.
	 * @param int|null $expected Expected count.
	 */
	public function test_counts_accept_numeric_database_results( $result, ?int $expected ) {
		$this->wpdb->method( 'get_var' )->willReturn( $result );
		$this->assertSame( $expected, $this->mock_merchant_issue->count_account_issues( [ 'error' ] ) );
		$this->assertSame( $expected, $this->mock_merchant_issue->count_products_with_issues( 'mc' ) );
	}

	public function count_result_provider(): array {
		return [
			'string'  => [ '3', 3 ],
			'integer' => [ 3, 3 ],
			'zero'    => [ 0, 0 ],
			'invalid' => [ false, null ],
		];
	}

	public function test_get_account_issue_rows_returns_merchant_center_and_filter_account_issues() {
		$table = $this->get_installed_table();

		$this->insert_issue( $table, 0, 'critical', 'mc', 'account', 'account_suspended', '["US","GB"]' );
		$this->insert_issue( $table, 0, 'error', 'filter', 'account', 'gla_country_mismatch', '["all"]' );
		$this->insert_issue( $table, 0, 'error', 'pre-sync', 'account', 'presync_account' );
		$this->insert_issue( $table, 0, 'error', 'custom', 'account', 'custom_account' );
		$this->insert_issue( $table, 10, 'error', 'mc', 'product', 'missing_gtin' );

		$rows = $table->get_account_issue_rows();
		usort(
			$rows,
			function ( array $a, array $b ) {
				return strcmp( $a['code'], $b['code'] );
			}
		);

		$this->assertSame(
			[
				[
					'code'                 => 'account_suspended',
					'severity'             => 'critical',
					'applicable_countries' => '["US","GB"]',
					'source'               => 'mc',
				],
				[
					'code'                 => 'gla_country_mismatch',
					'severity'             => 'error',
					'applicable_countries' => '["all"]',
					'source'               => 'filter',
				],
			],
			$rows
		);
	}

	public function test_get_top_issue_codes_excludes_given_severities_and_orders_by_count_then_code() {
		$table = $this->get_installed_table();
		$p1    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p2    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p3    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p4    = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		// Tied codes are inserted in reverse alphabetical order.
		$this->insert_issue( $table, $p1, 'error', 'mc', 'product', 'landing_page_error' );
		$this->insert_issue( $table, $p2, 'error', 'mc', 'product', 'landing_page_error' );
		$this->insert_issue( $table, $p3, 'error', 'mc', 'product', 'image_link_broken' );
		$this->insert_issue( $table, $p4, 'DISAPPROVED', 'mc', 'product', 'image_link_broken' );
		$this->insert_issue( $table, $p1, 'error', 'mc', 'product', 'missing_gtin' );
		$this->insert_issue( $table, $p2, 'error', 'mc', 'product', 'missing_gtin' );
		$this->insert_issue( $table, $p3, 'critical', 'mc', 'product', 'missing_gtin' );
		$this->insert_issue( $table, $p4, 'warning', 'mc', 'product', 'missing_gtin' );

		foreach ( [ $p1, $p2, $p3, $p4 ] as $product_id ) {
			$this->insert_issue( $table, $product_id, 'warning', 'mc', 'product', 'warning_only' );
			$this->insert_issue( $table, $product_id, 'suggestion', 'mc', 'product', 'suggestion_only' );
			$this->insert_issue( $table, $product_id, 'DEMOTED', 'mc', 'product', 'demoted_only' );
			$this->insert_issue( $table, $product_id, 'NOT_IMPACTED', 'mc', 'product', 'not_impacted_only' );
			$this->insert_issue( $table, $product_id, 'error', 'pre-sync', 'product', 'presync_error_price' );
		}
		$this->insert_issue( $table, 0, 'error', 'mc', 'account', 'account_suspended' );

		$this->assertSame(
			[
				'missing_gtin'       => 3,
				'image_link_broken'  => 2,
				'landing_page_error' => 2,
			],
			$table->get_top_issue_codes( 'mc', MerchantStatuses::WARNING_SEVERITIES )
		);
	}

	public function test_get_top_issue_codes_counts_a_variable_product_once() {
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

		$this->insert_issue( $table, $variation_a, 'error', 'mc', 'product', 'missing_gtin' );
		$this->insert_issue( $table, $variation_b, 'error', 'mc', 'product', 'missing_gtin' );
		$this->insert_issue( $table, $simple, 'error', 'mc', 'product', 'missing_gtin' );

		$this->assertSame( [ 'missing_gtin' => 2 ], $table->get_top_issue_codes( 'mc' ) );
	}

	public function test_get_top_issue_codes_keeps_only_the_limit() {
		$table = $this->get_installed_table();
		$p1    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p2    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p3    = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $p1, 'error', 'mc', 'product', 'least_common' );
		$this->insert_issue( $table, $p1, 'error', 'mc', 'product', 'second_most_common' );
		$this->insert_issue( $table, $p2, 'error', 'mc', 'product', 'second_most_common' );
		$this->insert_issue( $table, $p1, 'error', 'mc', 'product', 'most_common' );
		$this->insert_issue( $table, $p2, 'error', 'mc', 'product', 'most_common' );
		$this->insert_issue( $table, $p3, 'error', 'mc', 'product', 'most_common' );

		$this->assertSame(
			[
				'most_common'        => 3,
				'second_most_common' => 2,
			],
			$table->get_top_issue_codes( 'mc', [], 2 )
		);
	}

	public function test_get_top_issue_codes_collapses_md5_fallback_codes_into_one_bucket() {
		$table = $this->get_installed_table();
		$p1    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p2    = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $p1, 'error', 'pre-sync', 'product', 'presync_error_attrib_' . md5( 'First unparsed error' ) );
		$this->insert_issue( $table, $p1, 'error', 'pre-sync', 'product', 'presync_error_attrib_' . md5( 'Second unparsed error' ) );
		$this->insert_issue( $table, $p2, 'error', 'pre-sync', 'product', 'presync_error_attrib_' . md5( 'Third unparsed error' ) );

		$this->assertSame( [ 'presync_error_other' => 2 ], $table->get_top_issue_codes( 'pre-sync' ) );
	}

	public function test_get_top_issue_codes_strips_the_index_suffix() {
		$table = $this->get_installed_table();
		$p1    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p2    = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->insert_issue( $table, $p1, 'error', 'pre-sync', 'product', 'presync_error_shipping[0]' );
		$this->insert_issue( $table, $p2, 'error', 'pre-sync', 'product', 'presync_error_shipping[1]' );

		$this->assertSame( [ 'presync_error_shipping' => 2 ], $table->get_top_issue_codes( 'pre-sync' ) );
	}

	public function test_get_top_issue_codes_keeps_codes_that_only_resemble_the_md5_fallback() {
		$table = $this->get_installed_table();
		$p1    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$p2    = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$short = 'presync_error_attrib_' . str_repeat( 'a', 31 );
		$long  = 'presync_error_attrib_' . str_repeat( 'b', 33 );

		$this->insert_issue( $table, $p1, 'error', 'pre-sync', 'product', $short );
		$this->insert_issue( $table, $p2, 'error', 'pre-sync', 'product', $long );

		$this->assertSame(
			[
				$short => 1,
				$long  => 1,
			],
			$table->get_top_issue_codes( 'pre-sync' )
		);
	}

	public function test_get_top_issue_codes_returns_empty_array_without_issues() {
		$table = $this->get_installed_table();

		$this->assertSame( [], $table->get_top_issue_codes( 'mc' ) );
	}

	public function test_rows_return_null_when_the_query_fails() {
		$this->wpdb->method( 'get_results' )->willReturn( [] );
		$this->wpdb->last_error = 'Table does not exist';

		$this->assertNull( $this->mock_merchant_issue->get_account_issue_rows() );
		$this->assertNull( $this->mock_merchant_issue->get_top_issue_codes( 'mc' ) );
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
	 * @param string             $code
	 * @param string             $countries
	 */
	protected function insert_issue( MerchantIssueTable $table, int $product_id, string $severity, string $source, string $type = 'product', string $code = 'code', string $countries = '[]' ): void {
		global $wpdb;

		$wpdb->insert(
			$table->get_name(),
			[
				'product_id'           => $product_id,
				'issue'                => 'Issue ' . wp_generate_password( 8, false ),
				'code'                 => $code,
				'severity'             => $severity,
				'product'              => 'Product',
				'action'               => '',
				'action_url'           => '',
				'applicable_countries' => $countries,
				'source'               => $source,
				'type'                 => $type,
				'created_at'           => '2026-10-27 00:00:00',
			]
		);
	}
}
