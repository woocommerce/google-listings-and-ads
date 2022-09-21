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
	protected const ROUTE_GET_RULES            = '/wc/gla/mc/mapping/rules';
	protected const ROUTE_POST_RULE            = '/wc/gla/mc/mapping/rule';

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
		$this->assertArrayHasKey( self::ROUTE_GET_RULES, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE_POST_RULE, $this->server->get_routes() );
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
			[ "attribute" => "adult", "source" => "yes", "category_condition_type" => "ALL", "categories" => ''],
			[ "attribute" => "brand", "source" => "taxonomy:product_brand", "categories_type" => "ONLY", "categories" => '1,2,3'],
		];

		$this->attribute_mapping_helper->expects( $this->once() )
			->method( 'get_rules' )->willReturn( $data );

		$response = $this->do_request( self::ROUTE_GET_RULES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data, $response->get_data() );
	}

	public function test_post_rule_route() {

		$rule = [ "attribute" => "adult", "source" => "yes", "category_condition_type" => "ALL", "categories" => '' ];
		$rule_with_id = array_merge( [ "id" => 2 ], $rule );

		$this->attribute_mapping_helper->expects( $this->any() )
			->method( 'get_attributes' )->willReturn( [ 'adult' => 'adult', 'brand' => 'brand'] );

		$this->attribute_mapping_helper->expects( $this->once() )
			->method( 'insert_rule' )->with( $rule )->willReturn( $rule );

		$this->attribute_mapping_helper->expects( $this->once() )
			->method( 'update_rule' )->with( $rule_with_id )->willReturn( $rule_with_id );

		// insert works
		$response = $this->do_request( self::ROUTE_POST_RULE, "post",  [ "rule" => $rule ] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $rule, $response->get_data() );

		// update works
		$response = $this->do_request( self::ROUTE_POST_RULE, "post",  [ "rule" => $rule_with_id ] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array_merge( [ 'id' => 2 ], $rule ), $response->get_data() );

		// not working without rule param
		$response = $this->do_request( self::ROUTE_POST_RULE, "post" );
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.attribute param
		$response = $this->do_request( self::ROUTE_POST_RULE, "post",  [ "rule" => [ 'source' => 'x', 'category_condition_type' => 'ALL' ] ] );
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.source param
		$response = $this->do_request( self::ROUTE_POST_RULE, "post",  [ "rule" => [ 'attribute' => 'adult', 'category_condition_type' => 'ALL' ] ] );
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.category_condition_type param
		$response = $this->do_request( self::ROUTE_POST_RULE, "post",  [ "rule" => [ 'attribute' => 'adult', 'source' => 'test' ] ] );
		$this->assertEquals( 400, $response->get_status() );

	}

	public function test_delete_rule_route() {
		$rule_id = 1;
		$this->attribute_mapping_helper->expects( $this->once() )
			->method( 'delete_rule' )->with( $rule_id )->willReturn( true );

		// not working without rule_id param
		$response = $this->do_request( self::ROUTE_POST_RULE, "delete" );
		$this->assertEquals( 400, $response->get_status() );

		// not working with wrong rule_id param
		$response = $this->do_request( self::ROUTE_POST_RULE, "delete", [ "rule_id" => "bad" ] );
		$this->assertEquals( 400, $response->get_status() );

		// delete works
		$response = $this->do_request( self::ROUTE_POST_RULE, "delete", [ "rule_id" => 1 ] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data() );

	}

}
