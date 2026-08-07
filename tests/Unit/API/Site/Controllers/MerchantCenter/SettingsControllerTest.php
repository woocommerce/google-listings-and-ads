<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',

		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn(
			$options
		);
		$this->shipping_zone->expects( $this->once() )->method( 'get_shipping_rates_count' )->willReturn( 1 );

		$expected = $options + [
			'shipping_rates_count'           => 1,
			'collect_reviews_after_purchase' => false,
			'badge_widget_enabled'           => false,
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

		$this->options->expects( $this->once() )->method( 'update' )->with(
			OptionsInterface::MERCHANT_CENTER,
			array_merge(
				$options,
				[
					'shipping_time'                  => 'manual',
					'collect_reviews_after_purchase' => false,
					'badge_widget_enabled'           => false,
				]
			)
		);

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

	public function test_default_collect_reviews_after_purchase_setting() {
		$response = $this->do_request( self::ROUTE );

		$this->assertFalse( $response->get_data()['collect_reviews_after_purchase'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_collect_reviews_after_purchase_setting() {
		$options = [
			'shipping_rate'                  => 'flat',
			'shipping_time'                  => 'flat',
			'tax_rate'                       => 'destination',
			'collect_reviews_after_purchase' => false,
			'badge_widget_enabled'           => false,
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn(
			$options
		);

		$this->options->expects( $this->once() )->method( 'update' )->with(
			OptionsInterface::MERCHANT_CENTER,
			array_merge( $options, [ 'collect_reviews_after_purchase' => true ] )
		);

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'collect_reviews_after_purchase' => true,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['data']['collect_reviews_after_purchase'] );
	}

	public function test_default_badge_widget_enabled_setting() {
		$response = $this->do_request( self::ROUTE );

		$this->assertFalse( $response->get_data()['badge_widget_enabled'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_badge_widget_enabled_setting() {
		$options = [
			'shipping_rate'                  => 'flat',
			'shipping_time'                  => 'flat',
			'tax_rate'                       => 'destination',
			'collect_reviews_after_purchase' => false,
			'badge_widget_enabled'           => false,
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn(
			$options
		);

		$this->options->expects( $this->once() )->method( 'update' )->with(
			OptionsInterface::MERCHANT_CENTER,
			array_merge( $options, [ 'badge_widget_enabled' => true ] )
		);

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'badge_widget_enabled' => true,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['data']['badge_widget_enabled'] );
	}
}
