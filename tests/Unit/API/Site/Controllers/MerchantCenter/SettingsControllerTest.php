<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SettingsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class SettingsControllerTest extends RESTControllerUnitTest {

	/** @var SettingsController $controller */
	protected $controller;

	/** @var MockObject|ShippingZone $shipping_zone */
	protected $shipping_zone;

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	protected const ROUTE = '/wc/gla/mc/settings';

	public function setUp(): void {
		parent::setUp();

		$this->shipping_zone  = $this->createMock( ShippingZone::class );
		$this->market_service = $this->createMock( MarketService::class );
		$this->options        = $this->createMock( OptionsInterface::class );
		$this->controller     = new SettingsController( $this->server, $this->shipping_zone, $this->market_service );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_get_settings() {
		$options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',

		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn(
			$options
		);
		$this->shipping_zone->expects( $this->once() )->method( 'get_shipping_rates_count' )->willReturn( 1 );

		$expected = $options + [
			'shipping_rates_count' => 1,
		];

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings() {
		$options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn(
			$options
		);

		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::MERCHANT_CENTER, array_merge( $options, [ 'shipping_time' => 'manual' ] ) );

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'shipping_time' => 'manual',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $response->get_data()['status'] );
	}

	public function test_edit_settings_changing_the_shipping_method_triggers_a_market_resync() {
		$this->options->method( 'get' )->willReturn(
			[
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
				'tax_rate'      => 'destination',
			]
		);

		// The global shipping method changed (flat -> automatic), so markets must be resynced.
		$this->market_service->expects( $this->once() )
			->method( 'handle_global_shipping_method_change' );

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'shipping_rate' => 'automatic',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings_without_a_shipping_method_change_does_not_trigger_a_market_resync() {
		$this->options->method( 'get' )->willReturn(
			[
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
				'tax_rate'      => 'destination',
			]
		);

		// Only the tax rate changed; the shipping method is unchanged, so no resync is scheduled.
		$this->market_service->expects( $this->never() )
			->method( 'handle_global_shipping_method_change' );

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'tax_rate' => 'origin',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_default_tax_rate_settings() {
		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 'destination', $response->get_data()['tax_rate'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_default_tax_rate_settings_post() {
		$response = $this->do_request( self::ROUTE, 'POST', [] );

		$this->assertEquals( 'destination', $response->get_data()['data']['tax_rate'] );
		$this->assertEquals( 200, $response->get_status() );
	}
}
