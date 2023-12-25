<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesForm;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;

/**
 * Class AttributesFormTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product\Attributes
 */
class AttributesFormTest extends ContainerAwareUnitTest {

	/** @var AttributeManager $attribute_manager */
	protected $attribute_manager;

	public function setUp(): void {
		parent::setUp();
		$this->attribute_manager = $this->container->get( AttributeManager::class );
	}

	public function test_add_attribute_remove_attribute() {
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );

		$form = new AttributesForm( $attribute_types );

		$this->assertNotEmpty( $form->get_children() );
		$this->assertEquals( count( $attribute_types ), count( $form->get_children() ) );

		foreach ( $attribute_types as $attribute_type ) {
			$attribute = new $attribute_type();
			$this->assertArrayHasKey( $attribute->get_id(), $form->get_children() );
		}

		$mpn_attribute_type = $attribute_types['mpn'];
		$mpn_attribute      = new $mpn_attribute_type();
		$form->remove_attribute( $mpn_attribute_type );
		$this->assertArrayNotHasKey( $mpn_attribute->get_id(), $form->get_children() );

		$form->add_attribute( $mpn_attribute_type );
		$this->assertArrayHasKey( $mpn_attribute->get_id(), $form->get_children() );
	}

	public function test_set_attribute_input() {
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );

		$mpn_attribute_type = $attribute_types['mpn'];
		$mpn_input_type     = call_user_func( [ $mpn_attribute_type, 'get_input_type' ] );

		$gender_attribute_type = $attribute_types['gender'];
		$gender_input_type     = call_user_func( [ $gender_attribute_type, 'get_input_type' ] );

		$form = new AttributesForm( [] );

		// It won't set attribute input if the given attribute has not yet been set
		$this->assertCount( 0, $form->get_children() );
		$form->set_attribute_input( $mpn_attribute_type, $mpn_input_type );
		$this->assertCount( 0, $form->get_children() );

		// Add MPN as the existing attribute to be set with another input
		$form->add_attribute( $mpn_attribute_type );
		$this->assertCount( 1, $form->get_children() );
		$this->assertInstanceOf( $mpn_input_type, $form->get_children()['mpn'] );

		// Set MPN's input to GenderInput
		$form->set_attribute_input( $mpn_attribute_type, $gender_input_type );
		$this->assertCount( 1, $form->get_children() );
		$this->assertInstanceOf( $gender_input_type, $form->get_children()['mpn'] );
	}

	public function test_init_input_initialization() {
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );

		$gender_attribute_type = $attribute_types['gender'];
		$gender_input_type     = call_user_func( [ $gender_attribute_type, 'get_input_type' ] );

		$gender_input = new $gender_input_type();

		$this->assertNull( $gender_input->get_id() );
		$this->assertEquals( '', $gender_input->get_name() );
		$this->assertEquals( [], $gender_input->get_options() );

		$gender_input = AttributesForm::init_input( $gender_input, new $gender_attribute_type() );

		$this->assertEquals( 'gender', $gender_input->get_id() );
		$this->assertEquals( 'gender', $gender_input->get_name() );
		$this->assertEquals(
			[
				''       => 'Default',
				'male'   => 'Male',
				'female' => 'Female',
				'unisex' => 'Unisex',
			],
			$gender_input->get_options()
		);
	}


	public function test_init_input_transformation() {
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );

		$mpn_attribute_type = $attribute_types['mpn'];
		$mpn_input_type     = call_user_func( [ $mpn_attribute_type, 'get_input_type' ] );

		$mpn_input = AttributesForm::init_input( new $mpn_input_type(), new $mpn_attribute_type() );

		$this->assertInstanceOf( Text::class, $mpn_input );

		// Test the input transform an instance from the non-selectable class to SelectWithTextInput
		// when it gets any option from filter
		add_filter(
			'woocommerce_gla_product_attribute_value_options_mpn',
			function ( array $value_options ) {
				$value_options['from_integration'] = 'From integration value';
				return $value_options;
			}
		);

		$transformed_mpn_input = AttributesForm::init_input( new $mpn_input_type(), new $mpn_attribute_type() );

		$this->assertInstanceOf( SelectWithTextInput::class, $transformed_mpn_input );
		$this->assertEquals( $mpn_input->get_id(), $transformed_mpn_input->get_id() );
		$this->assertEquals( $mpn_input->get_name(), $transformed_mpn_input->get_name() );
		$this->assertEquals( $mpn_input->get_label(), $transformed_mpn_input->get_label() );
		$this->assertEquals( $mpn_input->get_description(), $transformed_mpn_input->get_description() );
	}

	public function test_get_attribute_product_types() {
		$attribute_types       = $this->attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );
		$bundle_attribute_type = $attribute_types['isBundle'];

		$this->assertEquals(
			[
				'visible' => [ 'simple', 'variation' ],
				'hidden'  => [],
			],
			AttributesForm::get_attribute_product_types( $bundle_attribute_type )
		);

		add_filter(
			'woocommerce_gla_attribute_applicable_product_types_isBundle',
			function ( array $applicable_types ) {
				$applicable_types[] = 'bundle';
				return $applicable_types;
			}
		);

		add_filter(
			'woocommerce_gla_attribute_hidden_product_types_isBundle',
			function ( array $applicable_types ) {
				$applicable_types[] = 'simple';
				return $applicable_types;
			}
		);

		$this->assertEquals(
			[
				'visible' => [
					'1' => 'variation',
					'2' => 'bundle',
				],
				'hidden'  => [ 'simple' ],
			],
			AttributesForm::get_attribute_product_types( $bundle_attribute_type )
		);
	}

	public function test_get_view_data() {
		add_filter(
			'woocommerce_gla_attribute_hidden_product_types_isBundle',
			function ( array $applicable_types ) {
				$applicable_types[] = 'simple';
				return $applicable_types;
			}
		);

		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );

		$form = new AttributesForm( $attribute_types );
		$form->set_name( 'attributes' );

		$view_data = $form->get_view_data();

		$this->assertEquals( 'gla_attributes', $view_data['name'] );
		$this->assertTrue( $view_data['is_root'] );
		$this->assertNotEmpty( $view_data['children'] );
		$this->assertEquals( count( $attribute_types ), count( $view_data['children'] ) );

		foreach ( $view_data['children'] as $id => $child_view_data ) {
			$attribute_type          = $attribute_types[ $id ];
			$attribute_product_types = AttributesForm::get_attribute_product_types( $attribute_type );
			$gla_wrapper_class       = $child_view_data['gla_wrapper_class'];

			foreach ( $attribute_product_types['visible'] as $visible_type ) {
				$this->assertMatchesRegularExpression( "/(^| )show_if_{$visible_type}( |$)/", $gla_wrapper_class );
			}

			foreach ( $attribute_product_types['hidden'] as $hidden_type ) {
				$this->assertMatchesRegularExpression( "/(^| )hide_if_{$hidden_type}( |$)/", $gla_wrapper_class );
			}
		}
	}
}
