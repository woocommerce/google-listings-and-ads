<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Material;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Multipack;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Pattern;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Size;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use WC_Helper_Product;

/**
 * Test suite for Attribute Mapping features in WC Product Adapter
 *
 * @group AttributeMapping
 */
class AttributeMappingWCProductAdapterTest extends UnitTest {
	use ProductTrait;

	/**
	 * Test static sources for attributes
	 */
	public function test_maps_rules_static_attributes() {
		$rules = $this->get_sample_rules();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'mapping_rules'  => $rules,
				'gla_attributes' => [],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( $this->get_rule_attribute( 'gtin' ), $adapted_product->getGtin() );
		$this->assertEquals( $this->get_rule_attribute( 'mpn' ), $adapted_product->getMpn() );
		$this->assertEquals( $this->get_rule_attribute( 'brand' ), $adapted_product->getBrand() );
		$this->assertEquals( $this->get_rule_attribute( 'condition' ), $adapted_product->getCondition() );
		$this->assertEquals( $this->get_rule_attribute( 'gender' ), $adapted_product->getGender() );
		$this->assertContains( $this->get_rule_attribute( 'size' ), $adapted_product->getSizes() );
		$this->assertEquals( $this->get_rule_attribute( 'sizeSystem' ), $adapted_product->getSizeSystem() );
		$this->assertEquals( $this->get_rule_attribute( 'sizeType' ), $adapted_product->getSizeType() );
		$this->assertEquals( $this->get_rule_attribute( 'color' ), $adapted_product->getColor() );
		$this->assertEquals( $this->get_rule_attribute( 'material' ), $adapted_product->getMaterial() );
		$this->assertEquals( $this->get_rule_attribute( 'pattern' ), $adapted_product->getPattern() );
		$this->assertEquals( $this->get_rule_attribute( 'ageGroup' ), $adapted_product->getAgeGroup() );
		$this->assertEquals( 2, $adapted_product->getMultipack() );
		$this->assertEquals( true, $adapted_product->getIsBundle() );
		$this->assertEquals( true, $adapted_product->getAdult() );
	}

	/**
	 * Test dynamic product title source
	 */
	public function test_maps_rules_product_fields_title() {
		$rules = [
			[
				'attribute'               => Brand::get_id(),
				'source'                  => 'product:title',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 'Dummy Product', $adapted_product->getBrand() );
		$this->assertEquals( 'Dummy Variable Product', $adapted_variation->getBrand() );
	}

	/**
	 * Test dynamic product name source
	 */
	public function test_maps_rules_product_fields_name() {
		$rules = [
			[
				'attribute'               => Size::get_id(),
				'source'                  => 'product:name',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( [ 'Dummy Product' ], $adapted_product->getSizes() );
		$this->assertEquals( [ 'Dummy Variable Product' ], $adapted_variation->getSizes() );
	}

	/**
	 * Test dynamic product sku source
	 */
	public function test_maps_rules_product_fields_sku() {
		$rules = [
			[
				'attribute'               => GTIN::get_id(),
				'source'                  => 'product:sku',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 'DUMMY SKU', $adapted_product->getGtin() );
		$this->assertEquals( 'DUMMY SKU VARIABLE HUGE BLUE ANY NUMBER', $adapted_variation->getGtin() );
	}

	/**
	 * Test dynamic stock quantity source
	 */
	public function test_maps_rules_product_fields_stock_quantity() {
		$rules = [
			[
				'attribute'               => Multipack::get_id(),
				'source'                  => 'product:stock_quantity',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 1, $adapted_product->getMultipack() );
		$this->assertEquals( 1, $adapted_variation->getMultipack() );
	}

	/**
	 * Test dynamic product stock status source
	 */
	public function test_maps_rules_product_fields_stock_status() {
		$rules = [
			[
				'attribute'               => Material::get_id(),
				'source'                  => 'product:stock_status',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 'instock', $adapted_product->getMaterial() );
		$this->assertEquals( 'instock', $adapted_variation->getMaterial() );
	}

	/**
	 * Test dynamic product weight source
	 */
	public function test_maps_rules_product_fields_weight() {
		$rules = [
			[
				'attribute'               => MPN::get_id(),
				'source'                  => 'product:weight',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 1.1, $adapted_product->getMpn() );
		$this->assertEquals( 1.2, $adapted_variation->getMpn() );
	}

	/**
	 * Test dynamic product weight (with units) source
	 */
	public function test_maps_rules_product_fields_weight_with_units() {
		$rules = [
			[
				'attribute'               => Pattern::get_id(),
				'source'                  => 'product:weight_with_unit',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( '1.1 kg', $adapted_product->getPattern() );
		$this->assertEquals( '1.2 kg', $adapted_variation->getPattern() );
	}

	/**
	 * Test dynamic product tax class source
	 */
	public function test_maps_rules_product_fields_taxclass() {
		$rules = [
			[
				'attribute'               => Color::get_id(),
				'source'                  => 'product:tax_class',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 'mytax', $adapted_product->getColor() );
		$this->assertEquals( 'mytax', $adapted_variation->getColor() );
	}

	/**
	 * Test dynamic taxonomy source
	 */
	public function test_maps_rules_product_taxonomies() {
		$rules = [
			[
				'attribute'               => Size::get_id(),
				'source'                  => 'taxonomy:pa_size',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Color::get_id(),
				'source'                  => 'taxonomy:pa_number', // set as any
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( [ 's' ], $adapted_product->getSizes() );
		$this->assertEquals( [ 'huge' ], $adapted_variation->getSizes() );
		$this->assertEquals( '0', $adapted_variation->getColor() );
	}

	/**
	 * Test taxonomy source like product_cat, product_tag
	 */
	public function test_maps_rules_product_cat() {
		$rules = [
			[
				'attribute'               => Brand::get_id(),
				'source'                  => 'taxonomy:product_cat',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			]
		];

		$category_1 = wp_insert_term( 'Zulu Category', 'product_cat' );
		$category_2 = wp_insert_term( 'Alpha Category', 'product_cat' );

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules, [ $category_1['term_id'], $category_2['term_id'] ] );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules, [ $category_1['term_id'], $category_2['term_id'] ]  );

		var_dump($adapted_variation->getBrand());
		$this->assertEquals( 'Alpha Category', $adapted_product->getBrand() );
		$this->assertEquals( 'Alpha Category', $adapted_variation->getBrand() );
	}

	/**
	 * Test dynamic taxonomy not found
	 */
	public function test_maps_rules_product_taxonomies_null() {
		$rules = [
			[
				'attribute'               => Color::get_id(),
				'source'                  => 'taxonomy:pa_other',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( '', $adapted_product->getColor() );
		$this->assertEquals( '', $adapted_variation->getColor() );
	}


	/**
	 * Test dynamic taxonomy not found
	 */
	public function test_maps_rules_custom_attributes() {
		$rules = [
			[
				'attribute'               => Color::get_id(),
				'source'                  => 'attribute:custom',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => GTIN::get_id(),
				'source'                  => 'attribute:array', // This won't be loaded because we only accept scalars.
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Brand::get_id(),
				'source'                  => 'attribute:multiple', // Only first value will be loaded.
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$adapted_product   = $this->generate_attribute_mapping_adapted_product( $rules );
		$adapted_variation = $this->generate_attribute_mapping_adapted_product_variant( $rules );

		$this->assertEquals( 'test', $adapted_product->getColor() );
		$this->assertEquals( 'test', $adapted_variation->getColor() );
		$this->assertEquals( '', $adapted_variation->getGtin() );
		$this->assertEquals( '', $adapted_variation->getGtin() );
		$this->assertEquals( 'Value1', $adapted_variation->getBrand() );
		$this->assertEquals( 'Value1', $adapted_variation->getBrand() );
	}


	/**
	 * Test Rule Category Type ONLY and EXCEPT matching logic
	 */
	public function test_rule_only_categories() {
		$category = wp_insert_term( 'Test Category', 'product_cat' );
		$term     = $category['term_id'];

		$rules = [
			[
				'attribute'               => Color::get_id(),
				'source'                  => 'test',
				'category_condition_type' => 'ONLY',
				'categories'              => strval( $term ),
			],
			[
				'attribute'               => Brand::get_id(),
				'source'                  => 'test',
				'category_condition_type' => 'ONLY',
				'categories'              => '999',
			],
			[
				'attribute'               => GTIN::get_id(),
				'source'                  => 'test',
				'category_condition_type' => 'EXCEPT',
				'categories'              => '999',
			],
			[
				'attribute'               => MPN::get_id(),
				'source'                  => 'test',
				'category_condition_type' => 'EXCEPT',
				'categories'              => strval( $term ),
			],
		];

		$adapted_product = $this->generate_attribute_mapping_adapted_product( $rules, [ $term ] );

		$this->assertEquals( 'test', $adapted_product->getColor() );
		$this->assertEquals( '', $adapted_product->getBrand() );
		$this->assertEquals( 'test', $adapted_product->getGtin() );
		$this->assertEquals( '', $adapted_product->getMpn() );

	}

	/**
	 * Test rules priority
	 */
	public function test_gla_attribute_has_priority_over_attribute_mapping_rules() {
		$rules = $this->get_sample_rules();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'mapping_rules'  => $rules,
				'gla_attributes' => [ 'gender' => 'man' ],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( $this->get_rule_attribute( 'condition' ), $adapted_product->getCondition() );
		$this->assertEquals( 'man', $adapted_product->getGender() );
	}

	/**
	 * Test suite setup
	 */
	public function setUp(): void {
		parent::setUp();
		\WC_Tax::create_tax_class( 'mytax' );
	}

	/**
	 * Test suite tear down
	 */
	public function tearDown(): void {
		parent::tearDown();
		\WC_Tax::delete_tax_class_by( 'name', 'mytax' );
	}
}
