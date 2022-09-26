<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AttributeMappingRulesController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for AttributeMappingRulesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group AttributeMapping
 */
class AttributeMappingRulesControllerTest extends RESTControllerUnitTest {


	protected const ROUTE_RULES = '/wc/gla/mc/mapping/rules';
	protected const ROUTE_RULE  = '/wc/gla/mc/mapping/rules/' . self::TEST_RULE_ID;

	protected const TEST_RULE_ID    = 1;
	protected const TEST_ATTRIBUTES = [
		'adult' => 'adult',
		'brand' => 'brand',
	];
	protected const TEST_RULE       = [
		'attribute'               => 'adult',
		'source'                  => 'yes',
		'category_condition_type' => 'ALL',
		'categories'              => '',
	];

	/**
	 * @var AttributeMappingHelper
	 */
	private AttributeMappingHelper $attribute_mapping_helper;


	/**
	 * @var AttributeMappingRulesQuery
	 */
	private AttributeMappingRulesQuery $attribute_mapping_rules_query;

	/**
	 * @var array The rule with ID used in tests
	 */
	private array $rule_with_id;


	public function setUp(): void {
		parent::setUp();
		$this->attribute_mapping_helper      = $this->createMock( AttributeMappingHelper::class );
		$this->attribute_mapping_rules_query = $this->createMock( AttributeMappingRulesQuery::class );
		$this->controller                    = new AttributeMappingRulesController( $this->server, $this->attribute_mapping_helper, $this->attribute_mapping_rules_query );
		$this->controller->register();
		$this->rule_with_id = array_merge( [ 'id' => self::TEST_RULE_ID ], self::TEST_RULE );

	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_RULES, $this->server->get_routes() );
	}

	public function test_get_rules_route() {
		$data = [
			[
				'id'                      => 1,
				'attribute'               => 'adult',
				'source'                  => 'yes',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'id'              => 2,
				'attribute'       => 'brand',
				'source'          => 'taxonomy:product_brand',
				'categories_type' => 'ONLY',
				'categories'      => '1,2,3',
			],
		];

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'get_results' )->willReturn( $data );

		$response = $this->do_request( self::ROUTE_RULES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data, $response->get_data() );
	}

	public function test_create_rule_route() {
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'last_insert_id' )->willReturn( self::TEST_RULE_ID );

		$this->validate_post_route( self::ROUTE_RULES, 'insert' );
	}

	public function test_update_rule_route() {
		$this->validate_post_route( self::ROUTE_RULE, 'update' );
	}

	public function test_create_rule_route_fails() {
		$this->attribute_mapping_helper->expects( $this->any() )
			->method( 'get_attributes' )->willReturn(
				self::TEST_ATTRIBUTES
			);

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'insert' )->willReturn( 0 );

		// insert works
		$response = $this->do_request( self::ROUTE_RULES, 'post', [ 'rule' => self::TEST_RULE ] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_rule_route_fails() {
		$this->attribute_mapping_helper->expects( $this->any() )
			->method( 'get_attributes' )->willReturn(
				self::TEST_ATTRIBUTES
			);

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'update' )->willReturn( 0 );

		// insert works
		$response = $this->do_request( self::ROUTE_RULE, 'post', [ 'rule' => self::TEST_RULE ] );
		$this->assertEquals( 400, $response->get_status() );
	}

	private function validate_post_route( $route, $method ) {
		$this->attribute_mapping_helper->expects( $this->any() )
			->method( 'get_attributes' )->willReturn(
				self::TEST_ATTRIBUTES
			);

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( $method )->willReturn( 1 );

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'get_rule' )->willReturn( $this->rule_with_id );

		// insert works
		$response = $this->do_request( $route, 'post', [ 'rule' => self::TEST_RULE ] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->rule_with_id, $response->get_data() );

		// not working without rule param
		$response = $this->do_request( $route, 'post' );
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.attribute param
		$response = $this->do_request(
			$route,
			'post',
			[
				'rule' => [
					'source'                  => 'x',
					'category_condition_type' => 'ALL',
				],
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.source param
		$response = $this->do_request(
			$route,
			'post',
			[
				'rule' => [
					'attribute'               => 'adult',
					'category_condition_type' => 'ALL',
				],
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.category_condition_type param
		$response = $this->do_request(
			$route,
			'post',
			[
				'rule' => [
					'attribute' => 'adult',
					'source'    => 'test',
				],
			]
		);
		$this->assertEquals( 400, $response->get_status() );

	}

	public function test_delete_rule_route() {
		$data = [ 'id' => self::TEST_RULE_ID ];
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'delete' )->willReturn( 1 );

		// delete works
		$response = $this->do_request( self::ROUTE_RULE, 'delete' );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data, $response->get_data() );
	}

	public function test_delete_rule_route_error() {
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'delete' )->willReturn( 0 );

		// delete fails
		$response = $this->do_request( self::ROUTE_RULE, 'delete' );
		$this->assertEquals( 400, $response->get_status() );
	}
}
