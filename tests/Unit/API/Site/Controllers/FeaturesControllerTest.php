<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\FeaturesController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Features;
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
	 * @var Features
	 */
	private Features $features;

	public function setUp(): void {
		parent::setUp();
		$this->features   = $this->createMock( Features::class );
		$this->controller = new FeaturesController( $this->server, $this->features );
		$this->controller->register();
	}

	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	public function test_get_features_route() {
		$this->features->expects( $this->once() )
			->method( 'get_features' )
			->willReturn( self::TEST_FEATURES );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::TEST_FEATURES, $response->get_data() );
	}

	public function test_get_features_for_single_feature() {
		$expected = self::TEST_FEATURES['features'][ Features::GOOGLE_TAG_GATEWAY ];

		$this->features->expects( $this->once() )
			->method( 'get_features' )
			->with( Features::GOOGLE_TAG_GATEWAY )
			->willReturn( $expected );

		$response = $this->do_request( self::ROUTE, 'GET', [ 'feature' => 'google_tag_gateway' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected, $response->get_data() );
	}
}
