<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\IncentiveCreditsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;

/**
 * Class IncentiveCreditsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class IncentiveCreditsControllerTest extends RESTControllerUnitTest {

	/** @var Container $container */
	protected $container;

	/** @var MockObject|Middleware $middleware */
	protected $middleware;

	/** @var IncentiveCreditsController $controller */
	protected $controller;

	protected const ROUTE_INCENTIVE_CREDITS = '/wc/gla/ads/incentive-credits';

	public function setUp(): void {
		parent::setUp();

		$this->middleware = $this->createMock( Middleware::class );

		$this->container = new Container();
		$this->container->addShared( Middleware::class, $this->middleware );

		$this->controller = new IncentiveCreditsController( $this->server );
		$this->controller->register();
		$this->controller->set_container( $this->container );
	}

	public function test_get_incentive_credits_success() {
		$incentive_credits = [
			'country'      => 'GB',
			'currency'     => 'GBP',
			'ads_currency' => 'GBP',
			'spending'     => 400.01,
			'credit'       => 400.01,
		];

		$this->middleware->expects( $this->once() )
			->method( 'get_incentive_credits' )
			->willReturn( $incentive_credits );

		$response = $this->do_request( self::ROUTE_INCENTIVE_CREDITS, 'GET' );

		$this->assertSame( $incentive_credits, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_incentive_credits_no_data() {
		$this->middleware->expects( $this->once() )
			->method( 'get_incentive_credits' )
			->willReturn( [] );

		$response = $this->do_request( self::ROUTE_INCENTIVE_CREDITS, 'GET' );

		$this->assertEquals(
			[
				'message' => 'No incentive credits found.',
			],
			$response->get_data()
		);
		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_get_incentive_credits_exception() {
		$this->middleware->expects( $this->once() )
			->method( 'get_incentive_credits' )
			->willThrowException( new Exception( 'Error fetching incentive credits.' ) );

		$response = $this->do_request( self::ROUTE_INCENTIVE_CREDITS, 'GET' );

		$this->assertEquals(
			[
				'message' => 'Error fetching incentive credits.',
			],
			$response->get_data()
		);
		$this->assertEquals( 500, $response->get_status() );
	}

	public function test_get_incentive_credits_invalid_response() {
		$this->middleware->expects( $this->once() )
			->method( 'get_incentive_credits' )
			->willThrowException( new Exception( 'Invalid response when fetching incentive credits.' ) );

		$response = $this->do_request( self::ROUTE_INCENTIVE_CREDITS, 'GET' );

		$this->assertEquals(
			[
				'message' => 'Invalid response when fetching incentive credits.',
			],
			$response->get_data()
		);
		$this->assertEquals( 500, $response->get_status() );
	}
}
