<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Product;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFactoryTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property MockObject|AttributeManager $attribute_manager
 * @property MockObject|WC               $wc
 * @property ProductFactory              $product_factory
 */
class ProductFactoryTest extends WP_UnitTestCase {

	use ProductTrait;

	/**
	 * @param MockObject|WC_Product $product
	 *
	 * @dataProvider return_blank_test_products
	 */
	public function test_create( $product ) {
		if ( $product instanceof \WC_Product_Variation ) {
			$this->wc->expects( $this->any() )
					 ->method( 'get_product' )
					 ->willReturnMap(
						 [
							 [ $product->get_parent_id(), $this->generate_variable_product_mock() ],
						 ]
					 );
		}

		$attributes = $this->get_sample_attributes();
		$this->attribute_manager->expects( $this->any() )
								->method( 'get_all_values' )
								->willReturn( $attributes );

		// the value of `is_virtual` doesn't matter but it should return a
		// boolean (and not `null` as the mock would return) for this test to work
		$product->expects( $this->any() )
				->method( 'is_virtual' )
				->willReturn( false );

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
		$parent    = $this->generate_variable_product_mock( 1 );
		$variation = $parent->get_children()[0];

		$this->wc->expects( $this->any() )
				 ->method( 'get_product' )
				 ->willReturnMap(
					 [
						 [ $parent->get_id(), $parent ],
					 ]
				 );

		$parent_attributes = [
			'color'   => 'black',
			'pattern' => 'polka dot',
		];
		$child_attributes  = [
			'color' => 'white',
		];

		$this->attribute_manager->expects( $this->any() )
								->method( 'get_all_values' )
								->willReturnMap(
									[
										[ $parent, $parent_attributes ],
										[ $variation, $child_attributes ],
									]
								);


		// the value of `is_virtual` doesn't matter but it should return a
		// boolean (and not `null` as the mock would return) for this test to work
		$variation->expects( $this->any() )
				  ->method( 'is_virtual' )
				  ->willReturn( false );

		$adapted_product = $this->product_factory->create( $variation, 'US' );

		$this->assertInstanceOf( WCProductAdapter::class, $adapted_product );

		// check if the child attribute overrides the parent
		$this->assertEquals( 'white', $adapted_product->getColor() );

		// check that the child inherits the non-existing attributes ( "pattern" ) from its parent
		$this->assertEquals( 'polka dot', $adapted_product->getPattern() );
	}

	public function test_create_variable_product_fails() {
		$variable = $this->generate_variable_product_mock();

		$this->expectException( InvalidClass::class );
		$this->product_factory->create( $variable, 'US' );
	}

	/**
	 * @return WC_Product[]
	 */
	public function return_blank_test_products(): array {
		return [
			[ $this->generate_simple_product_mock() ],
			[ $this->generate_variation_product_mock() ],
		];
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->attribute_manager = $this->createMock( AttributeManager::class );
		$this->wc                = $this->createMock( WC::class );
		$this->product_factory   = new ProductFactory( $this->attribute_manager, $this->wc );
	}
}
