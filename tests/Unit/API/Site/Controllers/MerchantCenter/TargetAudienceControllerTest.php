<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\TargetAudienceController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
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

	/** @var TargetAudienceController $controller */
	protected $controller;

	protected const ROUTE_TARGET_AUDIENCE = '/wc/gla/mc/target_audience';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp            = $this->createMock( WP::class );
		$this->wc            = $this->createMock( WC::class );
		$this->shipping_zone = $this->createMock( ShippingZone::class );
		$this->iso_provider  = $this->createMock( ISO3166DataProvider::class );
		$this->google_helper = $this->createMock( GoogleHelper::class );
		$this->options       = $this->createMock( OptionsInterface::class );

		$this->controller = new TargetAudienceController( $this->server, $this->wp, $this->wc, $this->shipping_zone, $this->google_helper );

		$this->controller->set_iso3166_provider( $this->iso_provider );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();

		$this->google_helper->method( 'is_country_supported' )->willReturn( true );
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
}
