<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\TargetAudienceController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TargetAudienceControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class TargetAudienceControllerTest extends RESTControllerUnitTest {

	/** @var WP $wp */
	protected $wp;

	/** @var WC $wc */
	protected $wc;

	/** @var MockObject|ShippingZone $shipping_zone */
	protected $shipping_zone;

	/** @var MockObject|ISO3166DataProvider $iso_provider */
	protected $iso_provider;

	/** @var MockObject|GoogleHelper $google_helper */
	protected $google_helper;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|TargetAudience $target_audience */
	protected $target_audience;

	/** @var TargetAudienceController $controller */
	protected $controller;

	protected const ROUTE_TARGET_AUDIENCE = '/wc/gla/mc/target_audience';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp              = $this->createMock( WP::class );
		$this->wc              = $this->createMock( WC::class );
		$this->shipping_zone   = $this->createMock( ShippingZone::class );
		$this->iso_provider    = $this->createMock( ISO3166DataProvider::class );
		$this->google_helper   = $this->createMock( GoogleHelper::class );
		$this->options         = $this->createMock( OptionsInterface::class );
		$this->target_audience = $this->createMock( TargetAudience::class );

		$this->controller = new TargetAudienceController( $this->server, $this->wp, $this->wc, $this->shipping_zone, $this->google_helper, $this->target_audience );

		$this->controller->set_iso3166_provider( $this->iso_provider );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();

		$this->google_helper->method( 'is_country_supported' )->willReturn( true );
	}

	/**
	 * Test that GET target audience response includes language_code derived from locale.
	 */
	public function test_get_target_audience_includes_language_code() {
		$this->wp->method( 'get_locale' )->willReturn( 'en_US' );
		$this->options->method( 'get' )->willReturn( [] );

		$response = $this->do_request( self::ROUTE_TARGET_AUDIENCE, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'language_code', $response->get_data() );
		$this->assertEquals( 'en', $response->get_data()['language_code'] );
	}

	/**
	 * Test that GET target audience response includes main_target_country from TargetAudience.
	 */
	public function test_get_target_audience_includes_main_target_country() {
		$this->wp->method( 'get_locale' )->willReturn( 'en_US' );
		$this->options->method( 'get' )->willReturn( [] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$response = $this->do_request( self::ROUTE_TARGET_AUDIENCE, 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'main_target_country', $response->get_data() );
		$this->assertEquals( 'US', $response->get_data()['main_target_country'] );
	}

	/**
	 * Test a successful update of target audience.
	 */
	public function test_update_target_audience() {
		$payload = [
			'location'  => 'selected',
			'countries' => [ 'US', 'GB' ],
		];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::TARGET_AUDIENCE, $payload );

		$response = $this->do_request( self::ROUTE_TARGET_AUDIENCE, 'POST', $payload );

		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * Test a successful update of target audience with empty country codes.
	 */
	public function test_update_target_audience_empty_countries() {
		$payload = [
			'location'  => 'all',
			'countries' => [],
		];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::TARGET_AUDIENCE, $payload );

		$response = $this->do_request( self::ROUTE_TARGET_AUDIENCE, 'POST', $payload );

		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * Test a failed update of target audience with invalid country codes.
	 */
	public function test_update_target_audience_invalid_countries() {
		$this->iso_provider
			->method( 'alpha2' )
			->willThrowException( new Exception( 'invalid_country' ) );

		$payload = [
			'location'  => 'selected',
			'countries' => [ 'United States' ],
		];

		$response = $this->do_request( self::ROUTE_TARGET_AUDIENCE, 'POST', $payload );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): countries', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
