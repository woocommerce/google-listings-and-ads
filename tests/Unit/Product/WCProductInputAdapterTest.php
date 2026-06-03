<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
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
}
