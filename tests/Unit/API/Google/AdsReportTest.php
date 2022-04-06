<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsReportTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsReport                   $report
 */
class AdsReportTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_ADS_ID = 1234567890;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );

		$this->report = new AdsReport( $this->client );
		$this->report->set_options_object( $this->options );
	}

	public function test_get_report_data_campaigns() {
		$report_type = 'campaigns';
		$report_args = [
			'interval' => 'day',
			'fields'   => [ 'clicks', 'impressions', 'spend', 'sales', 'conversions' ],
		];
		$report_data = [
			[
				'campaign' => [
					'status' => 'ENABLED',
					'name'   => 'First Campaign',
					'id'     => 1234567890,
				],
				'metrics'  => [
					'clicks'           => 12,
					'conversions'      => 1,
					'conversionsValue' => 20,
					'costMicros'       => 6650000,
					'impressions'      => 1987,
				],
				'segments' => [
					'date' => '2022-04-01',
				],
			],
			[
				'campaign' => [
					'status' => 'ENABLED',
					'name'   => 'Second Campaign',
					'id'     => 2345678901,
				],
				'metrics'  => [
					'clicks'           => 58,
					'conversions'      => 4,
					'conversionsValue' => 86,
					'costMicros'       => 21600000,
					'impressions'      => 4823,
				],
				'segments' => [
					'date' => '2022-04-01',
				],
			],
			[
				'campaign' => [
					'status' => 'ENABLED',
					'name'   => 'First Campaign',
					'id'     => 1234567890,
				],
				'metrics'  => [
					'clicks'           => 16,
					'conversions'      => 2,
					'conversionsValue' => 18,
					'costMicros'       => 8120000,
					'impressions'      => 378,
				],
				'segments' => [
					'date' => '2022-04-02',
				],
			],
		];

		$expected = [
			$report_type => [
				[
					'id'        => 1234567890,
					'name'      => 'First Campaign',
					'status'    => 'enabled',
					'subtotals' => [
						'clicks'      => 28,
						'impressions' => 2365,
						'spend'       => 14.77,
						'sales'       => 38,
						'conversions' => 3,
					],
				],
				[
					'id'        => 2345678901,
					'name'      => 'Second Campaign',
					'status'    => 'enabled',
					'subtotals' => [
						'clicks'      => 58,
						'impressions' => 4823,
						'spend'       => 21.6,
						'sales'       => 86,
						'conversions' => 4,
					],
				],
			],
			'intervals'  => [
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
			'totals'     => [
				'clicks'      => 86,
				'impressions' => 7188,
				'spend'       => 36.37,
				'sales'       => 124,
				'conversions' => 7,
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_products() {
		$report_type = 'products';
		$report_args = [
			'interval' => 'month',
			'fields'   => [ 'clicks', 'impressions' ],
		];
		$report_data = [
			[
				'metrics'  => [
					'clicks'      => 34,
					'impressions' => 2754,
				],
				'segments' => [
					'month'         => '2022-01',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
			[
				'metrics'  => [
					'clicks'      => 11,
					'impressions' => 4259,
				],
				'segments' => [
					'month'         => '2022-01',
					'productItemId' => 'gla_456',
					'productTitle'  => 'Product Two',
				],
			],
			[
				'metrics'  => [
					'clicks'      => 189,
					'impressions' => 7865,
				],
				'segments' => [
					'month'         => '2022-02',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
		];

		$expected = [
			$report_type => [
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
			'intervals'  => [
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
			'totals'     => [
				'clicks'      => 234,
				'impressions' => 14878,
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_unsorted_segments() {
		$report_type = 'products';
		$report_args = [
			'interval' => 'month',
			'fields'   => [ 'clicks' ],
		];
		$report_data = [
			[
				'metrics'  => [
					'clicks' => 943,
				],
				'segments' => [
					'month'         => '2022-02',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
			[
				'metrics'  => [
					'clicks' => 368,
				],
				'segments' => [
					'month'         => '2022-04',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
			[
				'metrics'  => [
					'clicks' => 489,
				],
				'segments' => [
					'month'         => '2022-01',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
		];

		$expected = [
			$report_type => [
				[
					'id'        => 'gla_123',
					'name'      => 'Product One',
					'subtotals' => [
						'clicks' => 1800,
					],
				],
			],
			'intervals'  => [
				[
					'interval'  => '2022-01',
					'subtotals' => [
						'clicks' => 489,
					],
				],
				[
					'interval'  => '2022-02',
					'subtotals' => [
						'clicks' => 943,
					],
				],
				[
					'interval'  => '2022-04',
					'subtotals' => [
						'clicks' => 368,
					],
				],
			],
			'totals'     => [
				'clicks' => 1800,
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_week() {
		$report_type = 'products';
		$report_args = [
			'interval' => 'week',
			'fields'   => [ 'clicks' ],
		];
		$report_data = [
			[
				'metrics'  => [
					'clicks' => 734,
				],
				'segments' => [
					'week'          => '2021-12-12',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
		];

		$expected = [
			$report_type => [
				[
					'id'        => 'gla_123',
					'name'      => 'Product One',
					'subtotals' => [
						'clicks' => 734,
					],
				],
			],
			'intervals'  => [
				[
					'interval'  => '2021-49', // Fourty-ninth week of 2021.
					'subtotals' => [
						'clicks' => 734,
					],
				],
			],
			'totals'     => [
				'clicks' => 734,
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_quarter() {
		$report_type = 'products';
		$report_args = [
			'interval' => 'quarter',
			'fields'   => [ 'clicks' ],
		];
		$report_data = [
			[
				'metrics'  => [
					'clicks' => 734,
				],
				'segments' => [
					'quarter'       => '2021-12', // December of 2021.
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
		];

		$expected = [
			$report_type => [
				[
					'id'        => 'gla_123',
					'name'      => 'Product One',
					'subtotals' => [
						'clicks' => 734,
					],
				],
			],
			'intervals'  => [
				[
					'interval'  => '2021-4', // Fourth quarter of 2021.
					'subtotals' => [
						'clicks' => 734,
					],
				],
			],
			'totals'     => [
				'clicks' => 734,
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_year() {
		$report_type = 'products';
		$report_args = [
			'interval' => 'year',
			'fields'   => [ 'clicks' ],
		];
		$report_data = [
			[
				'metrics'  => [
					'clicks' => 734,
				],
				'segments' => [
					'year'          => '2022',
					'productItemId' => 'gla_123',
					'productTitle'  => 'Product One',
				],
			],
		];

		$expected = [
			$report_type => [
				[
					'id'        => 'gla_123',
					'name'      => 'Product One',
					'subtotals' => [
						'clicks' => 734,
					],
				],
			],
			'intervals'  => [
				[
					'interval'  => '2022',
					'subtotals' => [
						'clicks' => 734,
					],
				],
			],
			'totals'     => [
				'clicks' => 734,
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_invalid_interval() {
		$report_type = 'products';
		$report_args = [
			'interval' => 'unknown',
		];
		$report_data = [
			[
				'metrics'  => [
					'clicks' => 734,
				],
				'segments' => [
					'day' => '2022-01-01',
				],
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'The value of unknown must be either of [day, week, month, quarter, year].' );

		$this->report->get_report_data( $report_type, $report_args );
	}

	public function test_get_report_data_no_fields() {
		$report_type = 'campaigns';
		$report_args = [ 'interval' => 'day' ];
		$report_data = [
			[
				'campaign' => [
					'status' => 'ENABLED',
					'name'   => 'First Campaign',
					'id'     => 1234567890,
				],
				'segments' => [
					'date' => '2022-04-01',
				],
			],
			[
				'campaign' => [
					'status' => 'ENABLED',
					'name'   => 'Second Campaign',
					'id'     => 2345678901,
				],
				'segments' => [
					'date' => '2022-04-01',
				],
			],
		];

		$expected = [
			$report_type => [
				[
					'id'        => 1234567890,
					'name'      => 'First Campaign',
					'status'    => 'enabled',
					'subtotals' => [],
				],
				[
					'id'        => 2345678901,
					'name'      => 'Second Campaign',
					'status'    => 'enabled',
					'subtotals' => [],
				],
			],
			'intervals'  => [
				[
					'interval'  => '2022-04-01',
					'subtotals' => [],
				],
			],
		];

		$this->generate_ads_report_query_mock( $report_data, $report_args );
		$this->assertEquals(
			$expected,
			$this->report->get_report_data( $report_type, $report_args )
		);
	}

	public function test_get_report_data_has_another_page() {
		$next_page = 'abcdefg';

		$this->generate_ads_report_query_mock( [], [], $next_page );
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

}
