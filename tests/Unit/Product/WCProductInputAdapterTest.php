<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductInputAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use WC_DateTime;
use WC_Helper_Product;
use WC_Tax;

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

	public function test_omits_price_when_currency_override_cannot_be_converted() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '19.99' );
		$product->save();

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_product_price_in_currency' )->willReturn( null );

		$attrs = ( new WCProductInputAdapter( $product, 'AE', null, [], [], [], 'AE-EN-AED', '', 'AED', $wpml ) )->get_product_input()->get_attributes();

		// A store-currency amount must never be submitted under a
		// non-store-currency feed label, so the price stays unset.
		$this->assertArrayNotHasKey( 'price', $attrs );
	}

	public function test_omits_price_when_currency_override_set_without_wpml() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '19.99' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'AE', null, [], [], [], 'AE-EN-AED', '', 'AED', null ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'price', $attrs );
	}

	public function test_maps_sale_price_and_effective_date() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 80,
				'regular_price'     => 100,
				'sale_price'        => 80,
				'date_on_sale_from' => '2021-01-01',
				'date_on_sale_to'   => '2099-01-01',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame(
			[
				'amountMicros' => '100000000',
				'currencyCode' => get_woocommerce_currency(),
			],
			$attrs['price']
		);
		$this->assertSame(
			[
				'amountMicros' => '80000000',
				'currencyCode' => get_woocommerce_currency(),
			],
			$attrs['salePrice']
		);
		$this->assertSame(
			[
				'startTime' => (string) $product->get_date_on_sale_from(),
				'endTime'   => (string) $product->get_date_on_sale_to(),
			],
			$attrs['salePriceEffectiveDate']
		);
	}

	public function test_sale_price_is_not_set_if_empty() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => '',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'salePrice', $attrs );
		$this->assertArrayNotHasKey( 'salePriceEffectiveDate', $attrs );
	}

	public function test_sale_price_is_set_if_zero() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => 0,
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( '0', $attrs['salePrice']['amountMicros'] );
	}

	public function test_sale_price_is_not_set_if_sale_end_date_passed() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'           => 100,
				'regular_price'   => 100,
				'sale_price'      => 50,
				'date_on_sale_to' => '0000-01-01',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'salePrice', $attrs );
		$this->assertArrayNotHasKey( 'salePriceEffectiveDate', $attrs );
	}

	public function test_sale_price_effective_date_start_is_set_to_now_if_empty() {
		$now = (string) ( new WC_DateTime( 'now' ) );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'           => 50,
				'regular_price'   => 100,
				'sale_price'      => 50,
				'date_on_sale_to' => '2099-01-01',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayHasKey( 'salePriceEffectiveDate', $attrs );
		$this->assertNotEmpty( $attrs['salePriceEffectiveDate']['startTime'] );
		$this->assertGreaterThanOrEqual( new WC_DateTime( $now ), new WC_DateTime( $attrs['salePriceEffectiveDate']['startTime'] ) );
		$this->assertSame( (string) new WC_DateTime( '2099-01-01' ), $attrs['salePriceEffectiveDate']['endTime'] );
	}

	public function test_sale_price_effective_date_start_is_not_set_if_in_past_and_no_end_date() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 50,
				'regular_price'     => 100,
				'sale_price'        => 50,
				'date_on_sale_from' => '0000-01-01',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayNotHasKey( 'salePriceEffectiveDate', $attrs );
	}

	public function test_sale_price_effective_date_end_is_set_to_one_day_if_start_in_future_but_no_end() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 100,
				'regular_price'     => 100,
				'sale_price'        => 50,
				'date_on_sale_from' => '2099-01-01',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame(
			[
				'startTime' => (string) new WC_DateTime( '2099-01-01' ),
				'endTime'   => (string) new WC_DateTime( '2099-01-02' ),
			],
			$attrs['salePriceEffectiveDate']
		);
	}

	public function test_sale_price_effective_date_is_not_set_if_not_set() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertArrayHasKey( 'salePrice', $attrs );
		$this->assertArrayNotHasKey( 'salePriceEffectiveDate', $attrs );
	}

	public function test_applies_sale_price_attribute_value_filter() {
		add_filter(
			'woocommerce_gla_product_attribute_value_sale_price',
			function () {
				return 20;
			}
		);

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();
		remove_all_filters( 'woocommerce_gla_product_attribute_value_sale_price' );

		$this->assertSame( '20000000', $attrs['salePrice']['amountMicros'] );
	}

	public function test_maps_availability_in_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'instock' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'IN_STOCK', $attrs['availability'] );
	}

	public function test_maps_availability_out_of_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'OUT_OF_STOCK', $attrs['availability'] );
	}

	public function test_maps_availability_backorder() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->set_manage_stock( true );
		$product->set_stock_status( 'instock' );
		$product->set_backorders( 'yes' );
		$product->set_stock_quantity( 0 );
		$product->save();

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( 'BACKORDER', $attrs['availability'] );
	}

	public function test_uppercases_availability_set_via_override_filter() {
		// Value injected by the override filter (e.g. the pre-orders integration's
		// lowercase `preorder`) must still be sent as the Merchant API's uppercase enum.
		$product = WC_Helper_Product::create_simple_product();

		$cb = static function ( array $overrides ): array {
			$overrides['availability'] = 'preorder';
			return $overrides;
		};
		add_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		remove_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$this->assertSame( 'PREORDER', $attrs['availability'] );
	}

	public function test_uppercases_condition_set_via_override_filter() {
		$product = WC_Helper_Product::create_simple_product();

		$cb = static function ( array $overrides ): array {
			$overrides['condition'] = 'refurbished';
			return $overrides;
		};
		add_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		remove_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$this->assertSame( 'REFURBISHED', $attrs['condition'] );
	}

	public function test_uppercases_gender_set_via_override_filter() {
		$product = WC_Helper_Product::create_simple_product();

		$cb = static function ( array $overrides ): array {
			$overrides['gender'] = 'female';
			return $overrides;
		};
		add_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		remove_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$this->assertSame( 'FEMALE', $attrs['gender'] );
	}

	public function test_uppercases_age_group_set_via_override_filter() {
		$product = WC_Helper_Product::create_simple_product();

		$cb = static function ( array $overrides ): array {
			$overrides['ageGroup'] = 'toddler';
			return $overrides;
		};
		add_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		remove_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$this->assertSame( 'TODDLER', $attrs['ageGroup'] );
	}

	public function test_uppercases_size_types_set_via_override_filter() {
		$product = WC_Helper_Product::create_simple_product();

		$cb = static function ( array $overrides ): array {
			$overrides['sizeTypes'] = [ 'petite' ];
			return $overrides;
		};
		add_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$attrs = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		remove_filter( 'woocommerce_gla_product_attribute_values', $cb );

		$this->assertSame( [ 'PETITE' ], $attrs['sizeTypes'] );
	}

	public function test_uppercases_enum_attributes_set_via_mapping_rule() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$rules = [
			[
				'attribute'               => 'condition',
				'source'                  => 'refurbished',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => 'gender',
				'source'                  => 'female',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => 'ageGroup',
				'source'                  => 'toddler',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'attribute'               => 'sizeType',
				'source'                  => 'petite',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
		];

		$attrs = ( new WCProductInputAdapter( $product, 'US', null, [], [], $rules ) )->get_product_input()->get_attributes();

		$this->assertSame( 'REFURBISHED', $attrs['condition'] );
		$this->assertSame( 'FEMALE', $attrs['gender'] );
		$this->assertSame( 'TODDLER', $attrs['ageGroup'] );
		$this->assertSame( [ 'PETITE' ], $attrs['sizeTypes'] );
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
		$this->assertSame( 'NEW', $attrs['condition'] );
		$this->assertSame( 'ADULT', $attrs['ageGroup'] );
		$this->assertSame( 'UNISEX', $attrs['gender'] );
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
		$this->assertSame( [ 'REGULAR' ], $attrs['sizeTypes'] );
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

	/**
	 * A product input has no custom attributes unless something adds them.
	 */
	public function test_no_custom_attributes_by_default() {
		$product = WC_Helper_Product::create_simple_product();

		$input = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input();

		$this->assertSame( [], $input->get_custom_attributes() );
	}

	/**
	 * The adapter's get/add/set custom-attribute accessors behave as expected.
	 */
	public function test_adapter_custom_attribute_accessors() {
		$product = WC_Helper_Product::create_simple_product();
		$adapter = new WCProductInputAdapter( $product, 'US' );

		$this->assertSame( [], $adapter->get_custom_attributes() );

		$adapter->add_custom_attribute(
			[
				'name'  => 'x',
				'value' => '1',
			]
		);
		$this->assertSame(
			[
				[
					'name'  => 'x',
					'value' => '1',
				],
			],
			$adapter->get_custom_attributes()
		);

		// set_custom_attributes replaces (and reindexes) the existing list.
		$adapter->set_custom_attributes(
			[
				2 => [
					'name'  => 'y',
					'value' => '2',
				],
			]
		);
		$this->assertSame(
			[
				[
					'name'  => 'y',
					'value' => '2',
				],
			],
			$adapter->get_custom_attributes()
		);
	}

	/**
	 * The attribute-values filter can attach custom attributes to the product input
	 * without leaking them into the typed product attributes.
	 */
	public function test_filter_can_add_custom_attributes() {
		$product = WC_Helper_Product::create_simple_product();

		add_filter(
			'woocommerce_gla_product_attribute_values',
			function ( $overrides, $wc_product, $adapter ) {
				$adapter->add_custom_attribute(
					[
						'name'        => 'native_commerce',
						'groupValues' => [
							[
								'name'  => 'checkout_eligibility',
								'value' => 'true',
							],
						],
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
			[
				[
					'name'  => 'checkout_eligibility',
					'value' => 'true',
				],
			],
			$custom_attributes[0]['groupValues']
		);
		$this->assertSame( 'merchant_item_id', $custom_attributes[1]['name'] );
		$this->assertSame( '123', $custom_attributes[1]['value'] );

		// Custom attributes must not leak into the typed product attributes.
		$attrs = $input->get_attributes();
		$this->assertArrayNotHasKey( 'customAttributes', $attrs );
		$this->assertArrayNotHasKey( 'native_commerce', $attrs );
	}

	/**
	 * Custom attributes set on the adapter are serialized under customAttributes.
	 */
	public function test_set_custom_attributes_replaces_and_serializes() {
		$product = WC_Helper_Product::create_simple_product();

		$adapter = new WCProductInputAdapter( $product, 'US' );
		$adapter->set_custom_attributes(
			[
				[
					'name'  => 'a',
					'value' => '1',
				],
				[
					'name'  => 'b',
					'value' => '2',
				],
			]
		);

		$serialized = $adapter->get_product_input()->to_array();

		$this->assertArrayHasKey( 'customAttributes', $serialized );
		$this->assertSame(
			[
				[
					'name'  => 'a',
					'value' => '1',
				],
				[
					'name'  => 'b',
					'value' => '2',
				],
			],
			$serialized['customAttributes']
		);
	}

	public function test_price_uses_target_country_tax_rate_when_tax_included() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );
		$this->insert_tax_rate_for_country( 'US', '8.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE' ) )->get_product_input()->get_attributes();

		$this->assertSame( '119000000', $attrs['price']['amountMicros'] );

		// Tax-excluded countries emit the untaxed price even when they have their own rate row.
		$attrs_us = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( '100000000', $attrs_us['price']['amountMicros'] );
	}

	public function test_tax_exempt_product_gets_no_tax_in_tax_included_country() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'tax_status'    => 'none',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE' ) )->get_product_input()->get_attributes();

		$this->assertSame( '100000000', $attrs['price']['amountMicros'] );
	}

	public function test_target_country_without_rate_row_yields_zero_tax() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'US', '8.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'FR' ) )->get_product_input()->get_attributes();

		$this->assertSame( '100000000', $attrs['price']['amountMicros'] );
	}

	public function test_inclusive_entered_prices_use_base_rate_before_target_rate() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		$this->enable_taxes( true );
		$this->insert_tax_rate_for_country( 'US', '8.0000' );
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 108,
				'regular_price' => 108,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		// 108.00 entered inclusive of the 8% base rate is 100.00 net, then 19% for the DE target.
		$attrs = ( new WCProductInputAdapter( $product, 'DE' ) )->get_product_input()->get_attributes();

		$this->assertSame( '119000000', $attrs['price']['amountMicros'] );
	}

	public function test_inclusive_price_keeps_target_vat_when_rate_is_postcode_restricted() {
		update_option( 'woocommerce_default_country', 'NO' );
		update_option( 'woocommerce_store_postcode', '0150' );
		$this->enable_taxes( true );
		$this->insert_tax_rate_for_country( 'NO', '25.0000', [ '0150' ] );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 429,
				'regular_price' => 429,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'NO' ) )->get_product_input()->get_attributes();

		// The entered price already includes Norwegian VAT. Looking up the target rate
		// without the store postcode must not strip it to 429 / 1.25 = 343.20.
		$this->assertSame( '429000000', $attrs['price']['amountMicros'] );
	}

	public function test_inclusive_price_is_not_taxed_twice_when_non_base_adjustments_are_disabled() {
		update_option( 'woocommerce_default_country', 'DK' );
		$this->enable_taxes( true );
		$this->insert_tax_rate_for_country( 'DK', '25.0000' );

		// Tax and multi-currency extensions can filter the base-rate lookup while
		// explicitly asking WooCommerce to preserve inclusive entered prices.
		add_filter( 'woocommerce_base_tax_rates', '__return_empty_array' );
		add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 800,
				'regular_price' => 800,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DK' ) )->get_product_input()->get_attributes();

		// The entered price already includes Danish VAT and must not become 800 * 1.25 = 1000.
		$this->assertSame( '800000000', $attrs['price']['amountMicros'] );
	}

	public function test_exchange_rate_converts_price_when_wpml_unavailable() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', null, 0.92 ) )->get_product_input()->get_attributes();

		$this->assertSame( '92000000', $attrs['price']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['price']['currencyCode'] );
	}

	public function test_exchange_rate_conversion_applies_before_target_country_tax() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		// 100.00 converts to 92.00 EUR first, then the DE 19% rate applies: 109.48.
		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', null, 0.92 ) )->get_product_input()->get_attributes();

		$this->assertSame( '109480000', $attrs['price']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['price']['currencyCode'] );
	}

	public function test_wpml_conversion_preferred_over_exchange_rate() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
			]
		);

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_product_price_in_currency' )->willReturn( 90.0 );

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', $wpml, 0.92 ) )->get_product_input()->get_attributes();

		$this->assertSame( '90000000', $attrs['price']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['price']['currencyCode'] );
	}

	public function test_virtual_product_zero_shipping_carries_entry_currency() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'virtual'       => true,
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', null, 1.0 ) )->get_product_input()->get_attributes();

		$this->assertSame( 'EUR', $attrs['price']['currencyCode'] );
		$this->assertSame( '0', $attrs['shipping'][0]['price']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['shipping'][0]['price']['currencyCode'] );
	}

	public function test_sale_price_uses_target_country_tax_rate_when_tax_included() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );
		$this->insert_tax_rate_for_country( 'US', '8.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE' ) )->get_product_input()->get_attributes();

		$this->assertSame( '59500000', $attrs['salePrice']['amountMicros'] );

		// Tax-excluded countries emit the untaxed sale price even when they have their own rate row.
		$attrs_us = ( new WCProductInputAdapter( $product, 'US' ) )->get_product_input()->get_attributes();

		$this->assertSame( '50000000', $attrs_us['salePrice']['amountMicros'] );
	}

	public function test_tax_exempt_sale_price_gets_no_tax_in_tax_included_country() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
				'tax_status'    => 'none',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE' ) )->get_product_input()->get_attributes();

		$this->assertSame( '50000000', $attrs['salePrice']['amountMicros'] );
	}

	public function test_inclusive_entered_sale_prices_use_base_rate_before_target_rate() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		$this->enable_taxes( true );
		$this->insert_tax_rate_for_country( 'US', '8.0000' );
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 54,
				'regular_price' => 108,
				'sale_price'    => 54,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		// 54.00 entered inclusive of the 8% base rate is 50.00 net, then 19% for the DE target.
		$attrs = ( new WCProductInputAdapter( $product, 'DE' ) )->get_product_input()->get_attributes();

		$this->assertSame( '59500000', $attrs['salePrice']['amountMicros'] );
	}

	public function test_exchange_rate_converts_sale_price_when_wpml_unavailable() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', null, 0.92 ) )->get_product_input()->get_attributes();

		$this->assertSame( '46000000', $attrs['salePrice']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['salePrice']['currencyCode'] );
	}

	public function test_sale_price_exchange_rate_conversion_applies_before_target_country_tax() {
		$this->enable_taxes();
		$this->insert_tax_rate_for_country( 'DE', '19.0000' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		// 50.00 converts to 46.00 EUR first, then the DE 19% rate applies: 54.74.
		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', null, 0.92 ) )->get_product_input()->get_attributes();

		$this->assertSame( '54740000', $attrs['salePrice']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['salePrice']['currencyCode'] );
	}

	public function test_wpml_sale_price_conversion_preferred_over_exchange_rate() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_product_price_in_currency' )->willReturn( 90.0 );
		$wpml->method( 'get_product_sale_price_in_currency' )->willReturn( 45.0 );

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', $wpml, 0.92 ) )->get_product_input()->get_attributes();

		$this->assertSame( '45000000', $attrs['salePrice']['amountMicros'] );
		$this->assertSame( 'EUR', $attrs['salePrice']['currencyCode'] );
	}

	public function test_omits_sale_price_when_currency_override_cannot_be_converted() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_product_price_in_currency' )->willReturn( 90.0 );
		$wpml->method( 'get_product_sale_price_in_currency' )->willReturn( null );

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', $wpml ) )->get_product_input()->get_attributes();

		// The converted regular price is kept; the unconvertible sale price stays unset
		// so a store-currency amount never appears under a non-store-currency feed label.
		$this->assertSame( '90000000', $attrs['price']['amountMicros'] );
		$this->assertArrayNotHasKey( 'salePrice', $attrs );
		$this->assertArrayNotHasKey( 'salePriceEffectiveDate', $attrs );
	}

	public function test_omits_ended_sale_price_on_currency_override_path() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 100,
				'regular_price'     => 100,
				'sale_price'        => 50,
				'date_on_sale_from' => '2020-01-01',
				'date_on_sale_to'   => '2020-02-01',
			]
		);

		$attrs = ( new WCProductInputAdapter( $product, 'DE', null, [], [], [], '', '', 'EUR', null, 0.92 ) )->get_product_input()->get_attributes();

		// The ended sale is never converted and sent; the regular price still is.
		$this->assertSame( '92000000', $attrs['price']['amountMicros'] );
		$this->assertArrayNotHasKey( 'salePrice', $attrs );
		$this->assertArrayNotHasKey( 'salePriceEffectiveDate', $attrs );
	}

	/**
	 * Enables tax calculation for the test.
	 *
	 * @param bool $prices_include_tax Whether entered prices include tax.
	 */
	protected function enable_taxes( bool $prices_include_tax = false ): void {
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_prices_include_tax', $prices_include_tax ? '__return_true' : '__return_false' );
	}

	/**
	 * Inserts a standard-class tax rate row for a country.
	 *
	 * @param string   $country   ISO 3166-1 alpha-2 country code.
	 * @param string   $rate      Percentage rate, e.g. '19.0000'.
	 * @param string[] $postcodes Optional postcodes restricting the rate.
	 */
	protected function insert_tax_rate_for_country( string $country, string $rate, array $postcodes = [] ): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			[
				'tax_rate_country'  => $country,
				'tax_rate_state'    => '',
				'tax_rate'          => $rate,
				'tax_rate_name'     => 'TAX',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			]
		);

		if ( ! empty( $postcodes ) ) {
			WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $postcodes );
		}
	}
}
