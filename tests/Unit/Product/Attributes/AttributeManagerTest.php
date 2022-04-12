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
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use WC_Helper_Product;

/**
 * Class AttributeManagerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property AttributeManager $attribute_manager
 */
class AttributeManagerTest extends ContainerAwareUnitTest {

	use PluginHelper;

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
		$attribute_manager = new AttributeManager();

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
		$attribute_manager = new AttributeManager();

		add_filter(
			"woocommerce_gla_product_attribute_types",
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
		$attribute_manager = new AttributeManager();

		add_filter(
			"woocommerce_gla_product_attribute_types",
			function ( array $attribute_types ) {
				$attribute_types[] = \stdClass::class;

				return $attribute_types;
			}
		);

		$this->expectException( InvalidClass::class );
		$attribute_manager->get_attribute_types_for_product_types( [ 'simple' ] );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->attribute_manager = $this->container->get( AttributeManager::class );
	}
}
