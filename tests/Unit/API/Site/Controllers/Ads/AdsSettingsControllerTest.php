<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AdsSettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AdsSettingsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class AdsSettingsControllerTest extends RESTControllerUnitTest {

	/** @var AdsSettingsController $controller */
	protected $controller;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	protected const ROUTE_SETTINGS = '/wc/gla/ads/settings';

	public function setUp(): void {
		parent::setUp();

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->controller = new AdsSettingsController( $this->server );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_get_settings() {
		$expected = [
			'enhanced_conversions_enabled' => true,
			'ads_has_unclaimed_incentive'  => true,
		];

		$this->options->expects( $this->once() )->method( 'get_ads_id' )->willReturn( 1 );

		$this->options->expects( $this->exactly( 2 ) )->method( 'get' )->willReturn( true );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'GET' );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_settings_with_null_option_value() {
		$expected = [
			'enhanced_conversions_enabled' => false,
			'ads_has_unclaimed_incentive'  => false,
		];

		$this->options->expects( $this->once() )->method( 'get_ads_id' )->willReturn( 1 );
		$this->options->expects( $this->exactly( 2 ) )->method( 'get' )->willReturn( null );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'GET' );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings() {
		$expected = [
			'enhanced_conversions_enabled' => false,
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED, false )->willReturn( true );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', $expected );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings_with_same_value() {
		$expected = [
			'enhanced_conversions_enabled' => false,
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( false );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', $expected );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings_with_null_option_value() {
		$expected = [
			'enhanced_conversions_enabled' => false,
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( null );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', $expected );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_settings_fail() {
		$query = [
			'enhanced_conversions_enabled' => false,
		];

		$this->options->expects( $this->once() )->method( 'get' )->willReturn( true );
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED, false )->willReturn( false );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', $query );

		$this->assertEquals( 'Unable to update setting.', $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}
}
