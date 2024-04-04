<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Size;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Multipack;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Material;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Pattern;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Helper_Product;

/**
 * Class AttributeManagerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class AttributeManagerTest extends ContainerAwareUnitTest {

	use PluginHelper;
	use ProductTrait;

	/** @var AttributeManager $attribute_manager */
	protected $attribute_manager;

	/** @var MockObject|AttributeMappingRulesQuery $attribute_mapping_rules_query */
	protected $attribute_mapping_rules_query;

	/** @var MockObject|WC $wc */
	protected $wc;

	/** @var array $sample_categories */
	protected $sample_categories = [];

	public function test_update_throws_exception_if_attribute_inapplicable_to_product() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );

		$this->expectException( InvalidValue::class );

		// Brand attribute is not applicable to a variation (see `Brand::get_applicable_product_types`)
		$this->attribute_manager->update( $variation, new Brand( 'Google' ) );
	}

	public function test_update_deletes_attribute_if_it_has_empty_or_null_value() {
		$product = WC_Helper_Product::create_simple_product();

		// Set the attribute first
		$this->attribute_manager->update( $product, new Brand( 'Google' ) );

		$this->assertTrue( $this->attribute_manager->exists( $product, Brand::get_id() ) );

		// Set an empty string as its value
		$this->attribute_manager->update( $product, new Brand( '' ) );

		$this->assertFalse( $this->attribute_manager->exists( $product, Brand::get_id() ) );
	}

	public function test_update_casts_boolean_value_to_string() {
		$product = WC_Helper_Product::create_simple_product();
		$this->attribute_manager->update( $product, new IsBundle( true ) );
		$value = $product->get_meta( $this->prefix_meta_key( IsBundle::get_id() ), true );
		$this->assertEquals( wc_bool_to_string( true ), $value );
	}

	public function test_update_and_get() {
		$product = WC_Helper_Product::create_simple_product();
		$this->attribute_manager->update( $product, new Brand( 'Google' ) );
		$this->assertTrue( $this->attribute_manager->exists( $product, Brand::get_id() ) );

		$attribute = $this->attribute_manager->get( $product, Brand::get_id() );
		$this->assertInstanceOf( Brand::class, $attribute );
		$this->assertEquals( 'Google', $attribute->get_value() );

		$this->assertEquals( 'Google', $this->attribute_manager->get_value( $product, Brand::get_id() ) );
	}

	public function test_get_throws_exception_if_attribute_id_invalid() {
		$product = WC_Helper_Product::create_simple_product();
		$this->expectException( InvalidValue::class );
		$this->attribute_manager->get( $product, 'some_random_string' . rand() );
	}

	public function test_get_throws_exception_if_attribute_inapplicable_to_product() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );

		$this->expectException( InvalidValue::class );

		// Brand attribute is not applicable to a variation (see `Brand::get_applicable_product_types`)
		$this->attribute_manager->get( $variation, Brand::get_id() );
	}

	public function test_get_returns_null_if_attribute_is_not_set() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertFalse( $this->attribute_manager->exists( $product, Brand::get_id() ) );
		$this->assertNull( $this->attribute_manager->get( $product, Brand::get_id() ) );
	}

	public function test_get_value_returns_null_if_attribute_is_not_set() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertFalse( $this->attribute_manager->exists( $product, Brand::get_id() ) );
		$this->assertNull( $this->attribute_manager->get_value( $product, Brand::get_id() ) );
	}

	public function test_get_all_returns_set_attributes() {
		$product = WC_Helper_Product::create_simple_product();

		$this->attribute_manager->update( $product, new Brand( 'Google' ) );
		$this->attribute_manager->update( $product, new IsBundle( false ) );
		$this->attribute_manager->update( $product, new GTIN( '12345678' ) );

		$attributes = $this->attribute_manager->get_all( $product );

		$this->assertCount( 3, $attributes );
		$this->assertContainsOnlyInstancesOf( AttributeInterface::class, $attributes );
		$this->assertArrayHasKey( Brand::get_id(), $attributes );
		$this->assertArrayHasKey( IsBundle::get_id(), $attributes );
		$this->assertArrayHasKey( GTIN::get_id(), $attributes );
		$this->assertEquals( 'Google', $attributes[ Brand::get_id() ]->get_value() );
		$this->assertEquals( false, $attributes[ IsBundle::get_id() ]->get_value() );
		$this->assertEquals( '12345678', $attributes[ GTIN::get_id() ]->get_value() );
	}

	public function test_get_all_values_returns_set_attributes() {
		$product = WC_Helper_Product::create_simple_product();

		$this->attribute_manager->update( $product, new Brand( 'Google' ) );
		$this->attribute_manager->update( $product, new IsBundle( false ) );
		$this->attribute_manager->update( $product, new GTIN( '12345678' ) );

		$attributes = $this->attribute_manager->get_all_values( $product );

		$this->assertCount( 3, $attributes );
		$this->assertArrayHasKey( Brand::get_id(), $attributes );
		$this->assertArrayHasKey( IsBundle::get_id(), $attributes );
		$this->assertArrayHasKey( GTIN::get_id(), $attributes );
		$this->assertEquals( 'Google', $attributes[ Brand::get_id() ] );
		$this->assertEquals( false, $attributes[ IsBundle::get_id() ] );
		$this->assertEquals( '12345678', $attributes[ GTIN::get_id() ] );
	}

	public function test_delete_throws_exception_if_attribute_id_invalid() {
		$product = WC_Helper_Product::create_simple_product();
		$this->expectException( InvalidValue::class );
		$this->attribute_manager->delete( $product, 'some_random_string' . rand() );
	}

	public function test_delete_throws_exception_if_attribute_inapplicable_to_product() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );

		$this->expectException( InvalidValue::class );

		// Brand attribute is not applicable to a variation (see `Brand::get_applicable_product_types`)
		$this->attribute_manager->delete( $variation, Brand::get_id() );
	}

	public function test_delete_and_exists() {
		$product = WC_Helper_Product::create_simple_product();
		$this->attribute_manager->update( $product, new Brand( 'Google' ) );
		$this->assertTrue( $this->attribute_manager->exists( $product, Brand::get_id() ) );
		$this->attribute_manager->delete( $product, Brand::get_id() );
		$this->assertFalse( $this->attribute_manager->exists( $product, Brand::get_id() ) );
	}

	public function test_get_attribute_types_for_product() {
		$variable = WC_Helper_Product::create_variation_product();
		$types    = $this->attribute_manager->get_attribute_types_for_product( $variable );
		$this->assertArrayHasKey( Brand::get_id(), $types );
		$this->assertArrayHasKey( Adult::get_id(), $types );

		$variation = wc_get_product( $variable->get_children()[0] );
		$types     = $this->attribute_manager->get_attribute_types_for_product( $variation );
		$this->assertArrayNotHasKey( Brand::get_id(), $types );
		$this->assertArrayHasKey( Adult::get_id(), $types );
	}

	public function test_get_attribute_types_for_product_types() {
		$types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'variable' ] );
		$this->assertArrayHasKey( Brand::get_id(), $types );
		$this->assertArrayHasKey( Adult::get_id(), $types );

		$types = $this->attribute_manager->get_attribute_types_for_product_types( [ 'variation' ] );
		$this->assertArrayNotHasKey( Brand::get_id(), $types );
		$this->assertArrayHasKey( Adult::get_id(), $types );
	}

	public function test_can_add_new_applicable_product_type_to_an_attribute_via_filter() {
		// We need a new instance of AttributeManager because the attribute map created by `AttributeManager:map_attribute_types`
		// is cached when called previously, which mean that it can not be modified by filters.
		// Since we can not place the filters to run before `AttributeManager:map_attribute_types`, it's best to create a new instance of our class.
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$brand_id = Brand::get_id();
		add_filter(
			"woocommerce_gla_attribute_applicable_product_types_{$brand_id}",
			function ( array $applicable_types ) {
				$applicable_types[] = 'some_type_of_product';

				return $applicable_types;
			}
		);

		$gtin_id = GTIN::get_id();
		add_filter(
			"woocommerce_gla_attribute_applicable_product_types_{$gtin_id}",
			function ( array $applicable_types ) {
				return array_diff( $applicable_types, [ 'simple' ] );
			}
		);

		$this->assertArrayHasKey( Brand::get_id(), $attribute_manager->get_attribute_types_for_product_types( [ 'some_type_of_product' ] ) );
		$this->assertArrayNotHasKey( GTIN::get_id(), $attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] ) );
	}

	public function test_can_modify_attribute_types_via_filter() {
		// We need a new instance of AttributeManager because the attribute map created by `AttributeManager:map_attribute_types`
		// is cached when called previously, which mean that it can not be modified by filters.
		// Since we can not place the filters to run before `AttributeManager:map_attribute_types`, it's best to create a new instance of our class.
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		add_filter(
			'woocommerce_gla_product_attribute_types',
			function ( array $attribute_types ) {
				return array_diff( $attribute_types, [ Brand::class ] );
			}
		);
		$this->assertArrayNotHasKey( Brand::get_id(), $attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] ) );
	}

	public function test_adding_invalid_attribute_type_via_filter_throws_exception() {
		// We need a new instance of AttributeManager because the attribute map created by `AttributeManager:map_attribute_types`
		// is cached when called previously, which mean that it can not be modified by filters.
		// Since we can not place the filters to run before `AttributeManager:map_attribute_types`, it's best to create a new instance of our class.
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		add_filter(
			'woocommerce_gla_product_attribute_types',
			function ( array $attribute_types ) {
				$attribute_types[] = \stdClass::class;

				return $attribute_types;
			}
		);

		$this->expectException( InvalidClass::class );
		$attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );
	}

	/**
	 * Test static sources for attributes
	 */
	public function test_simple_product_with_static_sources() {
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$rules = $this->get_sample_rules();
		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $rules );

		$product    = WC_Helper_Product::create_simple_product( false );
		$attributes = $attribute_manager->get_all_aggregated_values( $product );

		foreach ( $rules as $rule ) {
			$this->assertEquals( $rule['source'], $attributes[ $rule['attribute'] ] );
		}
	}

	/**
	 * Test dynamic product name source with a simple product
	 */
	public function test_maps_rules_simple_products() {
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $this->get_sample_dynamic_source_mapping_rules() );
		$this->wc->expects( $this->exactly( 0 ) )->method( 'get_product' );

		// Simple Product
		$attributes = $attribute_manager->get_all_aggregated_values( $this->generate_attribute_mapping_simple_product() );

		$this->assertEquals( 'Dummy Product', $attributes[ Brand::get_id() ] );
		$this->assertEquals( 'Dummy Product', $attributes[ Size::get_id() ] );
		$this->assertEquals( 'DUMMY SKU', $attributes[ GTIN::get_id() ] );
		$this->assertEquals( 1, $attributes[ Multipack::get_id() ] );
		$this->assertEquals( 'instock', $attributes[ Material::get_id() ] );
		$this->assertEquals( 1.1, $attributes[ MPN::get_id() ] );
		$this->assertEquals( '1.1 kg', $attributes[ Pattern::get_id() ] );
		$this->assertEquals( 'mytax', $attributes[ Color::get_id() ] );
	}

	/**
	 * Test dynamic product name source with a variant product
	 */
	public function test_maps_rules_variant_products() {
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );
		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $this->get_sample_dynamic_source_mapping_rules() );

		$product = $this->generate_attribute_mapping_variant_product();
		$this->wc->expects( $this->exactly( 1 ) )->method( 'get_product' )->willReturn( $product['parent'] );

		// Variation product
		$attributes = $attribute_manager->get_all_aggregated_values( $product['variation'] );
		$this->assertEquals( 'Dummy Variable Product', $attributes[ Brand::get_id() ] );
		$this->assertEquals( 'Dummy Variable Product', $attributes[ Size::get_id() ] );
		$this->assertEquals( 'DUMMY SKU VARIABLE HUGE BLUE ANY NUMBER', $attributes[ GTIN::get_id() ] );
		$this->assertEquals( 1, $attributes[ Multipack::get_id() ] );
		$this->assertEquals( 'instock', $attributes[ Material::get_id() ] );
		$this->assertEquals( 1.2, $attributes[ MPN::get_id() ] );
		$this->assertEquals( '1.2 kg', $attributes[ Pattern::get_id() ] );
		$this->assertEquals( 'mytax', $attributes[ Color::get_id() ] );
	}

	/**
	 * Test dynamic taxonomy source with simple products
	 */
	public function test_maps_rules_simple_product_taxonomies() {
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $this->get_sample_taxonomy_mapping_rules() );
		$this->wc->expects( $this->exactly( 0 ) )->method( 'get_product' );

		// Simple Product
		$attributes = $attribute_manager->get_all_aggregated_values( $this->generate_attribute_mapping_simple_product( $this->sample_categories ) );
		$this->assertEquals( 's', $attributes[ Size::get_id() ] );
		$this->assertEquals( '', $attributes[ Color::get_id() ] );
		$this->assertEquals( 'Alpha Category', $attributes[ Brand::get_id() ] );
		$this->assertEquals( '', $attributes[ MPN::get_id() ] );
	}

	/**
	 * Test dynamic taxonomy source with variable products
	 */
	public function test_maps_rules_simple_variable_taxonomies() {
		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$product = $this->generate_attribute_mapping_variant_product( $this->sample_categories );
		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $this->get_sample_taxonomy_mapping_rules() );
		$this->wc->method( 'get_product' )->willReturn( $product['parent'] );

		// Variation product
		$attributes = $attribute_manager->get_all_aggregated_values( $product['variation'] );

		$this->assertEquals( 'huge', $attributes[ Size::get_id() ] );
		$this->assertEquals( '0', $attributes[ Color::get_id() ] );
		$this->assertEquals( 'Alpha Category', $attributes[ Brand::get_id() ] );
		$this->assertEquals( '', $attributes[ MPN::get_id() ] );
	}

	/**
	 * Test mpapping rules with custom attributes
	 */
	public function test_custom_attributes() {
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

		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );
		$product           = $this->generate_attribute_mapping_variant_product( $this->sample_categories );
		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $rules );
		$this->wc->method( 'get_product' )->willReturn( $product['parent'] );

		// Variation product
		$attributes = $attribute_manager->get_all_aggregated_values( $product['variation'] );
		$this->assertEquals( 'test', $attributes[ Color::get_id() ] );
		$this->assertEquals( '', $attributes[ GTIN::get_id() ] );
		$this->assertEquals( 'Value1', $attributes[ Brand::get_id() ] );
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

		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $rules );
		$this->wc->expects( $this->exactly( 0 ) )->method( 'get_product' );

		// Simple Product
		$attributes = $attribute_manager->get_all_aggregated_values( $this->generate_attribute_mapping_simple_product( [ $term ] ) );
		$this->assertEquals( 'test', $attributes[ Color::get_id() ] );
		$this->assertEquals( 'test', $attributes[ GTIN::get_id() ] );

		$this->assertArrayNotHasKey( Brand::get_id(), $attributes );
		$this->assertArrayNotHasKey( MPN::get_id(), $attributes );
	}

	/**
	 * Test rules priority
	 */
	public function test_gla_attribute_has_priority_over_attribute_mapping_rules() {
		$rules = $this->get_sample_rules();

		// 'gla_attributes' => [ 'gender' => 'man' ],

		$product = $this->generate_attribute_mapping_simple_product();

		$product->add_meta_data( $this->prefix_meta_key( Gender::get_id() ), 'man' );

		$product->save_meta_data();

		$attribute_manager = new AttributeManager( $this->attribute_mapping_rules_query, $this->wc );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 1 ) )->method( 'get_results' )->willReturn( $rules );
		$this->wc->expects( $this->exactly( 0 ) )->method( 'get_product' );

		// Simple Product
		$attributes = $attribute_manager->get_all_aggregated_values( $product );

		$this->assertEquals( $this->get_rule_attribute( 'condition' ), $attributes[ Condition::get_id() ] );
		$this->assertEquals( 'man', $attributes[ Gender::get_id() ] );
	}


	protected function get_sample_taxonomy_mapping_rules() {
		return [
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
			[
				'attribute'               => Brand::get_id(),
				'source'                  => 'taxonomy:product_cat',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => MPN::get_id(),
				'source'                  => 'taxonomy:pa_other',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];
	}

	protected function get_sample_dynamic_source_mapping_rules() {
		return [
			[
				'attribute'               => Brand::get_id(),
				'source'                  => 'product:title',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Size::get_id(),
				'source'                  => 'product:name',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => GTIN::get_id(),
				'source'                  => 'product:sku',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Multipack::get_id(),
				'source'                  => 'product:stock_quantity',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Material::get_id(),
				'source'                  => 'product:stock_status',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => MPN::get_id(),
				'source'                  => 'product:weight',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Pattern::get_id(),
				'source'                  => 'product:weight_with_unit',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => Color::get_id(),
				'source'                  => 'product:tax_class',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$category_1 = wp_insert_term( 'Zulu Category', 'product_cat' );
		$category_2 = wp_insert_term( 'Alpha Category', 'product_cat' );

		$this->sample_categories = [ $category_1['term_id'], $category_2['term_id'] ];

		$this->attribute_mapping_rules_query = $this->createMock( AttributeMappingRulesQuery::class );
		$this->wc                            = $this->createMock( WC::class );

		$this->attribute_manager = $this->container->get( AttributeManager::class );
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
