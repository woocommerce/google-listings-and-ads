<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Ucp;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Ucp\UcpProductAttributes;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WC_Helper_Product;

/**
 * Class UcpProductAttributesTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Ucp
 */
class UcpProductAttributesTest extends UnitTest {

	/** @var UcpProductAttributes */
	private $ucp_attributes;

	public function setUp(): void {
		parent::setUp();

		remove_all_filters( 'woocommerce_gla_product_attribute_values' );
		remove_all_filters( 'woocommerce_agentic_commerce_enabled' );
		remove_all_filters( 'woocommerce_agentic_commerce_should_sync_product' );

		$this->ucp_attributes = new UcpProductAttributes();
		$this->ucp_attributes->register();
	}

	public function tearDown(): void {
		remove_all_filters( 'woocommerce_gla_product_attribute_values' );
		remove_all_filters( 'woocommerce_agentic_commerce_enabled' );
		remove_all_filters( 'woocommerce_agentic_commerce_should_sync_product' );

		parent::tearDown();
	}

	/**
	 * Build an adapter for a simple product and return its custom attributes as a name => value map.
	 *
	 * @return array
	 */
	private function adapt_simple_product(): array {
		$product = WC_Helper_Product::create_simple_product();
		$adapter = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$map = [];
		foreach ( (array) $adapter->getCustomAttributes() as $attribute ) {
			$map[ $attribute->getName() ] = $attribute->getValue();
		}

		return [ $product, $map ];
	}

	public function test_no_attributes_when_disabled() {
		// No `woocommerce_agentic_commerce_enabled` listener and no WCAI plugin => disabled.
		[ , $map ] = $this->adapt_simple_product();

		$this->assertArrayNotHasKey( 'native_commerce', $map );
		$this->assertArrayNotHasKey( 'merchant_item_id', $map );
	}

	public function test_attributes_added_when_enabled() {
		add_filter( 'woocommerce_agentic_commerce_enabled', '__return_true' );

		[ $product, $map ] = $this->adapt_simple_product();

		$this->assertArrayHasKey( 'native_commerce', $map );
		$this->assertSame( 'true', $map['native_commerce'] );
		$this->assertArrayHasKey( 'merchant_item_id', $map );
		$this->assertSame( (string) $product->get_id(), $map['merchant_item_id'] );
	}

	public function test_no_attributes_when_product_excluded_by_filter() {
		add_filter( 'woocommerce_agentic_commerce_enabled', '__return_true' );
		add_filter( 'woocommerce_agentic_commerce_should_sync_product', '__return_false' );

		[ , $map ] = $this->adapt_simple_product();

		$this->assertArrayNotHasKey( 'native_commerce', $map );
		$this->assertArrayNotHasKey( 'merchant_item_id', $map );
	}
}
