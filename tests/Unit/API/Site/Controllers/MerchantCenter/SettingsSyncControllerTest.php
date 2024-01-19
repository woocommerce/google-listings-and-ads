<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsSyncController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\TrackingTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SettingsSyncControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class SettingsSyncControllerTest extends RESTControllerUnitTest {

	use TrackingTrait;

	/** @var MockObject|Settings $settings */
	protected $settings;

	/** @var SettingsSyncController $controller */
	protected $controller;

	protected const ROUTE = '/wc/gla/mc/settings/sync';

	public function setUp(): void {
		parent::setUp();

		$this->settings   = $this->createMock( Settings::class );
		$this->controller = new SettingsSyncController( $this->server, $this->settings );
		$this->controller->register();
	}

	public function test_settings_sync() {
		$this->settings->expects( $this->once() )->method( 'sync_taxes' );
		$this->settings->expects( $this->once() )->method( 'sync_shipping' );

		$settings = [
			'shipping_rate'           => 'flat',
			'offers_free_shipping'    => true,
			'free_shipping_threshold' => 100,
			'shipping_time'           => 'manual',
			'tax_rate'                => 'manual',
			'target_countries'        => 'US,CA',
		];

		$this->settings->expects( $this->once() )
			->method( 'get_tracked_settings' )
			->willReturn( $settings );

		$this->expect_track_event( 'mc_setup_completed', $settings );

		$response = $this->do_request( self::ROUTE, 'POST' );

		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_mc_settings_sync' ) );
	}

	public function test_settings_sync_with_non_json_exception() {
		$this->settings->expects( $this->once() )
			->method( 'sync_taxes' )
			->willThrowException(
				new Exception( 'error' )
			);

		$response = $this->do_request( self::ROUTE, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 500, $response->get_status() );
	}

	public function test_settings_sync_with_json_exception() {
		$this->settings->expects( $this->once() )
			->method( 'sync_taxes' )
			->willThrowException(
				new Exception(
					json_encode(
						[
							'code'    => 400,
							'message' => 'error',
						]
					)
				)
			);

		$response = $this->do_request( self::ROUTE, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
