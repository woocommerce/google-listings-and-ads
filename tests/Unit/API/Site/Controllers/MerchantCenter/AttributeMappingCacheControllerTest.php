<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AttributeMappingCacheController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for AttributeMappingCacheController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group AttributeMapping
 */
class AttributeMappingCacheControllerTest extends RESTControllerUnitTest {


	protected const ROUTE = '/wc/gla/mc/mapping/cache';

	/**
	 * @var TransientsInterface
	 */
	private TransientsInterface $transients;


	public function setUp(): void {
		parent::setUp();
		$this->transients = $this->createMock( TransientsInterface::class );
		$this->controller = new AttributeMappingCacheController( $this->server, $this->transients );
		$this->controller->register();
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	public function test_flush_cache() {
		$this->transients->expects( $this->once() )
			->method( 'delete' )->with(TransientsInterface::ATTRIBUTE_MAPPING_META_FIELDS);

		$response = $this->do_request( self::ROUTE, 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Attribute Mapping cache was flushed', $response->get_data()['message'] );
	}

}
