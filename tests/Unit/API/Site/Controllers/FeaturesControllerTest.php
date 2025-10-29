<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\FeaturesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for FeaturesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class FeaturesControllerTest extends RESTControllerUnitTest {

	protected const ROUTE = '/wc/gla/features';

	protected const TEST_FEATURES = [
		'version'  => 1,
		'features' => [
			'google_tag_gateway' => [
				'enabled'    => true,
				'default'    => true,
				'percentage' => 100,
			],
		],
	];

	/**
	 * @var OptionsInterface
	 */
	private OptionsInterface $options;

	public function setUp(): void {
		parent::setUp();
		$this->options    = $this->createMock( OptionsInterface::class );
		$this->controller = new FeaturesController( $this->server );
		$this->controller->register();
		$this->controller->set_options_object( $this->options );
	}

	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	public function test_get_features_route() {
		$this->options->expects( $this->once() )
			->method( 'get' )->willReturn( self::TEST_FEATURES );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::TEST_FEATURES, $response->get_data() );
	}
}
