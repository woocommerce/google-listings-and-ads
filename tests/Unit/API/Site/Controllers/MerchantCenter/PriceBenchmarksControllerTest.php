<?php
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PriceBenchmarksController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
/**
 * Test class for PriceBenchmarksController.
 *
 * @group price-benchmarks
 */
class PriceBenchmarksControllerTest extends RESTControllerUnitTest {

	use GoogleAdsClientTrait;

	/** @var PriceBenchmarksController */
	protected $controller;

	/** @var MockObject MerchantPriceBenchmarks */
	protected $merchant_price_benchmarks;

	/** @var MockObject PriceBenchmarks */
	protected $price_benchmarks;

	/** @var Container */
	protected $container;

	protected const ROUTE_PRICE_BENCHMARKS = '/wc/gla/mc/price-benchmarks';

	protected const ROUTE_PRICE_BENCHMARKS_SUMMARY = '/wc/gla/mc/price-benchmarks/summary';

	protected const TEST_PRODUCT_ID = 123456;

	public function setUp(): void {
		parent::setUp();

		$this->merchant_price_benchmarks = $this->createMock( MerchantPriceBenchmarks::class );

		// Mock the container to return the mocked MerchantPriceBenchmarks.
		$this->container = new Container();
		$this->container->addShared( MerchantPriceBenchmarks::class, $this->merchant_price_benchmarks );

		$this->price_benchmarks = $this->createMock( PriceBenchmarks::class );
		$this->container->addShared( PriceBenchmarks::class, $this->price_benchmarks );

		// Initialize the controller.
		$this->controller = new PriceBenchmarksController( $this->server );
		$this->controller->set_container( $this->container );
		$this->controller->register();
	}

	/**
	 * Tests the '/mc/price-benchmarks/summary' endpoint.
	 */
	public function test_get_price_benchmarks_summary() {
		// Mock the benchmark summary data.
		$mock_benchmark_summary_data = [
			'total_products' => 0,
			'price_unknown'  => 0,
			'price_lower'    => 0,
			'price_similar'  => 0,
			'price_higher'   => 0,
		];

		// Configure the mocked methods.
		$this->price_benchmarks->expects( $this->once() )
			->method( 'get_summary' )
			->willReturn( $mock_benchmark_summary_data );

		// Simulate a GET request.
		$response = $this->do_request( self::ROUTE_PRICE_BENCHMARKS_SUMMARY, 'GET' );

		// Assert the response status.
		$this->assertEquals( 200, $response->get_status() );

		// Verify that the response data matches the expected structure.
		$this->assertSameSets( $mock_benchmark_summary_data, $response->get_data(), 'The response data should match the expected structure.' );
	}

	/**
	 * Tests the '/mc/price-benchmarks' endpoint.
	 */
	public function test_get_price_benchmarks_collection() {
		// Mock the benchmark data.
		$mock_benchmark_data = $this->get_mock_price_benchmark_results();

		// Configure the mocked methods.
		$this->price_benchmarks->expects( $this->once() )
			->method( 'get_price_benchmarks_data' )
			->willReturn( $mock_benchmark_data );

		// Simulate a GET request.
		$response = $this->do_request( self::ROUTE_PRICE_BENCHMARKS, 'GET' );

		// Assert the response status.
		$this->assertEquals( 200, $response->get_status() );

		// The expected shape should pass once the implementation is updated.
		$this->assertSameSets( $mock_benchmark_data, $response->get_data(), 'The response data should match the expected structure.' );
	}

	/**
	 * Tests the '/mc/price-benchmarks/{id}' endpoint.
	 */
	public function test_get_price_benchmarks_item() {
		// Mock the benchmark data.
		$mock_benchmark_data = $this->get_mock_price_benchmark_results();

		$method_args = [
			'include' => [ self::TEST_PRODUCT_ID ],
		];

		// Configure the mocked methods.
		$this->price_benchmarks->expects( $this->once() )
			->method( 'get_price_benchmarks_data' )
			->with( $method_args )
			->willReturn( $mock_benchmark_data );

		// Simulate a GET request.
		$response = $this->do_request( self::ROUTE_PRICE_BENCHMARKS . '/' . self::TEST_PRODUCT_ID );

		// Assert the response status.
		$this->assertEquals( 200, $response->get_status() );

		// The expected shape should pass once the implementation is updated.
		$this->assertSameSets( $mock_benchmark_data, $response->get_data(), 'The response data should match the expected structure.' );
	}

	/**
	 * Get mock price benchmark results.
	 *
	 * @param string $program_id The program ID.
	 * @return array The mock price benchmark results.
	 */
	private function get_mock_price_benchmark_results( $program_id = self::TEST_PRODUCT_ID ) {
		return [
			[
				'results' => [
					[
						'product'                       => [
							'id'        => $program_id,
							'thumbnail' => '', // The thumbnail URL of the ID.
							'title'     => 'Example Product Title',
						],
						'effectiveness'                 => 3,
						'country_code'                  => 'US',
						'currency_code'                 => 'USD',
						'product_price'                 => 28.55,
						'benchmark_price'               => 17.81,
						'benchmark_price_currency_code' => 'USD',
						'price_gap'                     => 10.74,
						'suggested_price'               => 124.87,
						'suggested_price_currency_code' => 'USD',
						'predicted_impressions_change'  => 0.190123,
						'predicted_clicks_change'       => 0.601235,
						'predicted_conversions_change'  => 2.276543,
						'clicks'                        => 0,
						'impressions'                   => 0,
						'ctr'                           => 0,
						'conversions'                   => 0,
						'price_compared_with_benchmark' => 3,
					],
				],
				'total'   => 1,
			],
		];
	}
}
