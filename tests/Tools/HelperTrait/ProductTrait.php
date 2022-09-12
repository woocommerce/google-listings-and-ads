<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Google\Service\ShoppingContent\Product as GoogleProduct;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Trait Product
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait ProductTrait {
	use SettingsTrait;

	/**
	 * Generates and returns a mock of a WC_Product_Variable object containing a number of mock variations/children.
	 *
	 * @param int $number_of_variations
	 *
	 * @return MockObject|WC_Product_Variable
	 */
	public function generate_variable_product_mock( int $number_of_variations = 3 ) {
		$product = $this->createMock( WC_Product_Variable::class );
		$id      = rand();

		$children = [];
		for ( $i = 0; $i < $number_of_variations; $i ++ ) {
			$child = $this->createMock( WC_Product_Variation::class );

			$child->expects( $this->any() )
				  ->method( 'get_id' )
				  ->willReturn( rand() );
			$child->expects( $this->any() )
				  ->method( 'get_parent_id' )
				  ->willReturn( $id );
			$child->expects( $this->any() )
				  ->method( 'get_type' )
				  ->willReturn( 'variation' );

			$children[] = $child;
		}

		$product->expects( $this->any() )
				->method( 'get_id' )
				->willReturn( $id );
		$product->expects( $this->any() )
				->method( 'get_children' )
				->willReturn( $children );
		$product->expects( $this->any() )
				->method( 'get_available_variations' )
				->willReturn( $children );
		$product->expects( $this->any() )
				->method( 'get_type' )
				->willReturn( 'variable' );

		return $product;
	}

	/**
	 * Generates and returns a mock of a Google Product object
	 *
	 * @param string|null $id
	 * @param string|null $target_country
	 *
	 * @return MockObject|GoogleProduct
	 */
	public function generate_google_product_mock( $id = null, $target_country = null ) {
		$product = $this->createMock( GoogleProduct::class );

		$target_country = $target_country ?: $this->get_sample_target_country();
		$id             = $id ?: "online:en:{$target_country}:gla_" . rand();

		$product->expects( $this->any() )
				->method( 'getId' )
				->willReturn( $id );
		$product->expects( $this->any() )
				->method( 'getTargetCountry' )
				->willReturn( $target_country );

		return $product;
	}

	/**
	 * Generates and returns a mock of a simple WC_Product object
	 *
	 * @return MockObject|WC_Product
	 */
	public function generate_simple_product_mock() {
		$product = $this->createMock( WC_Product::class );

		$product->expects( $this->any() )
				->method( 'get_id' )
				->willReturn( rand() );

		$product->expects( $this->any() )
				->method( 'get_type' )
				->willReturn( 'simple' );

		return $product;
	}

	/**
	 * Generates and returns a mock of a WC_Product_Variation object
	 *
	 * @return MockObject|WC_Product_Variation
	 */
	public function generate_variation_product_mock() {
		$product = $this->createMock( WC_Product_Variation::class );

		$product->expects( $this->any() )
				->method( 'get_id' )
				->willReturn( rand() );
		$product->expects( $this->any() )
				->method( 'get_parent_id' )
				->willReturn( rand() );
		$product->expects( $this->any() )
				->method( 'get_type' )
				->willReturn( 'variation' );

		return $product;
	}

	/**
	 * @param WC_Product|int $product Product object or ID
	 *
	 * @return string
	 */
	public function generate_google_id( $product ): string {
		$product_id = $product;
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id() ?: rand();
		}

		return 'online:en:' . $this->get_sample_target_country() . ':gla_' . $product_id;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return MockObject|WCProductAdapter
	 */
	public function generate_adapted_product( WC_Product $product ) {
		$adapted = $this->createMock( WCProductAdapter::class );
		$adapted->expects( $this->any() )
				->method( 'get_wc_product' )
				->willReturn( $product );

		return $adapted;
	}

	protected static function get_product_id( WC_Product $product ) {
		return $product->get_id();
	}

	/**
	 * @param int ...$product_count One or many integers each representing the number of products in each set
	 *
	 * @return array A multidimensional array of products based on the number of arguments provided and $set_count
	 *
	 * @example
	 * ```
	 * // Create 2 sub-arrays where the first set includes 2 products and the second set includes 4 products:
	 * $this->create_multiple_simple_product_sets( 2, 4 ); // [ [ *, * ], [ *, *, *, * ] ]
	 * ```
	 */
	public function create_multiple_simple_product_sets( ...$product_count ): array {
		$results = [];
		foreach ( $product_count as $count ) {
			$results[] = $this->create_simple_product_set( $count );
		}

		return $results;
	}

	/**
	 * @param integer $product_count Amount of products to generate.
	 *
	 * @return array Multiple simple products indexed by product ID.
	 */
	public function create_simple_product_set( int $product_count ): array {
		$products = [];
		for ( $i = 1; $i <= $product_count; $i ++ ) {
			$product                        = WC_Helper_Product::create_simple_product();
			$products[ $product->get_id() ] = $product;
		}

		return $products;
	}

	/**
	 * Create a dummy variation product or configure an existing product object with dummy data.
	 *
	 * @param WC_Product_Variable|null $product Product object to configure, or null to create a new one.
	 * @param array                    $props   Product properties.
	 *
	 * @return WC_Product_Variable
	 */
	protected function create_variation_product( $product = null, $props = [] ) {
		$is_new_product = is_null( $product );
		if ( $is_new_product ) {
			$product = new WC_Product_Variable();
		}

		$default_props = [
			'name' => 'Dummy Variable Product',
			'sku'  => 'DUMMY VARIABLE SKU',
		];

		$product->set_props( array_merge( $default_props, $props ) );

		$attributes = [
			WC_Helper_Product::create_product_attribute_object( 'size', [ 'small', 'large', 'huge' ] ),
		];

		$product->set_attributes( $attributes );
		$product->save();

		$variations = [];

		$variations[] = $this->create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE SMALL',
			10,
			[ 'pa_size' => 'small' ]
		);

		$variations[] = $this->create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE LARGE',
			15,
			[ 'pa_size' => 'large' ]
		);

		$variations[] = $this->create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE HUGE',
			15,
			[ 'pa_size' => 'huge' ]
		);

		if ( $is_new_product ) {
			return wc_get_product( $product->get_id() );
		}

		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );

		return $product;
	}

	/**
	 * Creates an instance of WC_Product_Variation with the supplied parameters, optionally persisting it to the
	 * database.
	 *
	 * @param string $parent_id  Parent product id.
	 * @param string $sku        SKU for the variation.
	 * @param int    $price      Price of the variation.
	 * @param array  $attributes Attributes that define the variation, e.g. ['pa_color'=>'red'].
	 * @param bool   $save       If true, the object will be saved to the database after being created and configured.
	 *
	 * @return WC_Product_Variation The created object.
	 */
	protected function create_product_variation_object( $parent_id, $sku, $price, $attributes, $save = true ) {
		$variation = new WC_Product_Variation();
		$variation->set_props(
			[
				'parent_id'     => $parent_id,
				'sku'           => $sku,
				'regular_price' => $price,
			]
		);
		$variation->set_parent_data( [ 'catalog_visibility' => 'visible' ] );
		$variation->set_attributes( $attributes );
		if ( $save ) {
			$variation->save();
		}

		return $variation;
	}

	/**
	 * @return array
	 */
	public function get_sample_attributes(): array {
		return [
			'gtin'       => '3234567890126',
			'mpn'        => 'GO12345OOGLE',
			'brand'      => 'Google',
			'condition'  => 'new',
			'gender'     => 'female',
			'size'       => 'L',
			'sizeSystem' => 'US',
			'sizeType'   => 'regular',
			'color'      => 'white',
			'material'   => 'cotton',
			'pattern'    => 'polka dot',
			'ageGroup'   => 'newborn',
			'multipack'  => 0,
			'isBundle'   => false,
			'adult'      => false,
		];
	}

	/**
	 * Helper function to create an array filled with product mocks for test purposes
	 *
	 * @param int $number Number of elements to fill an array with.
	 * @return WC_Product[]
	 */
	protected function generate_simple_product_mocks_set( int $number ) {
		return array_fill( 0, $number, $this->generate_simple_product_mock() );
	}
}
