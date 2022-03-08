<?php

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;

class MockController extends BaseController {

    public function __construct( $server ) {
		parent::__construct( $server );
	}

    protected function get_schema_properties() : array {
        return [];
    }

    protected function get_schema_title() : string {
        return '';
    }

    public function test_response( $schema, $data ){
        return $this->match_data_with_schema( $schema , $data );
    }


}

class AccountControllerTest extends RESTControllerUnitTest {

	public function setUp() {
		parent::setUp();
        $this->controller = new MockController( $this->server );
	}    

    public function test_one_level_nesting() {

        $schema =  [
            'country_code' => [
                'type'              => 'string',
            ],
            'currency'     => [
                'type'              => 'string',
                'default'           => 'USD',
            ],
            'rate'         => [
                'type'              => 'number',
            ],
        ]; 

        $items = [
            'country_code' => 'ES',
            'currency' => 'EUR',
            'rate' => 7,
        ];        

        $result = $this->controller->test_response( $schema, $items );
        $this->assertEquals( $items, $result );

    }

    public function test_array_of_strings() {

        $schema =  [
            'country_code' => [
                'type'   => 'array',
                'items'  => [
                    'type'       => 'string',
                ],
            ],
            'currency'     => [
                'type'              => 'string',
                'default'           => 'USD',
            ],
            'rate'         => [
                'type'              => 'number',
            ],
        ]; 

        $items = [
            'country_code' => [
                'CountryOne'
            ],
            'currency' => 'EUR',
            'rate' => 7,
        ];

        $result = $this->controller->test_response( $schema, $items );

        $expected = [ "country_code" => ['CountryOne'], "currency" => 'EUR', "rate" => 7 ];
        $this->assertEquals( $expected, $result );

    } 


    public function test_array_of_strings_with_default() {

        $schema =  [
            'currency'     => [
                'type'              => 'string',
                'default'           => 'USD',
            ],
            'country_code' => [
                'type'   => 'array',
                'default'    => ['CountryDefault'],
                'items'  => [
                    'type'       => 'string',
                ],
            ],
        ]; 

        $items = [
            'currency' => 'EUR',
            'rate' => 7,
        ];

        $result = $this->controller->test_response( $schema, $items );
        $expected = [ 'currency' => 'EUR', "country_code" => ['CountryDefault'] ];
        $this->assertEquals( $expected, $result );

    }    



    public function test_two_level_nesting() {

        $schema =  [
            'country_code' => [
                'type'   => 'array',
                'items'  => [
                    'type'       => 'object',
					'properties' => [
						'product' => [
							'type'        => 'string',
							'context'     => [ 'view' ],
                            'default'     => 'TestProduct',
					],
                ],
                ]
            ],
        ]; 

        $items = [
            'country_code' => [
               ["productp" => "ProductOne"],
               ["product" => "ProductTwo"],
            ],
        ];

        $result = $this->controller->test_response( $schema, $items );
        //var_dump( $result );
        $expected = [
            'country_code' => [
               ["product" => "TestProduct"],
               ["product" => "ProductTwo"],
            ],
        ];
        $this->assertEquals( $expected, $result );

    }    


    public function test_multiple_level_nesting_object() {

        $schema =  [
            'country_code' => [
                'type'   => 'array',
                'items'  => [
                    'type'       => 'object',
					'properties' => [
						'product' => [
							'type'        => 'string',
                            'default'     => 'TestProduct',
					],
                        'variation' => [
                        'type'        => 'object',
                        'properties'  => [
                            'id' => [
                                'type' => 'number',
                                'default' => 0
                            ],
                            'attributes' => [
                                'type' => 'variation',
                                'property' => [
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'color' => [
                                        'type' => 'string',
                                    ]
                                ]
                            ],                            
                        ]

                    ],                    
                ],
                ]
            ],
        ]; 

        $items = [
            'country_code' => [
               ["product" => "ProductOne", 'variation' => ['id' => 1, 'attributes' => ['name' => 'TestAttribute', 'color' => 'red'] ]],
               ["product" => "ProductTwo", 'variation' => ['price' => 2 ]],
            ],
        ];

        $result = $this->controller->test_response( $schema, $items );
        $expected = [
            'country_code' => [
                ["product" => "ProductOne", 'variation' => ['id' => 1, 'attributes' => ['name' => 'TestAttribute', 'color' => 'red'] ]],
                ["product" => "ProductTwo", 'variation' => ['id' => 0, 'attributes' => null ]],
             ],
        ];
        $this->assertEquals( $expected, $result );

    }     
    

    /*
    public function test_empty_array_is_obj() {

        $schema =  [           
            'country_code' => [
                'type'   => 'array',
                'items' => [
                    'type' => 'string',
                    
                ],
            ],
            'name' => [
                'type' => 'string'
            ]
        ]; 

        $items = [
            'country_code' => [],
            'name' => 'JM'
        ];

        $result = $this->controller->test_response( $schema, $items );
        var_dump( $result );
        $expected = [
            'country_code' => [
               "productp" => "ProductOne" 
               //["product" => "ProductTwo", "variation" => ['id' => 0 ] ],
            ],
        ];
        $this->assertEquals( $items, $result );

    } */  


}