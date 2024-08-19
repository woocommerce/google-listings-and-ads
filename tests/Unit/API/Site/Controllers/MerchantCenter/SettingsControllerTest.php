<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\TrackingTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SettingsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class SettingsControllerTest extends RESTControllerUnitTest {

	/** @var SettingsController $controller */
	protected $controller;

	protected const ROUTE = '/wc/gla/mc/settings';

	public function setUp(): void {
		parent::setUp();

		$this->controller = new SettingsController( $this->server );
		$this->controller->register();
	}

	public function test_default_settings() {
		$response = $this->do_request( self::ROUTE, 'GET' );

		$this->assertEquals( 'destination', $response->get_data()['tax_rate'] );
		$this->assertEquals( 200, $response->get_status() );
	}
}
