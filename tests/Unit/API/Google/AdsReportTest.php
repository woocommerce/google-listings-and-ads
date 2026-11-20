<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsReportTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsReportTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var AdsReport $report */
	protected $report;

	/** @var Container $container */
	protected $container;

	protected const TEST_ADS_ID = 1234567890;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();
		$this->ads_campaign = $this->createMock( AdsCampaign::class );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );

		$this->container = new Container();
		$this->container->addShared( AdsCampaign::class, $this->ads_campaign );

		$this->report = new AdsReport( $this->client );
		$this->report->set_options_object( $this->options );
		$this->report->set_container( $this->container );
	}

	/**
	 * Build a campaign table row.
	 *
	 * @param int    $id      Campaign ID.
	 * @param string $name    Campaign name.
	 * @param array  $metrics Row metrics.
	 * @param string $status  Campaign status.
	 * @param string $type    Campaign type.
	 *
	 * @return array
	 */
	protected function campaign_row( int $id, string $name, array $metrics, string $status = 'ENABLED', string $type = 'PERFORMANCE_MAX' ): array {
		return [
			'campaign' => [
				'status' => $status,
				'name'   => $name,
				'id'     => $id,
				'type'   => $type,
			],
			'metrics'  => $metrics,
		];
	}

	/**
	 * Build a product table row.
	 *
	 * @param string $id      Product item ID.
	 * @param string $title   Product title.
	 * @param array  $metrics Row metrics.
	 *
	 * @return array
	 */
	protected function product_row( string $id, string $title, array $metrics ): array {
		return [
			'metrics'  => $metrics,
			'segments' => [
				'productItemId' => $id,
				'productTitle'  => $title,
			],
		];
	}

	/**
	 * Build an interval row.
	 *
	 * @param string $interval Interval type (day, week, month, quarter or year).
	 * @param string $value    Segment value.
	 * @param array  $metrics  Row metrics.
	 *
	 * @return array
	 */
	protected function interval_row( string $interval, string $value, array $metrics ): array {
		$segment_keys = [
			'day'     => 'date',
			'week'    => 'week',
			'month'   => 'month',
			'quarter' => 'quarter',
			'year'    => 'year',
		];

		return [
			'metrics'  => $metrics,
			'segments' => [ $segment_keys[ $interval ] => $value ],
		];
	}

	public function test_get_report_data_campaigns() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks', 'impressions', 'spend', 'sales', 'conversions' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'table'     => [
					$this->campaign_row(
						1234567890,
						'First Campaign',
						[
							'clicks'           => 28,
							'conversions'      => 3,
							'conversionsValue' => 38,
							'costMicros'       => 14770000,
							'impressions'      => 2365,
						]
					),
					$this->campaign_row(
						2345678901,
						'Second Campaign',
						[
							'clicks'           => 58,
							'conversions'      => 4,
							'conversionsValue' => 86,
							'costMicros'       => 21600000,
							'impressions'      => 4823,
						]
					),
				],
				'intervals' => [
					$this->interval_row(
						'day',
						'2022-04-01',
						[
							'clicks'           => 70,
							'conversions'      => 5,
							'conversionsValue' => 106,
							'costMicros'       => 28250000,
							'impressions'      => 6810,
						]
					),
					$this->interval_row(
						'day',
						'2022-04-02',
						[
							'clicks'           => 16,
							'conversions'      => 2,
							'conversionsValue' => 18,
							'costMicros'       => 8120000,
							'impressions'      => 378,
						]
					),
				],
				'totals'    => [
					[
						'metrics' => [
							'clicks'           => 86,
							'conversions'      => 7,
							'conversionsValue' => 124,
							'costMicros'       => 36370000,
							'impressions'      => 7188,
						],
					],
				],
			],
			$report_args
		);

		$this->assertEquals(
			[
				'campaigns' => [
					[
						'id'          => 1234567890,
						'name'        => 'First Campaign',
						'status'      => 'enabled',
						'subtotals'   => [
							'clicks'      => 28,
							'impressions' => 2365,
							'spend'       => 14.77,
							'sales'       => 38,
							'conversions' => 3,
						],
						'isConverted' => false,
					],
					[
						'id'          => 2345678901,
						'name'        => 'Second Campaign',
						'status'      => 'enabled',
						'subtotals'   => [
							'clicks'      => 58,
							'impressions' => 4823,
							'spend'       => 21.6,
							'sales'       => 86,
							'conversions' => 4,
						],
						'isConverted' => false,
					],
				],
				'intervals' => [
					[
						'interval'  => '2022-04-01',
						'subtotals' => [
							'clicks'      => 70,
							'impressions' => 6810,
							'spend'       => 28.25,
							'sales'       => 106,
							'conversions' => 5,
						],
					],
					[
						'interval'  => '2022-04-02',
						'subtotals' => [
							'clicks'      => 16,
							'impressions' => 378,
							'spend'       => 8.12,
							'sales'       => 18,
							'conversions' => 2,
						],
					],
				],
				'totals'    => [
					'clicks'      => 86,
					'impressions' => 7188,
					'spend'       => 36.37,
					'sales'       => 124,
					'conversions' => 7,
				],
			],
			$this->report->get_report_data( 'campaigns', $report_args )
		);
	}

	public function test_get_report_data_products() {
		$report_args = [
			'interval' => 'month',
			'fields'   => [ 'clicks', 'impressions' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'table'     => [
					$this->product_row(
						'gla_123',
						'Product One',
						[
							'clicks'      => 223,
							'impressions' => 10619,
						]
					),
					$this->product_row(
						'gla_456',
						'Product Two',
						[
							'clicks'      => 11,
							'impressions' => 4259,
						]
					),
				],
				'intervals' => [
					$this->interval_row(
						'month',
						'2022-01',
						[
							'clicks'      => 45,
							'impressions' => 7013,
						]
					),
					$this->interval_row(
						'month',
						'2022-02',
						[
							'clicks'      => 189,
							'impressions' => 7865,
						]
					),
				],
				'totals'    => [
					[
						'metrics' => [
							'clicks'      => 234,
							'impressions' => 14878,
						],
					],
				],
			],
			$report_args
		);

		$this->assertEquals(
			[
				'products'  => [
					[
						'id'        => 'gla_123',
						'name'      => 'Product One',
						'subtotals' => [
							'clicks'      => 223,
							'impressions' => 10619,
						],
					],
					[
						'id'        => 'gla_456',
						'name'      => 'Product Two',
						'subtotals' => [
							'clicks'      => 11,
							'impressions' => 4259,
						],
					],
				],
				'intervals' => [
					[
						'interval'  => '2022-01',
						'subtotals' => [
							'clicks'      => 45,
							'impressions' => 7013,
						],
					],
					[
						'interval'  => '2022-02',
						'subtotals' => [
							'clicks'      => 189,
							'impressions' => 7865,
						],
					],
				],
				'totals'    => [
					'clicks'      => 234,
					'impressions' => 14878,
				],
			],
			$this->report->get_report_data( 'products', $report_args )
		);
	}

	/**
	 * The table is capped at per_page rows, but the totals come from their own query, so they
	 * still cover every product in the date range.
	 */
	public function test_get_report_data_totals_are_not_limited_to_table_rows() {
		$report_args = [
			'fields'   => [ 'clicks' ],
			'per_page' => 2,
		];

		$this->generate_ads_report_query_mock(
			[
				'table'         => [
					$this->product_row( 'gla_1', 'Product One', [ 'clicks' => 50 ] ),
					$this->product_row( 'gla_2', 'Product Two', [ 'clicks' => 40 ] ),
				],
				'totals'        => [
					[ 'metrics' => [ 'clicks' => 150 ] ],
				],
				'total_results' => [
					'table'  => 5,
					'totals' => 1,
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertCount( 2, $result['products'] );
		$this->assertSame( [ 'clicks' => 150 ], $result['totals'] );
	}

	/**
	 * Covers the row-processing guard: even if a page returns more rows than per_page, only
	 * per_page are processed into the table. The companion
	 * test_get_report_data_only_table_query_has_limit covers the other half: per_page is pushed
	 * down as a GAQL LIMIT so an oversized page should not arrive at all.
	 */
	public function test_get_report_data_stops_processing_table_at_per_page_rows() {
		$report_args = [
			'fields'   => [ 'clicks' ],
			'per_page' => 1,
		];

		$this->generate_ads_report_query_mock(
			[
				'table'  => [
					$this->product_row( 'gla_123', 'Product One', [ 'clicks' => 34 ] ),
					$this->product_row( 'gla_456', 'Product Two', [ 'clicks' => 11 ] ),
					$this->product_row( 'gla_789', 'Product Three', [ 'clicks' => 99 ] ),
				],
				'totals' => [
					[ 'metrics' => [ 'clicks' => 144 ] ],
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertCount( 1, $result['products'] );
		$this->assertSame( 'gla_123', $result['products'][0]['id'] );
		$this->assertSame( [ 'clicks' => 144 ], $result['totals'] );
	}

	public function test_get_report_data_intervals_come_from_date_only_query() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
			'per_page' => 1,
		];

		$this->generate_ads_report_query_mock(
			[
				'table'     => [
					$this->product_row( 'gla_1', 'Product One', [ 'clicks' => 7 ] ),
				],
				'intervals' => [
					$this->interval_row( 'day', '2022-04-01', [ 'clicks' => 3 ] ),
					$this->interval_row( 'day', '2022-04-02', [ 'clicks' => 4 ] ),
					$this->interval_row( 'day', '2022-04-03', [ 'clicks' => 5 ] ),
				],
				'totals'    => [
					[ 'metrics' => [ 'clicks' => 12 ] ],
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertSame(
			[ '2022-04-01', '2022-04-02', '2022-04-03' ],
			array_column( $result['intervals'], 'interval' )
		);
		$this->assertSame(
			[ [ 'clicks' => 3 ], [ 'clicks' => 4 ], [ 'clicks' => 5 ] ],
			array_column( $result['intervals'], 'subtotals' )
		);
		$this->assertSame( [ 'clicks' => 12 ], $result['totals'] );
	}

	public function test_get_report_data_only_table_query_has_limit() {
		$this->generate_ads_report_query_mock( [] );

		$this->report->get_report_data(
			'products',
			[
				'after'    => '2022-04-01',
				'before'   => '2022-04-30',
				'interval' => 'day',
				'fields'   => [ 'clicks' ],
				'orderby'  => 'clicks',
				'order'    => 'desc',
				'per_page' => 250,
			]
		);

		$this->assertSame(
			"SELECT segments.product_item_id,segments.product_title,metrics.clicks FROM shopping_performance_view WHERE segments.date BETWEEN '2022-04-01' AND '2022-04-30' ORDER BY metrics.clicks DESC LIMIT 250",
			$this->ads_report_queries['table']
		);
		$this->assertSame(
			"SELECT metrics.clicks FROM shopping_performance_view WHERE segments.date BETWEEN '2022-04-01' AND '2022-04-30'",
			$this->ads_report_queries['totals']
		);
		$this->assertSame(
			"SELECT metrics.clicks,segments.date FROM shopping_performance_view WHERE segments.date BETWEEN '2022-04-01' AND '2022-04-30'",
			$this->ads_report_queries['intervals']
		);
	}

	public function test_get_report_data_campaign_queries_have_no_campaign_columns_outside_table() {
		$this->generate_ads_report_query_mock( [] );

		$this->report->get_report_data(
			'campaigns',
			[
				'interval' => 'week',
				'fields'   => [ 'clicks', 'spend' ],
				'per_page' => 200,
			]
		);

		$this->assertSame(
			'SELECT campaign.id,campaign.name,campaign.status,campaign.advertising_channel_type,metrics.clicks,metrics.cost_micros FROM shopping_performance_view LIMIT 200',
			$this->ads_report_queries['table']
		);
		$this->assertSame(
			'SELECT metrics.clicks,metrics.cost_micros FROM shopping_performance_view',
			$this->ads_report_queries['totals']
		);
		$this->assertSame(
			'SELECT metrics.clicks,metrics.cost_micros,segments.week FROM shopping_performance_view',
			$this->ads_report_queries['intervals']
		);
	}

	/**
	 * Google Ads requires a field used in WHERE to also be selected, so a report filtered by
	 * product IDs keeps the product ID in the totals and intervals queries and sums per ID.
	 */
	public function test_get_report_data_filtered_by_product_ids_sums_per_id() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
			'ids'      => [ 'gla_1', 'gla_2' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'table'     => [
					$this->product_row( 'gla_1', 'Product One', [ 'clicks' => 9 ] ),
					$this->product_row( 'gla_2', 'Product Two', [ 'clicks' => 6 ] ),
				],
				'intervals' => [
					[
						'metrics'  => [ 'clicks' => 4 ],
						'segments' => [
							'date'          => '2022-04-01',
							'productItemId' => 'gla_1',
						],
					],
					[
						'metrics'  => [ 'clicks' => 1 ],
						'segments' => [
							'date'          => '2022-04-01',
							'productItemId' => 'gla_2',
						],
					],
					[
						'metrics'  => [ 'clicks' => 5 ],
						'segments' => [
							'date'          => '2022-04-02',
							'productItemId' => 'gla_1',
						],
					],
					[
						'metrics'  => [ 'clicks' => 5 ],
						'segments' => [
							'date'          => '2022-04-02',
							'productItemId' => 'gla_2',
						],
					],
				],
				'totals'    => [
					[
						'metrics'  => [ 'clicks' => 9 ],
						'segments' => [ 'productItemId' => 'gla_1' ],
					],
					[
						'metrics'  => [ 'clicks' => 6 ],
						'segments' => [ 'productItemId' => 'gla_2' ],
					],
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertSame( [ 'clicks' => 15 ], $result['totals'] );
		$this->assertEquals(
			[
				[
					'interval'  => '2022-04-01',
					'subtotals' => [ 'clicks' => 5 ],
				],
				[
					'interval'  => '2022-04-02',
					'subtotals' => [ 'clicks' => 10 ],
				],
			],
			$result['intervals']
		);
		$this->assertSame(
			"SELECT metrics.clicks,segments.product_item_id FROM shopping_performance_view WHERE segments.product_item_id IN ('gla_1','gla_2')",
			$this->ads_report_queries['totals']
		);
		$this->assertSame(
			"SELECT metrics.clicks,segments.date,segments.product_item_id FROM shopping_performance_view WHERE segments.product_item_id IN ('gla_1','gla_2')",
			$this->ads_report_queries['intervals']
		);
	}

	public function test_get_report_data_filtered_by_campaign_ids_selects_campaign_id() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
			'ids'      => [ '123', '456' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'totals' => [
					[ 'metrics' => [ 'clicks' => 9 ] ],
					[ 'metrics' => [ 'clicks' => 6 ] ],
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'campaigns', $report_args );

		$this->assertSame( [ 'clicks' => 15 ], $result['totals'] );
		$this->assertSame(
			"SELECT metrics.clicks,campaign.id FROM shopping_performance_view WHERE campaign.id IN ('123','456')",
			$this->ads_report_queries['totals']
		);
		$this->assertSame(
			"SELECT metrics.clicks,segments.date,campaign.id FROM shopping_performance_view WHERE campaign.id IN ('123','456')",
			$this->ads_report_queries['intervals']
		);
	}

	public function test_get_report_data_truncated_when_table_has_more_results() {
		$report_args = [
			'fields'   => [ 'clicks' ],
			'per_page' => 1,
		];

		$this->generate_ads_report_query_mock(
			[
				'table'         => [
					$this->product_row( 'gla_1', 'Product One', [ 'clicks' => 9 ] ),
				],
				'totals'        => [
					[ 'metrics' => [ 'clicks' => 15 ] ],
				],
				'total_results' => [
					'table'  => 2,
					'totals' => 1,
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertTrue( $result['truncated'] );
	}

	public function test_get_report_data_truncated_when_intervals_have_more_results() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'intervals'     => [
					$this->interval_row( 'day', '2022-04-01', [ 'clicks' => 3 ] ),
				],
				'total_results' => [
					'intervals' => 2,
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'campaigns', $report_args );

		$this->assertTrue( $result['truncated'] );
	}

	public function test_get_report_data_truncated_when_totals_have_more_results() {
		$report_args = [ 'fields' => [ 'clicks' ] ];

		$this->generate_ads_report_query_mock(
			[
				'totals'        => [
					[ 'metrics' => [ 'clicks' => 15 ] ],
				],
				'total_results' => [
					'totals' => 2,
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'campaigns', $report_args );

		$this->assertTrue( $result['truncated'] );
	}

	public function test_get_report_data_truncated_with_next_page() {
		$this->generate_ads_report_query_mock(
			[
				'table'         => [
					$this->campaign_row( 123, 'Campaign', [] ),
				],
				'next_page'     => 'abcdefg',
				'total_results' => [
					'table' => 2,
				],
			]
		);

		$result = $this->report->get_report_data( 'campaigns', [] );

		$this->assertSame( 'abcdefg', $result['next_page'] );
		$this->assertTrue( $result['truncated'] );
	}

	public function test_get_report_data_not_truncated_when_all_results_read() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
			'per_page' => 2,
		];

		$this->generate_ads_report_query_mock(
			[
				'table'         => [
					$this->product_row( 'gla_1', 'Product One', [ 'clicks' => 9 ] ),
					$this->product_row( 'gla_2', 'Product Two', [ 'clicks' => 6 ] ),
				],
				'intervals'     => [
					$this->interval_row( 'day', '2022-04-01', [ 'clicks' => 15 ] ),
				],
				'totals'        => [
					[ 'metrics' => [ 'clicks' => 15 ] ],
				],
				'total_results' => [
					'table'     => 2,
					'intervals' => 1,
					'totals'    => 1,
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertArrayNotHasKey( 'truncated', $result );
	}

	/**
	 * The Dashboard requests no interval, so only the table and totals queries run.
	 */
	public function test_get_report_data_no_intervals_query_without_interval() {
		$report_args = [ 'fields' => [ 'clicks' ] ];

		$this->generate_ads_report_query_mock(
			[
				'table'  => [
					$this->campaign_row( 123, 'Campaign', [ 'clicks' => 9 ] ),
				],
				'totals' => [
					[ 'metrics' => [ 'clicks' => 9 ] ],
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'campaigns', $report_args );

		$this->assertArrayNotHasKey( 'intervals', $result );
		$this->assertArrayNotHasKey( 'intervals', $this->ads_report_queries );
		$this->assertSame( 2, $this->ads_report_search_count );
	}

	public function test_get_report_data_is_cached_once_per_request() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'totals' => [
					[ 'metrics' => [ 'clicks' => 9 ] ],
				],
			],
			$report_args
		);

		$first = $this->report->get_report_data( 'campaigns', $report_args );
		$this->assertSame( 3, $this->ads_report_search_count );

		// The second identical request is served from the single cached transient.
		$second = $this->report->get_report_data( 'campaigns', $report_args );
		$this->assertSame( 3, $this->ads_report_search_count );
		$this->assertSame( $first, $second );
	}

	public function test_get_report_data_cache_is_not_shared_across_plugin_versions() {
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'totals' => [
					[ 'metrics' => [ 'clicks' => 9 ] ],
				],
			],
			$report_args
		);

		$this->report->get_report_data( 'campaigns', $report_args );
		$this->assertSame( 3, $this->ads_report_search_count );

		// The same report from a newer plugin version doesn't reuse the older cached result.
		$updated_report = $this->getMockBuilder( AdsReport::class )
			->setConstructorArgs( [ $this->client ] )
			->onlyMethods( [ 'get_version' ] )
			->getMock();
		$updated_report->method( 'get_version' )->willReturn( '999.0.0' );
		$updated_report->set_options_object( $this->options );
		$updated_report->set_container( $this->container );

		$updated_report->get_report_data( 'campaigns', $report_args );
		$this->assertSame( 6, $this->ads_report_search_count );
	}

	public function test_get_report_data_unsorted_segments() {
		$report_args = [
			'interval' => 'month',
			'fields'   => [ 'clicks' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'intervals' => [
					$this->interval_row( 'month', '2022-02', [ 'clicks' => 943 ] ),
					$this->interval_row( 'month', '2022-04', [ 'clicks' => 368 ] ),
					$this->interval_row( 'month', '2022-01', [ 'clicks' => 489 ] ),
				],
			],
			$report_args
		);

		$result = $this->report->get_report_data( 'products', $report_args );

		$this->assertSame(
			[ '2022-01', '2022-02', '2022-04' ],
			array_column( $result['intervals'], 'interval' )
		);
	}

	/**
	 * @dataProvider interval_segments_provider
	 *
	 * @param string $interval Interval type.
	 * @param string $segment  Segment value returned by Google Ads.
	 * @param string $expected Expected interval ID.
	 */
	public function test_get_report_data_interval_ids( string $interval, string $segment, string $expected ) {
		$report_args = [
			'interval' => $interval,
			'fields'   => [ 'clicks' ],
		];

		$this->generate_ads_report_query_mock(
			[
				'intervals' => [
					$this->interval_row( $interval, $segment, [ 'clicks' => 734 ] ),
				],
			],
			$report_args
		);

		$this->assertEquals(
			[
				[
					'interval'  => $expected,
					'subtotals' => [ 'clicks' => 734 ],
				],
			],
			$this->report->get_report_data( 'products', $report_args )['intervals']
		);
	}

	public function interval_segments_provider(): array {
		return [
			'week'    => [ 'week', '2021-12-12', '2021-49' ], // Forty-ninth week of 2021.
			'quarter' => [ 'quarter', '2021-12', '2021-4' ], // Fourth quarter of 2021.
			'year'    => [ 'year', '2022', '2022' ],
		];
	}

	public function test_get_report_data_invalid_interval() {
		$report_args = [
			'interval' => 'unknown',
		];

		$this->generate_ads_report_query_mock( [], $report_args );

		try {
			$this->report->get_report_data( 'products', $report_args );
			$this->fail( 'Expected an InvalidValue exception.' );
		} catch ( InvalidValue $e ) {
			$this->assertSame( 'The value of unknown must be either of [day, week, month, quarter, year].', $e->getMessage() );
		}

		// The interval is validated before any Google Ads query runs.
		$this->assertSame( 0, $this->ads_report_search_count );
	}

	public function test_get_report_data_no_fields() {
		$report_args = [ 'interval' => 'day' ];

		$this->generate_ads_report_query_mock(
			[
				'table'     => [
					$this->campaign_row( 1234567890, 'First Campaign', [] ),
					$this->campaign_row( 2345678901, 'Second Campaign', [] ),
				],
				'intervals' => [
					$this->interval_row( 'day', '2022-04-01', [] ),
				],
			],
			$report_args
		);

		$this->assertEquals(
			[
				'campaigns' => [
					[
						'id'          => 1234567890,
						'name'        => 'First Campaign',
						'status'      => 'enabled',
						'subtotals'   => [],
						'isConverted' => false,
					],
					[
						'id'          => 2345678901,
						'name'        => 'Second Campaign',
						'status'      => 'enabled',
						'subtotals'   => [],
						'isConverted' => false,
					],
				],
				'intervals' => [
					[
						'interval'  => '2022-04-01',
						'subtotals' => [],
					],
				],
			],
			$this->report->get_report_data( 'campaigns', $report_args )
		);

		// No fields means no totals, so the totals query is skipped.
		$this->assertArrayNotHasKey( 'totals', $this->ads_report_queries );
	}

	public function test_get_report_data_has_another_page() {
		$next_page = 'abcdefg';

		$this->generate_ads_report_query_mock( [ 'next_page' => $next_page ] );
		$this->assertEquals(
			[
				'next_page' => $next_page,
			],
			$this->report->get_report_data( 'campaigns', [] )
		);
	}

	public function test_get_report_data_exception() {
		$this->generate_ads_query_mock_exception( new ApiException( 'not found', 5, 'NOT_FOUND' ) );

		try {
			$this->report->get_report_data( 'campaigns', [] );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message'           => 'Unable to retrieve report data: not found',
					'errors'            => [ 'NOT_FOUND' => 'not found' ],
					'report_type'       => 'campaigns',
					'report_query_args' => [],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 404, $e->getCode() );
		}
	}

	public function test_get_report_data_converted_campaigns() {
		$report_args = [
			'fields' => [ 'clicks' ],
		];

		$this->ads_campaign
			->method( 'get_campaign_convert_status' )
			->willReturn( 'converted' );

		$this->generate_ads_report_query_mock(
			[
				'table'  => [
					$this->campaign_row( 1234567890, 'Test Campaign', [ 'clicks' => 12 ], 'REMOVED', 'SHOPPING' ),
					$this->campaign_row( 2345678901, 'PMax: Test Campaign', [ 'clicks' => 58 ] ),
				],
				'totals' => [
					[ 'metrics' => [ 'clicks' => 70 ] ],
				],
			],
			$report_args
		);

		$this->assertEquals(
			[
				'campaigns' => [
					[
						'id'          => 1234567890,
						'name'        => 'Test Campaign',
						'status'      => 'removed',
						'isConverted' => true,
						'subtotals'   => [
							'clicks' => 12,
						],
					],
					[
						'id'          => 2345678901,
						'name'        => 'PMax: Test Campaign',
						'status'      => 'enabled',
						'isConverted' => false,
						'subtotals'   => [
							'clicks' => 58,
						],
					],
				],
				'totals'    => [
					'clicks' => 70,
				],
			],
			$this->report->get_report_data( 'campaigns', $report_args )
		);
	}
}
