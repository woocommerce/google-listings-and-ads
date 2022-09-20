<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AttributeMappingController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AttributeMappingRules;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for AttributeMappingController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group AttributeMapping
 */
class AttributeMappingControllerTest extends RESTControllerUnitTest {


	protected const ROUTE_REQUEST_SOURCES      = '/wc/gla/mc/mapping/sources';
	protected const ROUTE_REQUEST_ATTRIBUTES   = '/wc/gla/mc/mapping/attributes';
	protected const ROUTE_RULES                = '/wc/gla/mc/mapping/rules';

	/**
	 * @var AttributeMappingHelper
	 */
	private AttributeMappingHelper $attribute_mapping_helper;

	/**
	 * @var AttributeMappingRules
	 */
	private AttributeMappingRules $mapping_rules;


	public function setUp(): void {
		parent::setUp();
		$this->attribute_mapping_helper = $this->createMock( AttributeMappingHelper::class );
		$this->mapping_rules            = $this->createMock( AttributeMappingRules::class );
		$this->controller               = new AttributeMappingController( $this->server, $this->attribute_mapping_helper, $this->mapping_rules );
		$this->controller->register();
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_REQUEST_ATTRIBUTES, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE_REQUEST_SOURCES, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE_RULES, $this->server->get_routes() );
	}

	public function test_attributes_route() {
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

	public function test_get_rules_route() {

		$data = [
			[ "attribute" => "adult", "source" => "yes", "categories_type" => "ALL", "categories" => []],
			[ "attribute" => "brand", "source" => "taxonomy:product_brand", "categories_type" => "ONLY", "categories" => [1,2,3]],
		];

		$this->mapping_rules->expects( $this->once() )
			->method( 'get' )->willReturn( $data );

		$response = $this->do_request( self::ROUTE_RULES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data, $response->get_data() );
	}

	public function test_post_rules_route() {

		$this->mapping_rules->expects( $this->once() )
			->method( 'set' );

		$response = $this->do_request( self::ROUTE_RULES, "post" ); // not called without rules param being null

		$this->assertEquals( 400, $response->get_status() );

		$response = $this->do_request( self::ROUTE_RULES, "post", [ "rules" => [] ] ); // called even if the array is empty (no rules)

		$this->assertEquals( 200, $response->get_status() );

	}

}
