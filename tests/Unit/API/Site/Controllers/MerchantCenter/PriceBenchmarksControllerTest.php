<?php
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PriceBenchmarksController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

/**
 * Test class for PriceBenchmarksController.
 *
 * @group price-benchmarks
 */
class PriceBenchmarksControllerTest extends RESTControllerUnitTest {

	/** @var PriceBenchmarksController */
	protected $controller;

	/** @var MockObject|MerchantPriceBenchmarks */
	protected $merchant_price_benchmarks;

	/** @var Container */
	protected $container;

	protected const ROUTE_PRICE_BENCHMARKS = '/wc/gla/mc/price-benchmarks';

	public function setUp(): void {
		parent::setUp();

		$this->merchant_price_benchmarks = $this->createMock( MerchantPriceBenchmarks::class );

		// Mock the container to return the mocked MerchantPriceBenchmarks.
		$this->container = new Container();
		$this->container->addShared( MerchantPriceBenchmarks::class, $this->merchant_price_benchmarks );

		// Initialize the controller.
		$this->controller = new PriceBenchmarksController( $this->server );
		$this->controller->set_container( $this->container );
		$this->controller->register();
	}

	public function test_get_price_benchmarks() {
		$product_id    = '123456';
		$product_title = "UGG Women's s Classic Mini";

		// Mock the benchmark data.
		$mock_benchmark_data = [
			'results'         => [
				[
					'id'                            => 'online:en:US:gla_' . $product_id,
					'offer_id'                      => $product_id,
					'title'                         => $product_title,
					'price_micros'                  => '124990000',
					'currency_code'                 => 'USD',
					'benchmark_price_micros'        => '119922291',
					'benchmark_price_currency_code' => 'USD',
				],
			],
			'next_page_token' => 'next_page_token',
		];

		// Mock the price insights data.
		$mock_price_insights_data = [
			'results'         => [
				[

					'id'                               => 'online:en:US:gla_' . $product_id,
					'offer_id'                         => $product_id,
					'price_micros'                     => '124990000',
					'currency_code'                    => 'USD',
					'suggested_price_micros'           => '118990000',
					'suggested_price_currency_code'    => 'US',
					'predicted_impressions_change_fraction' => '0.12609300017356873',
					'predicted_clicks_change_fraction' => '0.508745014667511',
					'predicted_conversions_change_fraction' => '2.3431060314178467',
					'effectiveness'                    => 3,
				],
			],
			'next_page_token' => 'next_page_token',
		];

		// Configure the mocked methods.
		$this->merchant_price_benchmarks->expects( $this->once() )
			->method( 'get_benchmark_data' )
			->willReturn( $mock_benchmark_data );

		$this->merchant_price_benchmarks->expects( $this->once() )
			->method( 'get_price_insights' )
			->willReturn( $mock_price_insights_data );

		// Simulate a GET request.
		$response = $this->do_request( self::ROUTE_PRICE_BENCHMARKS, 'GET' );

		// Expected shape once data from the two queries are stitched together.
		$expected = [
			[
				'product'         => [
					'id'        => (int) $product_id,
					'thumbnail' => '', // The thumbnail URL of the ID.
					'title'     => $product_title,
				],
				'effectiveness'   => 3,
				'regular_price'   => 124.99,
				'price_on_google' => 119.92, // Converted from micros.
				'price_gap'       => 5.07, // Reg price - Price on Google, Converted from micros.
				'suggested_price' => 118.99, // Converted from micros.
			],
		];

		// Assert the response status.
		$this->assertEquals( 200, $response->get_status() );

		// The expected shape should pass once the implementation is updated.
		$this->assertSameSets( $expected, $response->get_data(), 'The response data should match the expected structure.' );
	}
}
