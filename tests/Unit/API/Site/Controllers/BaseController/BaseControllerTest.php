<?php

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Helpers\ExtendedBaseController;

class BaseControllerTest extends RESTControllerUnitTest {

	public function setUp() {
		parent::setUp();
		$this->controller = new ExtendedBaseController( $this->server );
	}

	public function test_empty_obj() {
		$schema = [
			'options' => [
				'type'       => 'object',
				'properties' => [
					'city'     => [
						'type' => 'string',
					],
					'postcode' => [
						'type' => 'string',
					],
				],
			],
		];

		$items = [
			'options'          => [],
			'fieldNotInSchema' => 'TestName',
		];

		$result = $this->controller->get_matched_data_with_schema( $schema, $items );

		$this->assertIsObject( $result['options'] );
		$this->assertArrayNotHasKey( 'fieldNotInSchema', $result );

	}

	public function test_empty_array() {
		$schema = [
			'options' => [
				'type'  => 'array',
				'items' => [
					'type' => 'string',
				],
			],
		];

		$items = [
			'options' => [],
		];

		$result = $this->controller->get_matched_data_with_schema( $schema, $items );
		$this->assertEquals( $items, $result );

	}

	public function test_one_level_nesting() {
		$schema = [
			'country_code' => [
				'type' => 'string',
			],
			'currency'     => [
				'type'    => 'string',
				'default' => 'USD',
			],
			'rate'         => [
				'type' => 'number',
			],
		];

		$items = [
			'country_code' => 'ES',
			'currency'     => 'EUR',
			'rate'         => 7,
		];

		$result = $this->controller->get_matched_data_with_schema( $schema, $items );
		$this->assertEquals( $items, $result );

	}



	public function test_array_of_strings() {
		$schema = [
			'country_code' => [
				'type'  => 'array',
				'items' => [
					'type' => 'string',
				],
			],
			'currency'     => [
				'type'    => 'string',
				'default' => 'USD',
			],
			'rate'         => [
				'type' => 'number',
			],
		];

		$items = [
			'country_code' => [
				'CountryOne',
			],
			'currency'     => 'EUR',
			'rate'         => 7,
		];

		$result = $this->controller->get_matched_data_with_schema( $schema, $items );

		$expected = [
			'country_code' => [ 'CountryOne' ],
			'currency'     => 'EUR',
			'rate'         => 7,
		];
		$this->assertEquals( $expected, $result );

	}

	public function test_array_of_strings_with_default() {
		$schema = [
			'currency'     => [
				'type'    => 'string',
				'default' => 'USD',
			],
			'country_code' => [
				'type'    => 'array',
				'default' => [ 'CountryDefault' ],
				'items'   => [
					'type' => 'string',
				],
			],
		];

		$items = [
			'currency' => 'EUR',
		];

		$result   = $this->controller->get_matched_data_with_schema( $schema, $items );
		$expected = [
			'currency'     => 'EUR',
			'country_code' => [ 'CountryDefault' ],
		];
		$this->assertEquals( $expected, $result );

	}



	public function test_two_level_nesting() {
		$schema = [
			'country_code' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'product' => [
							'type'    => 'string',
							'context' => [ 'view' ],
							'default' => 'ProductDefault',
						],
					],
				],
			],
		];

		$items = [
			'country_code' => [
				[ 'fieldNotInSchema' => 'ProductOne' ],
				[ 'product' => 'ProductTwo' ],
			],
		];

		$result   = $this->controller->get_matched_data_with_schema( $schema, $items );
		$expected = [
			'country_code' => [
				[ 'product' => 'ProductDefault' ],
				[ 'product' => 'ProductTwo' ],
			],
		];
		$this->assertEquals( $expected, $result );
		$this->assertArrayNotHasKey( 'fieldNotInSchema', $result['country_code'][0] );

	}


	public function test_multiple_level_nesting_object() {
		$schema = [
			'country_code' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'product'   => [
							'type'    => 'string',
							'default' => 'ProductDefault',
						],
						'variation' => [
							'type'       => 'object',
							'properties' => [
								'id'         => [
									'type'    => 'number',
									'default' => 0,
								],
								'attributes' => [
									'type'       => 'object',
									'properties' => [
										'name'  => [
											'type' => 'string',
										],
										'color' => [
											'type' => 'string',
										],
									],
								],
							],

						],
					],
				],
			],
		];

		$items = [
			'country_code' => [
				[
					'product'   => 'ProductOne',
					'variation' => [
						'id'         => 1,
						'attributes' => [
							'name'  => 'TestAttribute',
							'color' => 'red',
						],
					],
				],
				[
					'product'          => 'ProductTwo',
					'fieldNotInSchema' => 'fieldNotInSchema',
					'variation'        => [ 'price' => 2 ],
				],
			],
		];

		$result   = $this->controller->get_matched_data_with_schema( $schema, $items );
		$expected = [
			'country_code' => [
				[
					'product'   => 'ProductOne',
					'variation' => [
						'id'         => 1,
						'attributes' => [
							'name'  => 'TestAttribute',
							'color' => 'red',
						],
					],
				],
				[
					'product'   => 'ProductTwo',
					'variation' => [ 'id' => 0 ],
				],
			],
		];
		$this->assertEquals( $expected, $result );
		$this->assertArrayNotHasKey( 'fieldNotInSchema', $result['country_code'][1] );

	}

	public function test_type_array_with_wrong_data() {
		$schema = [
			'country_code' => [
				'type' => 'array',
				'items' => [
					'type' => 'string'
				]
			],
			'rate'         => [
				'type' => 'number',
			],
		];

		$items = [
			'country_code' => 'ES', // It is not array
			'rate'         => 7,
		];

		//In case to receive an incorrect array, it will skip the schema matching and will continue to the next field.
		$result = $this->controller->get_matched_data_with_schema( $schema, $items );
		$this->assertEquals( $items, $result );

	}	

	public function test_type_object_with_wrong_data() {
		$schema = [
			'country_code' => [
				'type' => 'object',
				'properties' => [
					'type' => 'string'
				]
			],
			'rate'         => [
				'type' => 'number',
			],
		];

		$items = [
			'country_code' => 'ES', // It is not an object
			'rate'         => 7,
		];

		//In case to receive an incorrect object, it will skip the schema matching and will continue to the next field.
		$result = $this->controller->get_matched_data_with_schema( $schema, $items );
		$this->assertEquals( $items, $result );

	}	


}
