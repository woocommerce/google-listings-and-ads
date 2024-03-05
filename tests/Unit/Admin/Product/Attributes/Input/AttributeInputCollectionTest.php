<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\AdultInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\AgeGroupInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\AvailabilityDateInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\BrandInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\ColorInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\ConditionInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\GenderInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\GTINInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\IsBundleInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\MaterialInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\MPNInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\MultipackInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\PatternInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\SizeInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\SizeSystemInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\SizeTypeInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AvailabilityDate;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Material;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Multipack;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Pattern;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Size;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeType;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class AttributeInputCollectionTest
 *
 * Test collection of classes extends a class within Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product\Attributes
 */
class AttributeInputCollectionTest extends UnitTest {
	public function test_adult_input() {
		$input = new AdultInput();
		$input
			->set_id( Adult::get_id() )
			->set_name( Adult::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_adult',
				'type'        => 'select',
				'label'       => 'Adult content',
				'description' => 'Whether the product contains nudity or sexually suggestive content',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => [
					''    => 'Default',
					'yes' => 'Yes',
					'no'  => 'No',
				],
				'class'       => 'select short',
				'name'        => 'gla_adult',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-adult',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_adult',
					'label'    => 'Adult content',
					'tooltip'  => 'Whether the product contains nudity or sexually suggestive content',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_age_group_input() {
		$input = new AgeGroupInput();
		$input
			->set_id( AgeGroup::get_id() )
			->set_name( AgeGroup::get_id() )
			->set_options( AgeGroup::get_value_options() );

		$this->assertEquals(
			[
				'id'          => 'gla_ageGroup',
				'type'        => 'select',
				'label'       => 'Age Group',
				'description' => 'Target age group of the item.',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => AgeGroup::get_value_options(),
				'class'       => 'select short',
				'name'        => 'gla_ageGroup',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-ageGroup',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_ageGroup',
					'label'    => 'Age Group',
					'tooltip'  => 'Target age group of the item.',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_availability_date_input() {
		$input = new AvailabilityDateInput();
		$input
			->set_id( AvailabilityDate::get_id() )
			->set_name( AvailabilityDate::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_availabilityDate',
				'type'        => 'datetime',
				'label'       => 'Availability Date',
				'description' => 'The date a preordered or backordered product becomes available for delivery. Required if product availability is preorder or backorder',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_availabilityDate',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-availabilityDate',
				'blockName'  => 'google-listings-and-ads/product-date-time-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_availabilityDate',
					'label'    => 'Availability Date',
					'tooltip'  => 'The date a preordered or backordered product becomes available for delivery. Required if product availability is preorder or backorder',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_brand_input() {
		$input = new BrandInput();
		$input
			->set_id( Brand::get_id() )
			->set_name( Brand::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_brand',
				'type'        => 'text',
				'label'       => 'Brand',
				'description' => 'Brand of the product.',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_brand',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-brand',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_brand',
					'label'    => 'Brand',
					'tooltip'  => 'Brand of the product.',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_color_input() {
		$input = new ColorInput();
		$input
			->set_id( Color::get_id() )
			->set_name( Color::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_color',
				'type'        => 'text',
				'label'       => 'Color',
				'description' => 'Color of the product.',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_color',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-color',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_color',
					'label'    => 'Color',
					'tooltip'  => 'Color of the product.',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_condition_input() {
		$input = new ConditionInput();
		$input
			->set_id( Condition::get_id() )
			->set_name( Condition::get_id() )
			->set_options( Condition::get_value_options() );

		$this->assertEquals(
			[
				'id'          => 'gla_condition',
				'type'        => 'select',
				'label'       => 'Condition',
				'description' => 'Condition or state of the item.',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => Condition::get_value_options(),
				'class'       => 'select short',
				'name'        => 'gla_condition',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-condition',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_condition',
					'label'    => 'Condition',
					'tooltip'  => 'Condition or state of the item.',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_gender_input() {
		$input = new GenderInput();
		$input
			->set_id( Gender::get_id() )
			->set_name( Gender::get_id() )
			->set_options( Gender::get_value_options() );

		$this->assertEquals(
			[
				'id'          => 'gla_gender',
				'type'        => 'select',
				'label'       => 'Gender',
				'description' => 'The gender for which your product is intended.',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => Gender::get_value_options(),
				'class'       => 'select short',
				'name'        => 'gla_gender',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-gender',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_gender',
					'label'    => 'Gender',
					'tooltip'  => 'The gender for which your product is intended.',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_gtin_input() {
		$input = new GTINInput();
		$input
			->set_id( GTIN::get_id() )
			->set_name( GTIN::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_gtin',
				'type'        => 'text',
				'label'       => 'Global Trade Item Number (GTIN)',
				'description' => 'Global Trade Item Number (GTIN) for your item. These identifiers include UPC (in North America), EAN (in Europe), JAN (in Japan), and ISBN (for books)',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_gtin',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-gtin',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_gtin',
					'label'    => 'Global Trade Item Number (GTIN)',
					'tooltip'  => 'Global Trade Item Number (GTIN) for your item. These identifiers include UPC (in North America), EAN (in Europe), JAN (in Japan), and ISBN (for books)',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_is_bundle_input() {
		$input = new IsBundleInput();
		$input
			->set_id( IsBundle::get_id() )
			->set_name( IsBundle::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_isBundle',
				'type'        => 'select',
				'label'       => 'Is Bundle?',
				'description' => 'Whether the item is a bundle of products. A bundle is a custom grouping of different products sold by a merchant for a single price.',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => [
					''    => 'Default',
					'yes' => 'Yes',
					'no'  => 'No',
				],
				'class'       => 'select short',
				'name'        => 'gla_isBundle',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-isBundle',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_isBundle',
					'label'    => 'Is Bundle?',
					'tooltip'  => 'Whether the item is a bundle of products. A bundle is a custom grouping of different products sold by a merchant for a single price.',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_material_input() {
		$input = new MaterialInput();
		$input
			->set_id( Material::get_id() )
			->set_name( Material::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_material',
				'type'        => 'text',
				'label'       => 'Material',
				'description' => 'The material of which the item is made.',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_material',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-material',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_material',
					'label'    => 'Material',
					'tooltip'  => 'The material of which the item is made.',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_mpn_input() {
		$input = new MPNInput();
		$input
			->set_id( MPN::get_id() )
			->set_name( MPN::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_mpn',
				'type'        => 'text',
				'label'       => 'Manufacturer Part Number (MPN)',
				'description' => 'This code uniquely identifies the product to its manufacturer.',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_mpn',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-mpn',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_mpn',
					'label'    => 'Manufacturer Part Number (MPN)',
					'tooltip'  => 'This code uniquely identifies the product to its manufacturer.',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_multipack_input() {
		$input = new MultipackInput();
		$input
			->set_id( Multipack::get_id() )
			->set_name( Multipack::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_multipack',
				'type'        => 'integer',
				'label'       => 'Multipack',
				'description' => 'The number of identical products in a multipack. Use this attribute to indicate that you\'ve grouped multiple identical products for sale as one item.',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_multipack',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-multipack',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_multipack',
					'label'    => 'Multipack',
					'tooltip'  => 'The number of identical products in a multipack. Use this attribute to indicate that you\'ve grouped multiple identical products for sale as one item.',
					'type'     => [ 'value' => 'number' ],
					'min'      => [ 'value' => 0 ],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_pattern_input() {
		$input = new PatternInput();
		$input
			->set_id( Pattern::get_id() )
			->set_name( Pattern::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_pattern',
				'type'        => 'text',
				'label'       => 'Pattern',
				'description' => 'The item\'s pattern (e.g. polka dots).',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_pattern',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-pattern',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_pattern',
					'label'    => 'Pattern',
					'tooltip'  => 'The item\'s pattern (e.g. polka dots).',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_size_input() {
		$input = new SizeInput();
		$input
			->set_id( Size::get_id() )
			->set_name( Size::get_id() );

		$this->assertEquals(
			[
				'id'          => 'gla_size',
				'type'        => 'text',
				'label'       => 'Size',
				'description' => 'Size of the product.',
				'desc_tip'    => true,
				'value'       => null,
				'name'        => 'gla_size',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-size',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_size',
					'label'    => 'Size',
					'tooltip'  => 'Size of the product.',
				],
			],
			$input->get_block_config()
		);
	}

	public function test_size_system_input() {
		$input = new SizeSystemInput();
		$input
			->set_id( SizeSystem::get_id() )
			->set_name( SizeSystem::get_id() )
			->set_options( SizeSystem::get_value_options() );

		$this->assertEquals(
			[
				'id'          => 'gla_sizeSystem',
				'type'        => 'select',
				'label'       => 'Size system',
				'description' => 'System in which the size is specified. Recommended for apparel items.',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => SizeSystem::get_value_options(),
				'class'       => 'select short',
				'name'        => 'gla_sizeSystem',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-sizeSystem',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_sizeSystem',
					'label'    => 'Size system',
					'tooltip'  => 'System in which the size is specified. Recommended for apparel items.',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}

	public function test_size_type_input() {
		$input = new SizeTypeInput();
		$input
			->set_id( SizeType::get_id() )
			->set_name( SizeType::get_id() )
			->set_options( SizeType::get_value_options() );

		$this->assertEquals(
			[
				'id'          => 'gla_sizeType',
				'type'        => 'select',
				'label'       => 'Size type',
				'description' => 'The cut of the item. Recommended for apparel items.',
				'desc_tip'    => true,
				'value'       => null,
				'options'     => SizeType::get_value_options(),
				'class'       => 'select short',
				'name'        => 'gla_sizeType',
				'is_root'     => true,
				'children'    => [],
			],
			$input->get_view_data()
		);

		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-attributes-sizeType',
				'blockName'  => 'google-listings-and-ads/product-select-field',
				'attributes' => [
					'property' => 'meta_data._wc_gla_sizeType',
					'label'    => 'Size type',
					'tooltip'  => 'The cut of the item. Recommended for apparel items.',
					'options'  => $input->get_block_attributes()['options'],
				],
			],
			$input->get_block_config()
		);
	}
}
