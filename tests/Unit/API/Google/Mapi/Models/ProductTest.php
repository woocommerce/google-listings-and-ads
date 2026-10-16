<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models
 */
class ProductTest extends UnitTest {

	public function test_from_array_populates_all_fields() {
		$product = Product::from_array(
			[
				'name'              => 'accounts/123/products/online~en~US~sku42',
				'offerId'           => 'sku42',
				'feedLabel'         => 'US',
				'productAttributes' => [
					'title' => 'Test product',
				],
				'productStatus'     => [
					'itemLevelIssues' => [
						[ 'code' => 'missing_image' ],
					],
				],
			]
		);

		$this->assertSame( 'online~en~US~sku42', $product->get_id() );
		$this->assertSame( 'sku42', $product->get_offer_id() );
		$this->assertSame( 'Test product', $product->get_title() );
		$this->assertSame( 'US', $product->get_target_country() );

		$status = $product->get_product_status();
		$this->assertInstanceOf( ProductStatus::class, $status );
		$this->assertCount( 1, $status->get_item_level_issues() );
	}

	public function test_from_array_with_missing_optional_fields() {
		$product = Product::from_array(
			[
				'name' => 'accounts/123/products/abc',
			]
		);

		$this->assertSame( 'abc', $product->get_id() );
		$this->assertNull( $product->get_offer_id() );
		$this->assertNull( $product->get_title() );
		$this->assertNull( $product->get_target_country() );
		$this->assertNull( $product->get_product_status() );
	}

	public function test_get_id_returns_empty_when_name_missing() {
		$product = Product::from_array( [] );
		$this->assertSame( '', $product->get_id() );
	}

	public function test_get_id_extracts_trailing_segment() {
		$product = Product::from_array(
			[ 'name' => 'accounts/1/products/online~en~US~sku/extra/segments' ]
		);

		$this->assertSame( 'segments', $product->get_id() );
	}
}
