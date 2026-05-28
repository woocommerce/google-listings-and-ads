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

}
