<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingTimeBatchController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;
use WP_REST_Response as Response;

/**
 * Class ShippingTimeBatchControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class ShippingTimeBatchControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|Container $container */
	protected $container;

	/** @var MockObject|ISO3166DataProvider $iso_provider */
	protected $iso_provider;

	/** @var ShippingTimeBatchController $controller */
	protected $controller;

	protected const ROUTE_SHIPPING_TIME_BATCH = '/wc/gla/mc/shipping/times/batch';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->container    = $this->createMock( Container::class );
		$this->iso_provider = $this->createMock( ISO3166DataProvider::class );

		$this->container->method( 'get' )->willReturn( $this->server );

		$this->server->register_route(
			'/wc/gla',
			'/mc/shipping/times',
			[
				'methods'  => TransportMethods::CREATABLE,
				'callback' => function ( $request ) {
					return new Response( $request->get_param( 'country_code' ), 201 );
				},
			]
		);

		$this->controller = new ShippingTimeBatchController( $this->container );

		$this->controller->set_iso3166_provider( $this->iso_provider );
		$this->controller->register();
	}

	/**
	 * Test a successful dispatch request for the batch creation of shipping times.
	 */
	public function test_create_shipping_time_batch() {
		$payload = [
			'country_codes' => [ 'US', 'GB' ],
			'time'          => 5,
			'max_time'      => 10,
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIME_BATCH, 'POST', $payload );

		$expected = [
			'errors'  => [],
			'success' => $payload['country_codes'],
		];

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * Test a failed batch creation of shipping times with empty country codes.
	 */
	public function test_create_shipping_time_batch_empty_country_codes() {
		$payload = [
			'country_codes' => [],
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIME_BATCH, 'POST', $payload );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test a failed batch creation of shipping times with duplicate country codes.
	 */
	public function test_create_shipping_time_batch_duplicate_country_codes() {
		$payload = [
			'country_codes' => [ 'US', 'GB', 'US' ],
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIME_BATCH, 'POST', $payload );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test a failed batch creation of shipping times with invalid country codes.
	 */
	public function test_create_shipping_time_batch_invalid_country_codes() {
		$this->iso_provider
			->method( 'alpha2' )
			->willThrowException( new Exception( 'invalid_country' ) );

		$payload = [
			'country_codes' => [ 'United States' ],
		];

		$response = $this->do_request( self::ROUTE_SHIPPING_TIME_BATCH, 'POST', $payload );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
