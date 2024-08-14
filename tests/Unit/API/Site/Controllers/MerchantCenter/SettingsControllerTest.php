<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SettingsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class SettingsControllerTest extends RESTControllerUnitTest {


	/** @var MockObject|ShippingZone $shipping_zone */
	protected $shipping_zone;

	/** @var SettingsSyncController $controller */
	protected $controller;

	/** @var MockObject|OptionsInterface $options */
	protected $options;


	protected const ROUTE = '/wc/gla/mc/settings';

	public function setUp(): void {
		parent::setUp();

		$this->shipping_zone = $this->createMock( ShippingZone::class );
		$this->options       = $this->createMock( OptionsInterface::class );
		$this->controller    = new SettingsController( $this->server, $this->shipping_zone );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_get_settings() {
		$options = [
			'shipping_rate'           => 'flat',
			'shipping_time'           => 'flat',
			'tax_rate'                => null,
			'website_live'            => true,
			'checkout_process_secure' => true,
			'payment_methods_visible' => true,
			'refund_tos_visible'      => true,
			'contact_info_visible'    => true,

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
			'shipping_rate'           => 'flat',
			'shipping_time'           => 'flat',
			'tax_rate'                => null,
			'website_live'            => true,
			'checkout_process_secure' => true,
			'payment_methods_visible' => true,
			'refund_tos_visible'      => true,
			'contact_info_visible'    => true,

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
}
