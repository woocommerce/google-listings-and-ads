<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
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

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	protected const ROUTE = '/wc/gla/mc/settings';

	public function setUp(): void {
		parent::setUp();

		$this->controller = new SettingsController( $this->server );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
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
