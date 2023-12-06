<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\BooleanSelect;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\DateTime;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Integer;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class InputCollectionTest
 *
 * Test collection of classes extends the Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Input class
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Input
 */
class InputCollectionTest extends UnitTest {
	public function test_boolean_select() {
		$input = new BooleanSelect();
		$input->set_id( 'test-boolean-select' );

		$this->assertEquals( 'select', $input->get_type() );
		$this->assertEquals( 'google-listings-and-ads/product-select-field', $input->get_block_name() );

		// BooleanSelect doesn't reflect the call of set_options method
		$input->set_options( [] );

		$this->assertEquals(
			[
				''    => 'Default',
				'yes' => 'Yes',
				'no'  => 'No',
			],
			$input->get_options()
		);

		$this->assertEquals(
			[
				[
					'label' => 'Default',
					'value' => '',
				],
				[
					'label' => 'Yes',
					'value' => 'yes',
				],
				[
					'label' => 'No',
					'value' => 'no',
				],
			],
			$input->get_block_attributes()['options']
		);

		// Null by default
		$this->assertNull( $input->get_view_data()['value'] );

		// Convert to a 'yes' or 'no' if the data is the bool type
		$input->set_data( true );
		$this->assertEquals( 'yes', $input->get_view_data()['value'] );

		$input->set_data( false );
		$this->assertEquals( 'no', $input->get_view_data()['value'] );
	}

	public function test_date_time() {
		$input = new DateTime();

		$this->assertEquals( 'datetime', $input->get_type() );

		// Null by default
		$view_data = $input->get_view_data();

		$this->assertNull( $view_data['value'] );
		$this->assertArrayNotHasKey( 'date', $view_data );
		$this->assertArrayNotHasKey( 'time', $view_data );

		// Set date and time data with a string
		$input->set_data( '2023-09-04T08:42:00+00:00' );
		$view_data = $input->get_view_data();

		$this->assertEquals( '2023-09-04 08:42:00', $view_data['value'] );
		$this->assertEquals( '2023-09-04', $view_data['date'] );
		$this->assertEquals( '08:42', $view_data['time'] );

		// Set date and time data with a key-value array
		$input->set_data(
			[
				'date' => '2024-01-02',
				'time' => '19:27:56',
			]
		);
		$view_data = $input->get_view_data();

		$this->assertEquals( '2024-01-02 19:27:56', $view_data['value'] );
		$this->assertEquals( '2024-01-02', $view_data['date'] );
		$this->assertEquals( '19:27', $view_data['time'] );
	}

	public function test_integer() {
		$input = new Integer();

		$this->assertEquals( 'integer', $input->get_type() );
		$this->assertEquals( 'woocommerce/product-number-field', $input->get_block_name() );
	}

	public function test_select() {
		$input = new Select();
		$input->set_id( 'test-select' );

		$this->assertEquals( 'select', $input->get_type() );
		$this->assertEquals( 'google-listings-and-ads/product-select-field', $input->get_block_name() );
		$this->assertEquals( 'select short', $input->get_view_data()['class'] );

		// Empty options by default
		$this->assertEquals( [], $input->get_options() );
		$this->assertEquals( [], $input->get_view_data()['options'] );
		$this->assertEquals( [], $input->get_block_attributes()['options'] );

		// Set and get options
		$options = [
			'foo' => 'bar',
			'hi'  => 'hello',
		];
		$input->set_options( $options );

		$this->assertEquals( $options, $input->get_options() );
		$this->assertEquals( $options, $input->get_view_data()['options'] );
		$this->assertEquals(
			[
				[
					'label' => 'bar',
					'value' => 'foo',
				],
				[
					'label' => 'hello',
					'value' => 'hi',
				],
			],
			$input->get_block_attributes()['options']
		);
	}

	public function test_select_with_text_input() {
		$input = new SelectWithTextInput();
		$input->set_name( 'name' );
		$input->set_id( 'test-select-with-text-input' );

		$this->assertEquals( 'select-with-text-input', $input->get_type() );
		$this->assertEquals( 'google-listings-and-ads/product-select-with-text-field', $input->get_block_name() );

		// Default view data
		$this->assertEquals(
			[
				'id'                => 'gla_name',
				'type'              => 'select-with-text-input',
				'label'             => null,
				'description'       => null,
				'desc_tip'          => true,
				'value'             => null,
				'name'              => 'gla_name',
				'is_root'           => true,
				'children'          => [
					'_gla_select'       => [
						'id'          => 'gla_name__gla_select',
						'type'        => 'select',
						'label'       => null,
						'description' => null,
						'desc_tip'    => true,
						'value'       => null,
						'name'        => 'gla_name[_gla_select]',
						'is_root'     => false,
						'children'    => [],
						'class'       => 'select short',
						'options'     => [
							'_gla_custom_value' => 'Enter a custom value',
						],
					],
					'_gla_custom_value' => [
						'id'            => 'gla_name__gla_custom_value',
						'type'          => 'text',
						'label'         => 'Enter your value',
						'description'   => null,
						'desc_tip'      => true,
						'value'         => null,
						'name'          => 'gla_name[_gla_custom_value]',
						'is_root'       => false,
						'children'      => [],
						'wrapper_class' => 'custom-input',
					],
				],
				'gla_wrapper_class' => ' select-with-text-input',
			],
			$input->get_view_data()
		);

		$this->assertEquals( '_gla_custom_value', $input->get_block_attributes()['customInputValue'] );
		$this->assertEquals(
			[
				[
					'label' => 'Enter a custom value',
					'value' => '_gla_custom_value',
				],
			],
			$input->get_block_attributes()['options']
		);

		// After calling setters
		$input
			->set_label( 'Hi label' )
			->set_description( 'Hello description' )
			->set_options(
				[
					''               => 'Default name',
					'use_admin_name' => 'Use admin name',
				]
			);

		$this->assertEquals(
			[
				'id'                => 'gla_name',
				'type'              => 'select-with-text-input',
				'label'             => 'Hi label',
				'description'       => 'Hello description',
				'desc_tip'          => true,
				'value'             => null,
				'name'              => 'gla_name',
				'is_root'           => true,
				'children'          => [
					'_gla_select'       => [
						'id'          => 'gla_name__gla_select',
						'type'        => 'select',
						'label'       => 'Hi label',
						'description' => 'Hello description',
						'desc_tip'    => true,
						'value'       => null,
						'name'        => 'gla_name[_gla_select]',
						'is_root'     => false,
						'children'    => [],
						'class'       => 'select short',
						'options'     => [
							'_gla_custom_value' => 'Enter a custom value',
							''                  => 'Default name',
							'use_admin_name'    => 'Use admin name',
						],
					],
					'_gla_custom_value' => [
						'id'            => 'gla_name__gla_custom_value',
						'type'          => 'text',
						'label'         => 'Enter your value',
						'description'   => null,
						'desc_tip'      => true,
						'value'         => null,
						'name'          => 'gla_name[_gla_custom_value]',
						'is_root'       => false,
						'children'      => [],
						'wrapper_class' => 'custom-input',
					],
				],
				'gla_wrapper_class' => ' select-with-text-input',
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				[
					'label' => 'Default name',
					'value' => '',
				],
				[
					'label' => 'Use admin name',
					'value' => 'use_admin_name',
				],
				[
					'label' => 'Enter a custom value',
					'value' => '_gla_custom_value',
				],
			],
			$input->get_block_attributes()['options']
		);

		// Selected an option other than value from the custom text input
		$input->set_data(
			[
				'_gla_select'       => 'use_admin_name',
				'_gla_custom_value' => null,
			]
		);

		$this->assertEquals( 'use_admin_name', $input->get_view_data()['value'] );
		$this->assertEquals( 'use_admin_name', $input->get_view_data()['children']['_gla_select']['value'] );
		$this->assertNull( $input->get_view_data()['children']['_gla_custom_value']['value'] );

		// Selected the _gla_custom_value option and entered a value via the custom text input
		$input->set_data(
			[
				'_gla_select'       => 'mismatching any option',
				'_gla_custom_value' => 'Say my name!',
			]
		);

		$this->assertEquals( 'Say my name!', $input->get_view_data()['value'] );
		$this->assertEquals( '_gla_custom_value', $input->get_view_data()['children']['_gla_select']['value'] );
		$this->assertEquals( 'Say my name!', $input->get_view_data()['children']['_gla_custom_value']['value'] );
	}


	public function test_text() {
		$input = new Text();

		$this->assertEquals( 'text', $input->get_type() );
		$this->assertEquals( 'woocommerce/product-text-field', $input->get_block_name() );
	}
}
