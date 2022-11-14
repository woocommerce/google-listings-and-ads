<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\YoastWooCommerceSeo;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WC_Helper_Product;

/**
 * Class YoastWooCommerceSeoTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration
 *
 * @property YoastWooCommerceSeo $integration
 */
class YoastWooCommerceSeoTest extends UnitTest {

	protected const TEST_MPN  = '1234567';
	protected const TEST_GTIN = '34567890';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->integration = new YoastWooCommerceSeo();
		$this->integration->init();
	}

	public function test_mpn_options() {
		$options = apply_filters( 'woocommerce_gla_product_attribute_value_options_mpn', [] );
		$this->assertEquals(
			[ 'yoast_seo' => 'From Yoast WooCommerce SEO' ],
			$options
		);
	}

	public function test_gtin_options() {
		$options = apply_filters( 'woocommerce_gla_product_attribute_value_options_gtin', [] );
		$this->assertEquals(
			[ 'yoast_seo' => 'From Yoast WooCommerce SEO' ],
			$options
		);
	}

	public function test_get_mpn() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->update_meta_data(
			'wpseo_global_identifier_values',
			[ 'mpn' => self::TEST_MPN ]
		);
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => $product,
				'gla_attributes' => [ 'mpn' => 'yoast_seo' ],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( self::TEST_MPN, $adapted_product->getMpn() );
	}

	public function test_get_gtin() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->update_meta_data(
			'wpseo_global_identifier_values',
			[ 'gtin8' => self::TEST_GTIN ]
		);
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => $product,
				'gla_attributes' => [ 'gtin' => 'yoast_seo' ],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( self::TEST_GTIN, $adapted_product->getGtin() );
	}

	public function test_get_gtin_no_meta() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => $product,
				'gla_attributes' => [ 'gtin' => 'yoast_seo' ],
				'targetCountry'  => 'US',
			]
		);

		$this->assertNull( $adapted_product->getGtin() );
	}

	public function test_get_gtin_variation_product() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );

		// Set identifier in variation meta.
		$variation->update_meta_data(
			'wpseo_variation_global_identifiers_values',
			[ 'gtin8' => self::TEST_GTIN ]
		);
		$variation->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'gla_attributes'    => [ 'gtin' => 'yoast_seo' ],
				'targetCountry'     => 'US',
			]
		);

		$this->assertEquals( self::TEST_GTIN, $adapted_product->getGtin() );
	}

	public function test_get_gtin_variation_product_parent_fallback() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );

		// Set identifier meta in parent product.
		$variable->update_meta_data(
			'wpseo_global_identifier_values',
			[ 'gtin8' => self::TEST_GTIN ]
		);
		$variable->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'gla_attributes'    => [ 'gtin' => 'yoast_seo' ],
				'targetCountry'     => 'US',
			]
		);

		$this->assertEquals( self::TEST_GTIN, $adapted_product->getGtin() );
	}

	public function test_get_gtin_multiple_products() {
		$product_one = WC_Helper_Product::create_simple_product( false );
		$product_one->update_meta_data(
			'wpseo_global_identifier_values',
			[ 'gtin8' => self::TEST_GTIN ]
		);
		$product_one->save();
		$adapted_one = new WCProductAdapter(
			[
				'wc_product'     => $product_one,
				'gla_attributes' => [ 'gtin' => 'yoast_seo' ],
				'targetCountry'  => 'US',
			]
		);

		$product_two = WC_Helper_Product::create_simple_product( false );
		$product_two->update_meta_data(
			'wpseo_global_identifier_values',
			[ 'gtin8' => '78901234' ]
		);
		$product_two->save();
		$adapted_two = new WCProductAdapter(
			[
				'wc_product'     => $product_two,
				'gla_attributes' => [ 'gtin' => 'yoast_seo' ],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( self::TEST_GTIN, $adapted_one->getGtin() );
		$this->assertEquals( '78901234', $adapted_two->getGtin() );
	}

}
