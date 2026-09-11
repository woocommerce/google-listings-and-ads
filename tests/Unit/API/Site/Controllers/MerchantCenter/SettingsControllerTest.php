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

	/**
	 * Default GCR settings, as they read when the GOOGLE_CUSTOMER_REVIEWS option doesn't exist yet.
	 *
	 * @var array
	 */
	protected const DEFAULT_GCR_OPTIONS = [
		'gcr_collect_reviews_after_purchase' => false,
		'gcr_badge_widget_enabled'           => false,
		'gcr_badge_widget_position'          => 'bottom-right',
	];

	/**
	 * Stub `$this->options->get()` for both the MERCHANT_CENTER and GOOGLE_CUSTOMER_REVIEWS
	 * options, since the controller now reads from both on every request.
	 *
	 * @param array $mc_options
	 * @param array $gcr_options
	 */
	protected function mock_options( array $mc_options = [], array $gcr_options = [] ): void {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::MERCHANT_CENTER, [], $mc_options ],
				[ OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, [], $gcr_options ],
			]
		);
	}

	public function test_get_settings() {
		$mc_options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];
		$this->mock_options( $mc_options );
		$this->shipping_zone->expects( $this->once() )->method( 'get_shipping_rates_count' )->willReturn( 1 );

		$expected = $mc_options + [ 'shipping_rates_count' => 1 ] + self::DEFAULT_GCR_OPTIONS;

		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings() {
		$mc_options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];
		$this->mock_options( $mc_options );

		// No GCR key is in this request, so only MERCHANT_CENTER should be persisted — see
		// test_edit_settings_does_not_persist_google_customer_reviews_when_request_has_no_gcr_keys
		// for the dedicated regression test on that specifically.
		$this->options->expects( $this->once() )->method( 'update' )->with(
			OptionsInterface::MERCHANT_CENTER,
			array_merge( $mc_options, [ 'shipping_time' => 'manual' ] )
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

	public function test_edit_settings_does_not_persist_google_customer_reviews_when_request_has_no_gcr_keys() {
		// A merchant editing an unrelated Merchant Center setting must not create the
		// GOOGLE_CUSTOMER_REVIEWS option (with default values) or fire its
		// woocommerce_gla_options_updated_google_customer_reviews hook for a save that never
		// touched GCR at all.
		$this->mock_options(
			[
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
				'tax_rate'      => 'destination',
			]
		);

		$this->options->expects( $this->once() )->method( 'update' )
			->with( OptionsInterface::MERCHANT_CENTER, $this->anything() );

		$response = $this->do_request( self::ROUTE, 'POST', [ 'tax_rate' => 'origin' ] );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings_falls_back_to_empty_array_when_merchant_center_option_is_not_an_array() {
		// A stored option can come back as `false` (e.g. never set) rather than an array — the
		// edit endpoint must fall back to an empty array instead of fataling on array access.
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::MERCHANT_CENTER, [], false ],
				[ OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, [], [] ],
			]
		);

		$response = $this->do_request( self::ROUTE, 'POST', [ 'shipping_time' => 'manual' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'manual', $response->get_data()['data']['shipping_time'] );
	}

	public function test_edit_settings_falls_back_to_empty_array_when_google_customer_reviews_option_is_not_an_array() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::MERCHANT_CENTER, [], [] ],
				[ OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, [], false ],
			]
		);

		$response = $this->do_request( self::ROUTE, 'POST', [ 'gcr_badge_widget_enabled' => true ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['data']['gcr_badge_widget_enabled'] );
	}

	public function test_edit_settings_changing_the_shipping_method_triggers_a_market_resync() {
		$this->mock_options(
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
		$this->mock_options(
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
		$this->mock_options();

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 'destination', $response->get_data()['tax_rate'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_default_tax_rate_settings_post() {
		$this->mock_options();

		$response = $this->do_request( self::ROUTE, 'POST', [] );

		$this->assertEquals( 'destination', $response->get_data()['data']['tax_rate'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_default_collect_reviews_after_purchase_setting() {
		$this->mock_options();

		$response = $this->do_request( self::ROUTE );

		$this->assertFalse( $response->get_data()['gcr_collect_reviews_after_purchase'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_collect_reviews_after_purchase_setting() {
		$mc_options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];
		$this->mock_options( $mc_options, self::DEFAULT_GCR_OPTIONS );

		$this->options->expects( $this->exactly( 2 ) )->method( 'update' )->withConsecutive(
			[ OptionsInterface::MERCHANT_CENTER, $mc_options ],
			[
				OptionsInterface::GOOGLE_CUSTOMER_REVIEWS,
				array_merge( self::DEFAULT_GCR_OPTIONS, [ 'gcr_collect_reviews_after_purchase' => true ] ),
			]
		);

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'gcr_collect_reviews_after_purchase' => true,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['data']['gcr_collect_reviews_after_purchase'] );
	}

	public function test_default_badge_widget_enabled_setting() {
		$this->mock_options();

		$response = $this->do_request( self::ROUTE );

		$this->assertFalse( $response->get_data()['gcr_badge_widget_enabled'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_badge_widget_enabled_setting() {
		$mc_options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];
		$this->mock_options( $mc_options, self::DEFAULT_GCR_OPTIONS );

		$this->options->expects( $this->exactly( 2 ) )->method( 'update' )->withConsecutive(
			[ OptionsInterface::MERCHANT_CENTER, $mc_options ],
			[
				OptionsInterface::GOOGLE_CUSTOMER_REVIEWS,
				array_merge( self::DEFAULT_GCR_OPTIONS, [ 'gcr_badge_widget_enabled' => true ] ),
			]
		);

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'gcr_badge_widget_enabled' => true,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['data']['gcr_badge_widget_enabled'] );
	}

	public function test_default_badge_widget_position_setting() {
		$this->mock_options();

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 'bottom-right', $response->get_data()['gcr_badge_widget_position'] );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_badge_widget_position_setting() {
		$mc_options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];
		$this->mock_options( $mc_options, self::DEFAULT_GCR_OPTIONS );

		$this->options->expects( $this->exactly( 2 ) )->method( 'update' )->withConsecutive(
			[ OptionsInterface::MERCHANT_CENTER, $mc_options ],
			[
				OptionsInterface::GOOGLE_CUSTOMER_REVIEWS,
				array_merge( self::DEFAULT_GCR_OPTIONS, [ 'gcr_badge_widget_position' => 'bottom-left' ] ),
			]
		);

		$response = $this->do_request(
			self::ROUTE,
			'POST',
			[
				'gcr_badge_widget_position' => 'bottom-left',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'bottom-left', $response->get_data()['data']['gcr_badge_widget_position'] );
	}
}
