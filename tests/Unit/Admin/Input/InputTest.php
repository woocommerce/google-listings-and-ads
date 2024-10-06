<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Input;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class InputTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Input
 */
class InputTest extends UnitTest {
	/** @var Input $input */
	protected $input;

	public function setUp(): void {
		parent::setUp();
		$this->input = new Input( 'text', 'woocommerce/product-text-field' );
	}

	public function test_constructor_and_init_values() {
		$input = new Input( 'integer', 'woocommerce/product-number-field' );

		$this->assertEquals( 'integer', $input->get_type() );
		$this->assertEquals( 'woocommerce/product-number-field', $input->get_block_name() );
	}

	public function test_set_id_get_id() {
		$this->assertNull( $this->input->get_id() );
		$this->input->set_id( 'color' );
		$this->assertEquals( 'color', $this->input->get_id() );
	}

	public function test_set_label_get_label() {
		$this->assertNull( $this->input->get_label() );
		$this->input->set_label( 'Color' );
		$this->assertEquals( 'Color', $this->input->get_label() );
	}

	public function test_set_description_get_description() {
		$this->assertNull( $this->input->get_description() );
		$this->input->set_description( 'Color of the product' );
		$this->assertEquals( 'Color of the product', $this->input->get_description() );
	}

	public function test_set_value_get_value() {
		$this->assertNull( $this->input->get_value() );
		$this->input->set_value( 'black' );
		$this->assertEquals( 'black', $this->input->get_value() );
	}

	public function test_get_view_id() {
		$this->input->set_name( 'leaf' );
		$this->assertEquals( 'gla_leaf', $this->input->get_view_id() );

		// An input can have a Form parent
		$this->input->set_id( 'leaf' );
		$form = new Form();
		$form->add( $this->input );
		$this->assertEquals( 'gla__leaf', $this->input->get_view_id() );

		// The depth of the tree structure of Form and Input can be > 1. Depth starts from 0
		$nested_input = new Input( 'text', '' );
		$nested_input->set_id( 'mid-node' );
		$form->remove( 'leaf' );
		$form->add( $nested_input );
		$nested_input->add( $this->input );
		$this->assertEquals( 'gla__mid-node_leaf', $this->input->get_view_id() );
	}

	public function test_get_view_data() {
		$form = new Form();
		$leaf = new Input( 'select', '' );

		$this->input
			->set_id( 'color' )
			->set_label( 'Color' )
			->set_value( 'black' )
			->set_description( 'Color of the product' )
			->set_name( 'attr_color' );

		$leaf
			->set_id( 'leaf' )
			->set_name( 'attr_leaf' );

		$form->add( $this->input );
		$this->input->add( $leaf );

		$this->assertEquals(
			[
				// Input
				'id'          => 'gla__color',
				'type'        => 'text',
				'label'       => 'Color',
				'value'       => 'black',
				'description' => 'Color of the product',
				'desc_tip'    => true,
				// Form
				'name'        => 'gla_[attr_color]',
				'is_root'     => false,
				'children'    => [
					'attr_leaf' => [
						// Input
						'id'          => 'gla__color_leaf',
						'type'        => 'select',
						'label'       => null,
						'value'       => null,
						'description' => null,
						'desc_tip'    => true,
						// Form
						'name'        => 'gla_[attr_color][attr_leaf]',
						'is_root'     => false,
						'children'    => [],
					],
				],
			],
			$this->input->get_view_data()
		);
	}

	public function test_set_block_attribute_get_block_attributes() {
		$this->input->set_id( 'color' );

		$this->assertEquals(
			[
				'property' => 'meta_data._wc_gla_color',
				'label'    => null,
				'tooltip'  => null,
			],
			$this->input->get_block_attributes()
		);

		$this->input->set_label( 'Color' );
		$this->input->set_description( 'Color of the product' );

		$this->assertEquals(
			[
				'property' => 'meta_data._wc_gla_color',
				'label'    => 'Color',
				'tooltip'  => 'Color of the product',
			],
			$this->input->get_block_attributes()
		);

		$this->input->set_block_attribute( 'required', true );
		$this->input->set_block_attribute( 'min', 10 );
		$this->input->set_block_attribute( 'placeholder', 'Enter a color' );

		$this->assertEquals(
			[
				'property'    => 'meta_data._wc_gla_color',
				'label'       => 'Color',
				'tooltip'     => 'Color of the product',
				'required'    => true,
				'min'         => 10,
				'placeholder' => 'Enter a color',
			],
			$this->input->get_block_attributes()
		);
	}

	public function test_get_block_config() {
		$this->input->set_id( 'size' );
		$this->input->set_label( 'Size' );
		$this->input->set_description( 'Size of the product' );
		$this->input->set_block_attribute( 'maxLength', 120 );
		$this->input->set_block_attribute( 'help', 'Hello!' );

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-size',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property'  => 'meta_data._wc_gla_size',
					'label'     => 'Size',
					'tooltip'   => 'Size of the product',
					'maxLength' => 120,
					'help'      => 'Hello!',
				],
			],
			$this->input->get_block_config()
		);
	}

	public function test_hidden_prop() {
		$this->assertFalse( $this->input->is_hidden() );
		$this->input->set_hidden( true );
		$this->assertTrue( $this->input->is_hidden() );
		$this->input->set_hidden( false );
	}

	public function test_readonly_prop() {
		$this->assertFalse( isset( $this->input->get_view_data()['custom_attributes']['readonly'] ) );
		$this->input->set_readonly( true );
		$this->assertEquals( $this->input->get_view_data()['custom_attributes']['readonly'], 'readonly' );
		$this->input->set_readonly( false );
	}
}
