<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AttributeMappingController;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for AttributeMappingController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group AttributeMapping
 */
class AttributeMappingControllerTest extends RESTControllerUnitTest {


	protected const ROUTE_REQUEST_SOURCES    = '/wc/gla/mc/mapping/sources';
	protected const ROUTE_REQUEST_ATTRIBUTES = '/wc/gla/mc/mapping/attributes';

	/**
	 * @var AttributeMappingHelper
	 */
	private AttributeMappingHelper $attribute_mapping_helper;


	public function setUp(): void {
		parent::setUp();
		$this->attribute_mapping_helper = $this->createMock( AttributeMappingHelper::class );
		$this->controller               = new AttributeMappingController( $this->server, $this->attribute_mapping_helper );
		$this->controller->register();
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_REQUEST_ATTRIBUTES, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE_REQUEST_SOURCES, $this->server->get_routes() );
	}

	public function test_destinations_route() {
		$this->attribute_mapping_helper->expects( $this->once() )
			->method( 'get_attributes' );

		$response = $this->do_request( self::ROUTE_REQUEST_ATTRIBUTES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'data', $response->get_data() );
	}

	public function test_sources_route() {
		$this->attribute_mapping_helper->expects( $this->once() )
			->method( 'get_sources' )
			->willReturn(
				[
					'adult' => [
						'yes' => 'Yes',
						'no'  => 'No',
					],
				],
			);

		$response = $this->do_request( self::ROUTE_REQUEST_SOURCES, 'GET', [ 'attribute' => 'adult' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'data' => [
					'yes' => 'Yes',
					'no'  => 'No',
				],
			],
			$response->get_data()
		);
	}

}
