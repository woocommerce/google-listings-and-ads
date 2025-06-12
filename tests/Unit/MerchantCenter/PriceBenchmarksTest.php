<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantPriceBenchmarksQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantPriceBenchmarksTable;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class PriceBenchmarksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 */
class PriceBenchmarksTest extends UnitTest {

	/** @var Container $container */
	protected $container;

	/** @var PriceBenchmarks */
	protected $price_benchmarks;

	/** @var MockObject|MerchantPriceBenchmarks */
	protected $price_benchmarks_api_middleware;

	/** @var MockObject|MerchantPriceBenchmarksQuery */
	protected $price_benchmark_query;

	/** @var  MerchantPriceBenchmarksTable */
	protected static $price_benchmarks_table;

	/** @var array */
	protected static $test_products = [];

	/** @var array */
	protected static $test_data = [];

	/**
	 * Set up shared fixtures once before all tests run.
	 */
	public static function set_up_before_class() {
		global $wpdb;

		parent::set_up_before_class();

		self::$price_benchmarks_table = new MerchantPriceBenchmarksTable( new WP(), $wpdb );

		if ( self::$price_benchmarks_table->exists() ) {
			self::$price_benchmarks_table->truncate();
		} else {
			self::$price_benchmarks_table->install();
		}

		self::$test_data = [
			[
				'product_id'                             => 101,
				'mc_product_id'                          => 'gla_101',
				'mc_product_offer_id'                    => 'offer_101',
				'mc_price_country_code'                  => 'US',
				'mc_product_currency_code'               => 'USD',
				'mc_product_price_micros'                => 19.99,
				'mc_price_benchmark_price_micros'        => 18.99,
				'mc_price_benchmark_price_currency_code' => 'USD',
				'mc_insights_suggested_price_micros'     => 17.99,
				'mc_insights_suggested_price_currency_code' => 'USD',
				'mc_insights_predicted_impressions_change_fraction' => '0.12',
				'mc_insights_predicted_clicks_change_fraction' => '0.21',
				'mc_insights_predicted_conversions_change_fraction' => '0.13',
				'mc_insights_effectiveness'              => 2,
				'mc_metrics_clicks'                      => 150,
				'mc_metrics_impressions'                 => 1500,
				'mc_metrics_ctr'                         => 0.1,
				'mc_metrics_conversions'                 => 10,
				'price_compared_with_benchmark'          => 3,
			],
			[
				'product_id'                             => 102,
				'mc_product_id'                          => 'gla_102',
				'mc_product_offer_id'                    => 'offer_102',
				'mc_price_country_code'                  => 'US',
				'mc_product_currency_code'               => 'USD',
				'mc_product_price_micros'                => 25.99,
				'mc_price_benchmark_price_micros'        => 25.99,
				'mc_price_benchmark_price_currency_code' => 'USD',
				'mc_insights_suggested_price_micros'     => 24.99,
				'mc_insights_suggested_price_currency_code' => 'USD',
				'mc_insights_predicted_impressions_change_fraction' => '0.05',
				'mc_insights_predicted_clicks_change_fraction' => '0.10',
				'mc_insights_predicted_conversions_change_fraction' => '0.08',
				'mc_insights_effectiveness'              => 1,
				'mc_metrics_clicks'                      => 80,
				'mc_metrics_impressions'                 => 1000,
				'mc_metrics_ctr'                         => 0.08,
				'mc_metrics_conversions'                 => 5,
				'price_compared_with_benchmark'          => 2,
			],
			[
				'product_id'                             => 103,
				'mc_product_id'                          => 'gla_103',
				'mc_product_offer_id'                    => 'offer_103',
				'mc_price_country_code'                  => 'US',
				'mc_product_currency_code'               => 'USD',
				'mc_product_price_micros'                => 15.99,
				'mc_price_benchmark_price_micros'        => 17.99,
				'mc_price_benchmark_price_currency_code' => 'USD',
				'mc_insights_suggested_price_micros'     => 16.99,
				'mc_insights_suggested_price_currency_code' => 'USD',
				'mc_insights_predicted_impressions_change_fraction' => '0.15',
				'mc_insights_predicted_clicks_change_fraction' => '0.20',
				'mc_insights_predicted_conversions_change_fraction' => '0.18',
				'mc_insights_effectiveness'              => 3,
				'mc_metrics_clicks'                      => 200,
				'mc_metrics_impressions'                 => 2000,
				'mc_metrics_ctr'                         => 0.1,
				'mc_metrics_conversions'                 => 15,
				'price_compared_with_benchmark'          => 1,
			],
		];
	}

	/**
	 * Set up the test
	 */
	public function setUp(): void {
		global $wpdb;

		parent::setUp();

		$this->container = new Container();

		// Set up the price benchmarks class with our mock
		$this->price_benchmarks                = new PriceBenchmarks();
		$this->price_benchmark_query           = new MerchantPriceBenchmarksQuery( $wpdb, self::$price_benchmarks_table );
		$this->price_benchmarks_api_middleware = $this->createMock( MerchantPriceBenchmarks::class );

		$this->container->addShared( MerchantPriceBenchmarksQuery::class, $this->price_benchmark_query );
		$this->container->addShared( MerchantPriceBenchmarks::class, $this->price_benchmarks_api_middleware );
		$this->price_benchmarks->set_container( $this->container );

		// Set up test data
		$this->populate_test_data();
	}

	/**
	 * Populates test data for use in tests.
	 */
	protected function populate_test_data(): void {
		foreach ( self::$test_data as $data ) {
			$this->price_benchmark_query->insert( $data );
		}
	}

	/**
	 * Ensure test data is installed correctly.
	 */
	public function test_data_installed(): void {
		// Check if the test data is installed correctly
		$results = $this->price_benchmark_query->get_results();
		$this->assertCount( 3, $results );
	}

	/**
	 * Test getting price benchmarks data with default arguments.
	 */
	public function test_get_price_benchmarks_data_default(): void {
		// Call the method with default arguments
		$result = $this->price_benchmarks->get_price_benchmarks_data();

		// Assert expectations
		$this->assertCount( 3, $result['results'] );
		$this->assertEquals( 3, $result['total'] );

		// Check that the first result has the expected shape
		$first_result = $result['results'][0];

		// Verify product structure
		$this->assertArrayHasKey( 'product', $first_result );
		$this->assertArrayHasKey( 'id', $first_result['product'] );
		$this->assertArrayHasKey( 'thumbnail', $first_result['product'] );
		$this->assertArrayHasKey( 'title', $first_result['product'] );

		// Verify price data
		$this->assertArrayHasKey( 'currency_code', $first_result );
		$this->assertArrayHasKey( 'country_code', $first_result );
		$this->assertArrayHasKey( 'product_price', $first_result );
		$this->assertArrayHasKey( 'benchmark_price', $first_result );
		$this->assertArrayHasKey( 'benchmark_price_currency_code', $first_result );
		$this->assertArrayHasKey( 'price_gap', $first_result );
		$this->assertArrayHasKey( 'suggested_price', $first_result );
		$this->assertArrayHasKey( 'suggested_price_currency_code', $first_result );
		$this->assertArrayHasKey( 'price_compared_with_benchmark', $first_result );

		// Verify insights
		$this->assertArrayHasKey( 'effectiveness', $first_result );
		$this->assertArrayHasKey( 'predicted_impressions_change', $first_result );
		$this->assertArrayHasKey( 'predicted_clicks_change', $first_result );
		$this->assertArrayHasKey( 'predicted_conversions_change', $first_result );

		// Verify metrics
		$this->assertArrayHasKey( 'clicks', $first_result );
		$this->assertArrayHasKey( 'impressions', $first_result );
		$this->assertArrayHasKey( 'ctr', $first_result );
		$this->assertArrayHasKey( 'conversions', $first_result );

		// Verify value types.
		$this->assertIsInt( $first_result['product']['id'] );
		$this->assertIsFloat( $first_result['product_price'] );
		$this->assertIsFloat( $first_result['benchmark_price'] );
		$this->assertIsFloat( $first_result['price_gap'] );
		$this->assertIsInt( $first_result['effectiveness'] );
		$this->assertIsInt( $first_result['price_compared_with_benchmark'] );
	}

	/**
	 * Test getting price benchmarks data with product ID filtering.
	 */
	public function test_get_price_benchmarks_data_with_include(): void {
		// Call the method with include argument
		$result = $this->price_benchmarks->get_price_benchmarks_data(
			[
				'include' => [ 102 ],
			]
		);

		// Assert expectations.
		$this->assertCount( 1, $result['results'] );
		$this->assertEquals( 1, $result['total'] );
		$this->assertEquals( 102, $result['results'][0]['product']['id'] );
	}

	/**
	 * Test getting price benchmarks data with custom pagination.
	 */
	public function test_get_price_benchmarks_data_with_pagination(): void {
		// Call the method with pagination arguments for page 2 of 2.
		$result = $this->price_benchmarks->get_price_benchmarks_data(
			[
				'page'     => 2,
				'per_page' => 2,
			]
		);

		// Assert expectations.
		$this->assertCount( 1, $result['results'] );
		$this->assertEquals( 3, $result['total'] );  // Total should still be 3.
		$this->assertEquals( 102, $result['results'][0]['product']['id'] ); // ID 102 has the lowest effectiveness.
	}

	/**
	 * Test getting price benchmarks data with custom ordering.
	 *
	 * @dataProvider data_order_scenarios
	 *
	 * @param string $orderby
	 * @param string $order
	 * @param array  $expected_ids
	 */
	public function test_get_price_benchmarks_data_with_custom_order( $orderby, $order, $expected_ids ): void {
		// Call the method with custom ordering
		$result = $this->price_benchmarks->get_price_benchmarks_data(
			[
				'orderby' => $orderby,
				'order'   => $order,
			]
		);

		// Assert expectations.
		$this->assertCount( 3, $result['results'] );
		$this->assertSame(
			$expected_ids,
			array_map(
				function ( $item ) {
					return $item['product']['id'];
				},
				$result['results']
			)
		);
	}

	/**
	 * Data provider for test_get_price_benchmarks_data_with_custom_order.
	 *
	 * @return array
	 */
	public function data_order_scenarios(): array {
		return [
			'id asc'               => [ 'id', 'asc', [ 101, 102, 103 ] ],
			'id desc'              => [ 'id', 'desc', [ 103, 102, 101 ] ],
			'effectiveness asc'    => [ 'effectiveness', 'asc', [ 102, 101, 103 ] ],
			'effectiveness desc'   => [ 'effectiveness', 'desc', [ 103, 101, 102 ] ],
			'product_price asc'    => [ 'product_price', 'asc', [ 103, 101, 102 ] ],
			'product_price desc'   => [ 'product_price', 'desc', [ 102, 101, 103 ] ],
			'benchmark_price asc'  => [ 'benchmark_price', 'asc', [ 103, 101, 102 ] ],
			'benchmark_price desc' => [ 'benchmark_price', 'desc', [ 102, 101, 103 ] ],
			'suggested_price asc'  => [ 'suggested_price', 'asc', [ 103, 101, 102 ] ],
			'suggested_price desc' => [ 'suggested_price', 'desc', [ 102, 101, 103 ] ],
			'unsupported_key asc'  => [ 'unsupported', 'asc', [ 102, 101, 103 ] ], // Unsupported key should return in order of effectiveness.
			'unsupported_key desc' => [ 'unsupported', 'desc', [ 103, 101, 102 ] ], // Unsupported key should return in order of effectiveness.
		];
	}

	/**
	 * Test getting price benchmarks data with search filter.
	 */
	public function test_get_price_benchmarks_data_with_search(): void {
		// Create a sample product and set a unique title.
		$test_product = WC_Helper_Product::create_simple_product();
		$test_product->set_name( 'Sample test name' );
		$test_product->save();

		// Insert a row into the price benchmarks database with sample data.
		$this->price_benchmark_query->insert(
			[
				'product_id'                             => $test_product->get_id(),
				'mc_product_id'                          => 'gla_' . $test_product->get_id(),
				'mc_product_offer_id'                    => 'offer_' . $test_product->get_id(),
				'mc_price_country_code'                  => 'US',
				'mc_product_currency_code'               => 'USD',
				'mc_product_price_micros'                => 29.99,
				'mc_price_benchmark_price_micros'        => 28.99,
				'mc_price_benchmark_price_currency_code' => 'USD',
				'mc_insights_suggested_price_micros'     => 27.99,
				'mc_insights_suggested_price_currency_code' => 'USD',
				'mc_insights_predicted_impressions_change_fraction' => '0.10',
				'mc_insights_predicted_clicks_change_fraction' => '0.15',
				'mc_insights_predicted_conversions_change_fraction' => '0.12',
				'mc_insights_effectiveness'              => 2,
				'mc_metrics_clicks'                      => 120,
				'mc_metrics_impressions'                 => 1200,
				'mc_metrics_ctr'                         => 0.1,
				'mc_metrics_conversions'                 => 8,
				'price_compared_with_benchmark'          => 3,
			]
		);

		// Call the method with search argument.
		$result = $this->price_benchmarks->get_price_benchmarks_data(
			[
				'search' => $test_product->get_name(),
			]
		);

		// Assert expectations.
		$this->assertCount( 1, $result['results'] );
		$this->assertEquals( 1, $result['total'] );
		$this->assertEquals( $test_product->get_id(), $result['results'][0]['product']['id'] );
	}

	/**
	 * Test getting price benchmarks data with search filter.
	 */
	public function test_get_price_benchmarks_data_with_search_returns_null(): void {
		// Call the method with search argument.
		$result = $this->price_benchmarks->get_price_benchmarks_data(
			[
				'search' => 'search with no results',
			]
		);

		// Assert expectations.
		$this->assertCount( 0, $result['results'] );
		$this->assertEquals( 0, $result['total'] );
	}
}
