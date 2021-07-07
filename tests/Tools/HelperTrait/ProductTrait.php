<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Google\Service\ShoppingContent\Product as GoogleProduct;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

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
	 * @return string[]
	 */
	public function generate_google_ids( $product ): array {
		$product_id = $product;
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id() ?: rand();
		}

		return [ 'online:en:US:gla_' . $product_id ];
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
}
