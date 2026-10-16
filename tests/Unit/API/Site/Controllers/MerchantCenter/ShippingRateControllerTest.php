<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ShippingRateController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

/**
 * Class ShippingRateControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class ShippingRateControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|ShippingRateQuery $shipping_rate_query */
	protected $shipping_rate_query;

	/** @var ShippingRateController $controller */
	protected $controller;

	protected const ROUTE_RATES = '/wc/gla/mc/shipping/rates';

	public function setUp(): void {
		parent::setUp();

		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->controller          = new ShippingRateController( $this->server, $this->shipping_rate_query );
		$this->controller->register();
	}

	public function test_get_all_rates() {
		$this->shipping_rate_query->expects( $this->once() )
			->method( 'set_order' )
			->willReturn( $this->shipping_rate_query );
		$this->shipping_rate_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn(
				[
					[
						'id'       => '123',
						'country'  => 'US',
						'currency' => 'USD',
						'rate'     => '5.00',
						'options'  => [
							'free_shipping_threshold' => '100',
						],
					],
				]
			);

		$response = $this->do_request( self::ROUTE_RATES, 'GET' );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertCount( 1, $data );

		$this->assertEquals( '123', $data[0]['id'] );
		$this->assertEquals( 'US', $data[0]['country'] );
		$this->assertEquals( 'USD', $data[0]['currency'] );
		$this->assertEquals( '5.00', $data[0]['rate'] );
		$this->assertEquals( 100, $data[0]['options']['free_shipping_threshold'] );
	}

	public function test_schema_currency_default_is_store_currency_not_usd() {
		$method = new ReflectionMethod( ShippingRateController::class, 'get_shipping_rate_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( $this->controller );

		$this->assertArrayHasKey( 'currency', $schema );
		$this->assertSame( get_woocommerce_currency(), $schema['currency']['default'] );
		$this->assertIsCallable( $schema['currency']['validate_callback'] );
	}

	public function test_schema_currency_validate_callback_rejects_non_iso_codes() {
		$method = new ReflectionMethod( ShippingRateController::class, 'get_shipping_rate_schema' );
		$method->setAccessible( true );

		$schema   = $method->invoke( $this->controller );
		$validate = $schema['currency']['validate_callback'];
		$request  = new \WP_REST_Request();

		$this->assertInstanceOf( \WP_Error::class, $validate( 'usd', $request, 'currency' ) );
		$this->assertInstanceOf( \WP_Error::class, $validate( 'EURO', $request, 'currency' ) );
		$this->assertInstanceOf( \WP_Error::class, $validate( '123', $request, 'currency' ) );
		$this->assertInstanceOf( \WP_Error::class, $validate( '', $request, 'currency' ) );
		$this->assertInstanceOf( \WP_Error::class, $validate( null, $request, 'currency' ) );

		$this->assertTrue( $validate( 'USD', $request, 'currency' ) );
		$this->assertTrue( $validate( 'EUR', $request, 'currency' ) );
	}

	public function test_empty_options_array_is_returned_as_object() {
		$this->shipping_rate_query->expects( $this->once() )
			->method( 'set_order' )
			->willReturn( $this->shipping_rate_query );
		$this->shipping_rate_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn(
				[
					[
						'id'       => '123',
						'country'  => 'US',
						'currency' => 'USD',
						'rate'     => '5.00',
						'method'   => 'flat_rate',
						'options'  => [],
					],
				]
			);

		$response = $this->do_request( self::ROUTE_RATES, 'GET' );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertCount( 1, $data );

		// Confirm that the 'options' value is an object, not an array.
		$this->assertIsObject( $data[0]['options'] );
	}
}
