<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\BudgetMetricsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class BudgetMetricsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class BudgetMetricsControllerTest extends RESTControllerUnitTest {

	/** @var Container $container */
	protected $container;

	/** @var MockObject|Ads $ads */
	protected $ads;

	/** @var MockObject|BudgetMetrics $budget_metrics */
	protected $budget_metrics;

	/** @var MockObject|ISO3166DataProvider $iso_provider */
	protected $iso_provider;

	/** @var BudgetMetricsController $controller */
	protected $controller;

	protected const ROUTE_BUDGET_METRICS = '/wc/gla/ads/campaigns/budget-metrics';
	protected const METRICS_DATA         = [
		'cost'              => 84,
		'conversions'       => 6.1,
		'conversions_value' => 243.88,
	];

	public function setUp(): void {
		parent::setUp();

		$this->iso_provider   = $this->createMock( ISO3166DataProvider::class );
		$this->ads            = $this->createMock( Ads::class );
		$this->budget_metrics = $this->createMock( BudgetMetrics::class );

		$this->container = new Container();
		$this->container->addShared( BudgetMetrics::class, $this->budget_metrics );

		$this->controller = new BudgetMetricsController( $this->server, $this->ads );
		$this->controller->register();
		$this->controller->set_container( $this->container );
		$this->controller->set_iso3166_provider( $this->iso_provider );
	}

	public function test_get_budget_metrics() {
		$budget_metrics_params = [
			'country_codes' => [ 'US' ],
			'budget'        => 12.25,
		];

		$expected_response_data = [
			'currency' => 'USD',
			'budget'   => 12.25,
			'country'  => 'US',
			'metrics'  => self::METRICS_DATA,
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'USD' );

		$this->budget_metrics->expects( $this->once() )
			->method( 'get_metrics' )
			->with( 12.25, [ 'US' ] )
			->willReturn( self::METRICS_DATA );

		$response = $this->do_request( self::ROUTE_BUDGET_METRICS, 'GET', $budget_metrics_params );

		$this->assertSame( $expected_response_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_budget_metrics_no_currency() {
		$budget_metrics_params = [
			'country_codes' => [ 'US' ],
			'budget'        => 12.4,
		];

		$response = $this->do_request( self::ROUTE_BUDGET_METRICS, 'GET', $budget_metrics_params );

		$this->assertEquals(
			[
				'message'       => 'No currency available for the Ads account.',
				'budget'        => 12.4,
				'currency'      => '',
				'country_codes' => [ 'US' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_metrics_no_metrics() {
		$budget_metrics_params = [
			'country_codes' => [ 'US' ],
			'budget'        => 12.5,
		];

		$expected_response_data = [
			'message'       => 'Cannot find any budget metrics.',
			'budget'        => 12.5,
			'currency'      => 'USD',
			'country_codes' => [ 'US' ],
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'USD' );

		$this->budget_metrics->expects( $this->once() )
			->method( 'get_metrics' )
			->with( 12.5, [ 'US' ] )
			->willReturn( null );

		$response = $this->do_request( self::ROUTE_BUDGET_METRICS, 'GET', $budget_metrics_params );

		$this->assertSame( $expected_response_data, $response->get_data() );
		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_get_budget_metrics_without_query_parameters() {
		$response = $this->do_request( self::ROUTE_BUDGET_METRICS, 'GET', [] );

		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
		$this->assertEquals( 'Missing parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
