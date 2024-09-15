<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingTimeController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ShippingTimeControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class ShippingTimeControllerTest extends RESTControllerUnitTest {

	/** @var Container $container */
	protected $container;

	/** @var ShippingTimeController $controller */
	protected $controller;

	/** @var MockObject|ISO3166DataProvider $iso_provider */
	protected $iso_provider;

	/** @var MockObject|ShippingTimeQuery $conversion_action */
	protected $shiping_time_query;

	protected const ROUTE_SHIPPING_TIMES = '/wc/gla/mc/shipping/times';

	protected const ROUTE_SHIPPING_TIMES_COUNTRY = '/wc/gla/mc/shipping/times/ES';

	public function setUp(): void {
		parent::setUp();

		$this->shiping_time_query = $this->createMock( ShippingTimeQuery::class );
		$this->iso_provider       = $this->createMock( ISO3166DataProvider::class );

		$this->container = new Container();
		$this->container->share( RESTServer::class, $this->server );
		$this->container->share( ShippingTimeQuery::class, $this->shiping_time_query );

		$this->controller = new ShippingTimeController( $this->container );
		$this->controller->set_iso3166_provider( $this->iso_provider );
		$this->controller->register();
	}

	public function test_get_shipping_times() {
		$this->shiping_time_query->expects( $this->once() )
			->method( 'set_limit' )
			->willReturn(
				$this->shiping_time_query
			);

		$this->shiping_time_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn(
				[
					[
						'id'       => '1',
						'country'  => 'ES',
						'time'     => 3,
						'max_time' => 1,
					],
				]
			);

		$this->iso_provider
		->method( 'alpha2' )
		->willReturn( [ 'name' => 'Spain' ] );

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'ES' => [
					'country_code' => 'ES',
					'country'      => 'Spain',
					'time'         => 3,
					'max_time'     => 1,
				],
			],
			$response->get_data()
		);
	}

	public function test_create_shipping_rate_insert() {
		$payload = [
			'country_code' => 'US',
			'time'         => 5,
			'max_time'     => 10,
		];

		$this->shiping_time_query->expects( $this->once() )
			->method( 'where' )
			->with( 'country', 'US' )
			->willReturn(
				$this->shiping_time_query
			);

		$this->shiping_time_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn(
				[]
			);

		$this->shiping_time_query->expects( $this->once() )
			->method( 'insert' )
			->with(
				[
					'country'  => 'US',
					'time'     => 5,
					'max_time' => 10,
				]
			);

		$this->shiping_time_query->expects( $this->never() )
			->method( 'update' );

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 'Successfully added time for country: "US".', $response->get_data()['message'] );
	}

	public function test_create_shipping_rate_update() {
		$payload = [
			'country_code' => 'US',
			'time'         => 5,
			'max_time'     => 10,
		];

		$this->shiping_time_query->expects( $this->once() )
			->method( 'where' )
			->with( 'country', 'US' )
			->willReturn(
				$this->shiping_time_query
			);

		$this->shiping_time_query->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn(
				[
					[
						'id'       => 1,
						'country'  => 'US',
						'time'     => 3,
						'max_time' => 1,
					],
				]
			);

		$this->shiping_time_query->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'country'  => 'US',
					'time'     => 5,
					'max_time' => 10,
				],
				[
					'id' => 1,
				]
			);

		$this->shiping_time_query->expects( $this->never() )
			->method( 'insert' );

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 'Successfully added time for country: "US".', $response->get_data()['message'] );
	}

	public function test_create_shipping_time_invalid_query() {
		$payload = [
			'country_code' => 'US',
			'time'         => 5,
			'max_time'     => 10,
		];

		$this->shiping_time_query->expects( $this->once() )
			->method( 'where' )
			->with( 'country', 'US' )
			->willThrowException(
				new InvalidQuery( 'error' )
			);

		try {
			$this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );
		} catch ( InvalidQuery $e ) {
			$this->assertEquals( 'error', $e->getMessage() );
		}
	}

	public function test_get_shipping_time_country() {
		$this->shiping_time_query->expects( $this->once() )
			->method( 'where' )
			->with( 'country', 'ES' )
			->willReturn(
				$this->shiping_time_query
			);

		$this->shiping_time_query->expects( $this->exactly( 1 ) )
			->method( 'get_results' )
			->willReturn(
				[
					[
						'id'       => 1,
						'country'  => 'ES',
						'time'     => 3,
						'max_time' => 1,
					],
				]
			);

		$this->iso_provider
			->method( 'alpha2' )
			->willReturn( [ 'name' => 'Spain' ] );

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES_COUNTRY, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'ES', $response->get_data()['country_code'] );
		$this->assertEquals( 3, $response->get_data()['time'] );
		$this->assertEquals( 1, $response->get_data()['max_time'] );
	}

	public function test_get_shipping_time_country_not_found() {
		$this->shiping_time_query->expects( $this->once() )
			->method( 'where' )
			->with( 'country', 'ES' )
			->willReturn(
				$this->shiping_time_query
			);

		$this->shiping_time_query->expects( $this->exactly( 1 ) )
			->method( 'get_results' )
			->willReturn(
				[]
			);

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES_COUNTRY, 'GET' );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'No time available.', $response->get_data()['message'] );
		$this->assertEquals( 'ES', $response->get_data()['country'] );
	}

	public function test_delete_shipping_time_country() {
		$this->shiping_time_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'ES' );

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES_COUNTRY, 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 'Successfully deleted the time for country: "ES".', $response->get_data()['message'] );
	}

	public function test_delete_shipping_time_country_invalid_query() {
		$this->shiping_time_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'ES' )
			->willThrowException(
				new InvalidQuery( 'error' )
			);

		try {
			$this->do_request( self::ROUTE_SHIPPING_TIMES_COUNTRY, 'DELETE' );
		} catch ( InvalidQuery $e ) {
			$this->assertEquals( 'error', $e->getMessage() );
		}
	}

	public function test_missing_time_param() {
		$payload = [
			'country_code' => 'US',
			'max_time'     => 10,
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): time', $response->get_data()['message'] );
	}

	public function test_missing_max_time_param() {
		$payload = [
			'country_code' => 'US',
			'time'         => 10,
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): max_time', $response->get_data()['message'] );
	}

	public function test_negative_time_param() {
		$payload = [
			'country_code' => 'US',
			'time'         => -1,
			'max_time'     => 10,
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Shipping times cannot be negative.', $response->get_data()['data']['params']['time'] );
	}

	public function test_minimum_shipping_time_bigger_than_max_time_param() {
		$payload = [
			'country_code' => 'US',
			'time'         => 20,
			'max_time'     => 10,
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIMES, 'POST', $payload );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'The minimum shipping time cannot be greater than the maximum shipping time.', $response->get_data()['data']['params']['time'] );
	}
}
