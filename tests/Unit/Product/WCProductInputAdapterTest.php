<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductInputAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use WC_Helper_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCProductInputAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class WCProductInputAdapterTest extends UnitTest {

	use ProductTrait;

	public function test_returns_product_input_with_identity() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Adapter title' );
		$product->save();

		$input = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input();

		$this->assertInstanceOf( ProductInput::class, $input );
		$this->assertSame( "gla_{$product->get_id()}", $input->get_offer_id() );
		$this->assertSame( 'en', $input->get_content_language() );
		$this->assertSame( 'US', $input->get_feed_label() );
	}

	public function test_maps_general_attributes() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Adapter title' );
		$product->set_description( 'A description' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'Adapter title', $attrs['title'] );
		$this->assertSame( 'A description', $attrs['description'] );
		$this->assertSame( $product->get_permalink(), $attrs['link'] );
		$this->assertArrayNotHasKey( 'itemGroupId', $attrs );
	}

	public function test_falls_back_to_short_description() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_description( '' );
		$product->set_short_description( 'Short only' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'Short only', $attrs['description'] );
	}

	public function test_strips_shortcodes_from_description() {
		add_shortcode( 'gla_probe_sc', '__return_empty_string' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_description( 'Hello [gla_probe_sc]' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		remove_shortcode( 'gla_probe_sc' );

		$this->assertStringNotContainsString( '[gla_probe_sc]', $attrs['description'] );
	}

	public function test_variation_sets_item_group_id_to_parent() {
		$parent     = WC_Helper_Product::create_variation_product();
		$variations = $parent->get_children();
		$variation  = wc_get_product( $variations[0] );

		$attrs = ( new WCProductInputAdapter( $variation, 'US', $parent ) )->get_product_input()->get_attributes();

		$this->assertSame( (string) $parent->get_id(), $attrs['itemGroupId'] );
	}

	public function test_variation_without_parent_throws() {
		$parent     = WC_Helper_Product::create_variation_product();
		$variations = $parent->get_children();
		$variation  = wc_get_product( $variations[0] );

		$this->expectException( InvalidValue::class );
		new WCProductInputAdapter( $variation, 'US' );
	}

	public function test_maps_price_to_micros() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '19.99' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame(
			[
				'amountMicros' => '19990000',
				'currencyCode' => get_woocommerce_currency(),
			],
			$attrs['price']
		);
	}

	public function test_omits_price_when_no_regular_price() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '' );
		$product->set_price( '' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'price', $attrs );
	}

	public function test_maps_availability_in_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'instock' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'in_stock', $attrs['availability'] );
	}

	public function test_maps_availability_out_of_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'out_of_stock', $attrs['availability'] );
	}

	public function test_omits_images_when_product_has_none() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( '' );
		$product->set_gallery_image_ids( [] );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'imageLink', $attrs );
		$this->assertArrayNotHasKey( 'additionalImageLinks', $attrs );
	}

	public function test_adds_shipping_entry_for_feed_label_country() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( [ [ 'country' => 'US' ] ], $attrs['shipping'] );
	}

	public function test_adds_shipping_entry_per_target_country() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [ 'US', 'CA', 'GB' ] ) )->get_product_input()->get_attributes();

		$this->assertEqualsCanonicalizing( [ 'US', 'CA', 'GB' ], array_column( $attrs['shipping'], 'country' ) );
		$this->assertCount( 3, $attrs['shipping'] );
	}

	public function test_virtual_product_shipping_is_free_with_no_measurements() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( true );
		$product->set_weight( '2' );
		$product->set_length( '10' );
		$product->set_width( '10' );
		$product->set_height( '10' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame(
			[
				[
					'country' => 'US',
					'price'   => [
						'amountMicros' => '0',
						'currencyCode' => get_woocommerce_currency(),
					],
				],
			],
			$attrs['shipping']
		);
		$this->assertArrayNotHasKey( 'shippingWeight', $attrs );
		$this->assertArrayNotHasKey( 'shippingLength', $attrs );
		$this->assertArrayNotHasKey( 'shippingLabel', $attrs );
	}

	public function test_physical_product_maps_dimensions_and_weight() {
		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_weight_unit', 'g' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->set_length( '10' );
		$product->set_width( '20' );
		$product->set_height( '30' );
		$product->set_weight( '500' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertEqualsCanonicalizing(
			[
				'value' => 10.0,
				'unit'  => 'cm',
			],
			$attrs['shippingLength']
		);
		$this->assertEqualsCanonicalizing(
			[
				'value' => 20.0,
				'unit'  => 'cm',
			],
			$attrs['shippingWidth']
		);
		$this->assertEqualsCanonicalizing(
			[
				'value' => 30.0,
				'unit'  => 'cm',
			],
			$attrs['shippingHeight']
		);
		$this->assertEqualsCanonicalizing(
			[
				'value' => 500.0,
				'unit'  => 'g',
			],
			$attrs['shippingWeight']
		);
	}

	public function test_maps_shipping_label_from_shipping_class() {
		$term = wp_insert_term( 'Bulky', 'product_shipping_class' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_shipping_class_id( $term['term_id'] );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'bulky', $attrs['shippingLabel'] );
	}

	public function test_physical_product_without_weight_omits_shipping_weight() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->set_weight( '' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayHasKey( 'shipping', $attrs );
		$this->assertArrayNotHasKey( 'shippingWeight', $attrs );
	}

	public function test_omits_shipping_dimensions_when_not_all_set() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->set_length( '10' );
		$product->set_width( '20' );
		$product->set_height( '' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'shippingLength', $attrs );
		$this->assertArrayNotHasKey( 'shippingWidth', $attrs );
		$this->assertArrayNotHasKey( 'shippingHeight', $attrs );
	}

	public function test_maps_weight_unit_lbs_to_lb() {
		update_option( 'woocommerce_weight_unit', 'lbs' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->set_weight( '3' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'lb', $attrs['shippingWeight']['unit'] );
	}

	public function test_honors_virtual_property_filter_for_shipping() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->set_weight( '2' );
		$product->save();

		add_filter( 'woocommerce_gla_product_property_value_is_virtual', '__return_true' );
		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();
		remove_filter( 'woocommerce_gla_product_property_value_is_virtual', '__return_true' );

		// Forced virtual: free shipping and no weight mapped.
		$this->assertSame(
			[
				'amountMicros' => '0',
				'currencyCode' => get_woocommerce_currency(),
			],
			$attrs['shipping'][0]['price']
		);
		$this->assertArrayNotHasKey( 'shippingWeight', $attrs );
	}

	public function test_variation_maps_shipping() {
		update_option( 'woocommerce_weight_unit', 'g' );

		$parent     = WC_Helper_Product::create_variation_product();
		$variations = $parent->get_children();
		$variation  = wc_get_product( $variations[0] );
		$variation->set_virtual( false );
		$variation->set_weight( '4' );
		$variation->save();

		$attrs = ( new WCProductInputAdapter( $variation, 'US', $parent ) )->get_product_input()->get_attributes();

		$this->assertSame( [ [ 'country' => 'US' ] ], $attrs['shipping'] );
		$this->assertEqualsCanonicalizing(
			[
				'value' => 4.0,
				'unit'  => 'g',
			],
			$attrs['shippingWeight']
		);
	}

	public function test_maps_string_attributes() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter(
			$product,
			'US',
			null,
			[],
			[
				'brand'     => 'Acme',
				'color'     => 'Red',
				'material'  => 'Cotton',
				'pattern'   => 'Striped',
				'mpn'       => 'MPN123',
				'condition' => 'new',
				'ageGroup'  => 'adult',
				'gender'    => 'unisex',
			]
		) )->get_product_input()->get_attributes();

		$this->assertSame( 'Acme', $attrs['brand'] );
		$this->assertSame( 'Red', $attrs['color'] );
		$this->assertSame( 'Cotton', $attrs['material'] );
		$this->assertSame( 'Striped', $attrs['pattern'] );
		$this->assertSame( 'MPN123', $attrs['mpn'] );
		$this->assertSame( 'new', $attrs['condition'] );
		$this->assertSame( 'adult', $attrs['ageGroup'] );
		$this->assertSame( 'unisex', $attrs['gender'] );
	}

	public function test_maps_size_attribute() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [ 'size' => 'XL' ] ) )->get_product_input()->get_attributes();

		$this->assertSame( 'XL', $attrs['size'] );
	}

	public function test_maps_array_attributes() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter(
			$product,
			'US',
			null,
			[],
			[
				'gtin'     => '00012345678905',
				'sizeType' => 'regular',
			]
		) )->get_product_input()->get_attributes();

		$this->assertSame( [ '00012345678905' ], $attrs['gtins'] );
		$this->assertSame( [ 'regular' ], $attrs['sizeTypes'] );
		$this->assertArrayNotHasKey( 'gtin', $attrs );
		$this->assertArrayNotHasKey( 'sizeType', $attrs );
	}

	public function test_coerces_boolean_and_multipack_attributes() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter(
			$product,
			'US',
			null,
			[],
			[
				'isBundle'  => 'yes',
				'adult'     => 'no',
				'multipack' => '6',
			]
		) )->get_product_input()->get_attributes();

		$this->assertSame( true, $attrs['isBundle'] );
		$this->assertSame( false, $attrs['adult'] );
		$this->assertSame( '6', $attrs['multipack'] );
	}

	public function test_skips_unsupported_attribute() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$attrs = ( new WCProductInputAdapter(
			$product,
			'US',
			null,
			[],
			[
				'notAReal' => 'x',
				'brand'    => 'Acme',
			]
		) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'notAReal', $attrs );
		$this->assertSame( 'Acme', $attrs['brand'] );
	}

	public function test_applies_attribute_value_filter() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		add_filter(
			'woocommerce_gla_product_attribute_value_brand',
			function () {
				return 'Filtered';
			}
		);
		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [ 'brand' => 'Original' ] ) )->get_product_input()->get_attributes();
		remove_all_filters( 'woocommerce_gla_product_attribute_value_brand' );

		$this->assertSame( 'Filtered', $attrs['brand'] );
	}

	public function test_applies_attribute_values_override_filter() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		add_filter(
			'woocommerce_gla_product_attribute_values',
			function ( $values ) {
				$values['color'] = 'Blue';
				return $values;
			}
		);
		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [ 'brand' => 'Acme' ] ) )->get_product_input()->get_attributes();
		remove_all_filters( 'woocommerce_gla_product_attribute_values' );

		$this->assertSame( 'Blue', $attrs['color'] );
		$this->assertSame( 'Acme', $attrs['brand'] );
	}

	public function test_applies_mapping_rule_with_static_source() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$rules = [
			[
				'attribute'               => 'brand',
				'source'                  => 'RuleBrand',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'RuleBrand', $attrs['brand'] );
	}

	public function test_mapping_rule_resolves_product_field_source() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( 'SKU-123' );
		$product->save();

		$rules = [
			[
				'attribute'               => 'mpn',
				'source'                  => 'product:sku',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'SKU-123', $attrs['mpn'] );
	}

	public function test_per_product_value_overrides_mapping_rule() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$rules = [
			[
				'attribute'               => 'brand',
				'source'                  => 'RuleBrand',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [ 'brand' => 'ProductBrand' ], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'ProductBrand', $attrs['brand'] );
	}

	public function test_mapping_rule_only_condition_matches_category() {
		$term    = wp_insert_term( 'RuleCat', 'product_cat' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( [ $term['term_id'] ] );
		$product->save();

		$rules = [
			[
				'attribute'               => 'brand',
				'source'                  => 'MatchedBrand',
				'category_condition_type' => 'ONLY',
				'categories'              => (string) $term['term_id'],
			],
			[
				'attribute'               => 'color',
				'source'                  => 'ShouldNotApply',
				'category_condition_type' => 'ONLY',
				'categories'              => '999999',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'MatchedBrand', $attrs['brand'] );
		$this->assertArrayNotHasKey( 'color', $attrs );
	}

	public function test_mapping_rule_skips_unsupported_attribute() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$rules = [
			[
				'attribute'               => 'notAReal',
				'source'                  => 'x',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'notAReal', $attrs );
	}

	public function test_maps_product_types_from_categories() {
		$parent = wp_insert_term( 'Clothing', 'product_cat' );
		$child  = wp_insert_term( 'Shirts', 'product_cat', [ 'parent' => $parent['term_id'] ] );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( [ $child['term_id'] ] );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( [ 'Clothing > Shirts' ], $attrs['productTypes'] );
	}

	public function test_product_types_capped_at_ten() {
		$category_ids = [];
		for ( $i = 1; $i <= 11; $i++ ) {
			$term           = wp_insert_term( "CapCat{$i}", 'product_cat' );
			$category_ids[] = $term['term_id'];
		}

		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( $category_ids );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertCount( 10, $attrs['productTypes'] );
	}

	public function test_mapping_rule_resolves_taxonomy_source() {
		$term    = wp_insert_term( 'TaxoCat', 'product_cat' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( [ $term['term_id'] ] );
		$product->save();

		$rules = [
			[
				'attribute'               => 'material',
				'source'                  => 'taxonomy:product_cat',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'TaxoCat', $attrs['material'] );
	}

	public function test_mapping_rule_resolves_custom_attribute_source() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( 'fabric', 'First | Second' );
		$product->save();

		$rules = [
			[
				'attribute'               => 'pattern',
				'source'                  => 'attribute:fabric',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'First', $attrs['pattern'] );
	}

	public function test_mapping_rule_except_condition_excludes_matching_category() {
		$term    = wp_insert_term( 'ExceptCat', 'product_cat' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( [ $term['term_id'] ] );
		$product->save();

		$rules = [
			[
				'attribute'               => 'brand',
				'source'                  => 'ShouldNotApply',
				'category_condition_type' => 'EXCEPT',
				'categories'              => (string) $term['term_id'],
			],
			[
				'attribute'               => 'color',
				'source'                  => 'ShouldApply',
				'category_condition_type' => 'EXCEPT',
				'categories'              => '999999',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'brand', $attrs );
		$this->assertSame( 'ShouldApply', $attrs['color'] );
	}

	public function test_core_gtin_overrides_per_product_gtin() {
		$product = WC_Helper_Product::create_simple_product();
		if ( ! method_exists( $product, 'set_global_unique_id' ) ) {
			$this->markTestSkipped( 'Core global unique id requires WooCommerce 9.2+.' );
		}
		$product->set_global_unique_id( '12-345' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [ 'gtin' => '99999' ] ) )->get_product_input()->get_attributes();

		$this->assertSame( [ '12345' ], $attrs['gtins'] );
	}

	public function test_override_filter_accepts_mapi_keys() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		add_filter(
			'woocommerce_gla_product_attribute_values',
			function () {
				return [
					'gtins' => [ '999' ],
					'size'  => 'M',
					'color' => 'Blue',
				];
			}
		);
		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();
		remove_all_filters( 'woocommerce_gla_product_attribute_values' );

		$this->assertSame( [ '999' ], $attrs['gtins'] );
		$this->assertSame( 'M', $attrs['size'] );
		$this->assertSame( 'Blue', $attrs['color'] );
	}

	public function test_no_custom_attributes_by_default() {
		$product = WC_Helper_Product::create_simple_product();

		$input = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input();

		$this->assertSame( [], $input->get_custom_attributes() );
	}

	public function test_adapter_custom_attribute_accessors() {
		$product = WC_Helper_Product::create_simple_product();
		$adapter = new WCProductInputAdapter( $product, 'US' );

		$this->assertSame( [], $adapter->get_custom_attributes() );

		$adapter->add_custom_attribute( [ 'name' => 'x', 'value' => '1' ] );
		$this->assertSame( [ [ 'name' => 'x', 'value' => '1' ] ], $adapter->get_custom_attributes() );

		// set_custom_attributes replaces (and reindexes) the existing list.
		$adapter->set_custom_attributes( [ 2 => [ 'name' => 'y', 'value' => '2' ] ] );
		$this->assertSame( [ [ 'name' => 'y', 'value' => '2' ] ], $adapter->get_custom_attributes() );
	}

	public function test_filter_can_add_custom_attributes() {
		$product = WC_Helper_Product::create_simple_product();

		add_filter(
			'woocommerce_gla_product_attribute_values',
			function ( $overrides, $wc_product, $adapter ) {
				$adapter->add_custom_attribute(
					[
						'name'        => 'native_commerce',
						'groupValues' => [ [ 'name' => 'checkout_eligibility', 'value' => 'true' ] ],
					]
				);
				$adapter->add_custom_attribute(
					[
						'name'  => 'merchant_item_id',
						'value' => '123',
					]
				);

				return $overrides;
			},
			10,
			3
		);

		$input = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input();
		remove_all_filters( 'woocommerce_gla_product_attribute_values' );

		$custom_attributes = $input->get_custom_attributes();
		$this->assertCount( 2, $custom_attributes );
		$this->assertSame( 'native_commerce', $custom_attributes[0]['name'] );
		$this->assertSame(
			[ [ 'name' => 'checkout_eligibility', 'value' => 'true' ] ],
			$custom_attributes[0]['groupValues']
		);
		$this->assertSame( 'merchant_item_id', $custom_attributes[1]['name'] );
		$this->assertSame( '123', $custom_attributes[1]['value'] );

		// Custom attributes must not leak into the typed product attributes.
		$attrs = $input->get_attributes();
		$this->assertArrayNotHasKey( 'customAttributes', $attrs );
		$this->assertArrayNotHasKey( 'native_commerce', $attrs );
	}

	public function test_set_custom_attributes_replaces_and_serializes() {
		$product = WC_Helper_Product::create_simple_product();

		$adapter = new WCProductInputAdapter( $product, 'US' );
		$adapter->set_custom_attributes(
			[
				[ 'name' => 'a', 'value' => '1' ],
				[ 'name' => 'b', 'value' => '2' ],
			]
		);

		$serialized = $adapter->get_product_input()->to_array();

		$this->assertArrayHasKey( 'customAttributes', $serialized );
		$this->assertSame(
			[
				[ 'name' => 'a', 'value' => '1' ],
				[ 'name' => 'b', 'value' => '2' ],
			],
			$serialized['customAttributes']
		);
	}
}
