<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ProductFactoryTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property MockObject|AttributeManager $attribute_manager
 * @property WC                          $wc
 * @property ProductFactory              $product_factory
 */
class ProductFactoryTest extends ContainerAwareUnitTest {

	use ProductTrait;

	public function test_create() {
		$product = WC_Helper_Product::create_simple_product();

		$attributes = $this->get_sample_attributes();
		$this->attribute_manager->expects( $this->any() )
								->method( 'get_all_values' )
								->willReturn( $attributes );

		$adapted_product = $this->product_factory->create( $product, 'US' );

		$this->assertInstanceOf( WCProductAdapter::class, $adapted_product );

		// make sure the target country is set
		$this->assertEquals( 'US', $adapted_product->getTargetCountry() );

		// check if attribute are all set in the created product
		$this->assertEquals( $attributes['gtin'], $adapted_product->getGtin() );
		$this->assertEquals( $attributes['mpn'], $adapted_product->getMpn() );
		$this->assertEquals( $attributes['brand'], $adapted_product->getBrand() );
		$this->assertEquals( $attributes['condition'], $adapted_product->getCondition() );
		$this->assertEquals( $attributes['gender'], $adapted_product->getGender() );
		$this->assertContains( $attributes['size'], $adapted_product->getSizes() );
		$this->assertEquals( $attributes['sizeSystem'], $adapted_product->getSizeSystem() );
		$this->assertEquals( $attributes['sizeType'], $adapted_product->getSizeType() );
		$this->assertEquals( $attributes['color'], $adapted_product->getColor() );
		$this->assertEquals( $attributes['material'], $adapted_product->getMaterial() );
		$this->assertEquals( $attributes['pattern'], $adapted_product->getPattern() );
		$this->assertEquals( $attributes['ageGroup'], $adapted_product->getAgeGroup() );
		$this->assertEquals( $attributes['multipack'], $adapted_product->getMultipack() );
		$this->assertEquals( $attributes['isBundle'], $adapted_product->getIsBundle() );
		$this->assertEquals( $attributes['adult'], $adapted_product->getAdult() );
	}

	public function test_created_variation_inherits_attributes_from_parent() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );

		$parent_attributes = [
			'color'   => 'black',
			'pattern' => 'polka dot',
		];
		$child_attributes  = [
			'color' => 'white',
		];

		$this->attribute_manager->expects( $this->any() )
								->method( 'get_all_values' )
								->willReturnCallback(
									function ( WC_Product $product ) use ( $parent, $variation, $parent_attributes, $child_attributes ) {
										if ( $parent->get_id() === $product->get_id() ) {
											return $parent_attributes;
										}
										if ( $variation->get_id() === $product->get_id() ) {
											return $child_attributes;
										}

										return [];
									}
								);

		$adapted_product = $this->product_factory->create( $variation, 'US' );

		$this->assertInstanceOf( WCProductAdapter::class, $adapted_product );

		// check if the child attribute overrides the parent
		$this->assertEquals( 'white', $adapted_product->getColor() );

		// check that the child inherits the non-existing attributes ( "pattern" ) from its parent
		$this->assertEquals( 'polka dot', $adapted_product->getPattern() );
	}

	public function test_create_variable_product_fails() {
		$variable = WC_Helper_Product::create_variation_product();
		$this->expectException( InvalidClass::class );
		$this->product_factory->create( $variable, 'US' );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->attribute_manager = $this->createMock( AttributeManager::class );
		$this->wc                = $this->container->get( WC::class );
		$this->product_factory   = new ProductFactory( $this->attribute_manager, $this->wc );
	}
}
