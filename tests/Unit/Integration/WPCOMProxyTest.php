<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use WC_Meta_Data;
use WP_REST_Response;


/**
 * Class WPCOMProxyTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class WPCOMProxyTest extends RESTControllerUnitTest {

	public function setUp(): void {
		parent::setUp();
		do_action( 'rest_api_init' );
	}

	/**
	 * Return the metadata in array format.
	 *
	 * @param array $metadata
	 *
	 * @return array
	 */
	protected function format_metadata( array $metadata ): array {
		$new_metadata = [];

		/** @var WC_Meta_Data $meta */
		foreach ( $metadata as $meta ) {
			$new_metadata[ $meta->key ] = $meta->value;
		}

		return $new_metadata;
	}

	/**
	 * Return the metadata to be used in the tests.
	 *
	 * @param string|null $visibility The _wc_gla_visibility metadata.
	 *
	 * @return array
	 */
	protected function get_test_metadata( $visibility = 'sync-and-show' ): array {
		$args = [
			'_private_meta' => 'private',
			'public_meta'   => 'public',
		];

		if ( $visibility ) {
			$args['_wc_gla_visibility'] = $visibility;
		}
		return $args;
	}

	/**
	 * Add metadata to a item.
	 *
	 * @param int   $id The item id.
	 * @param array $meta The metadata to be added.
	 */
	protected function add_metadata( int $id, array $meta ) {
		// Update meta.
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
	}

	/**
	 *  Maps the response with the item id.
	 *
	 * @param WP_REST_Response $response The response.
	 *
	 * @return array
	 */
	protected function maps_the_response_with_the_item_id( WP_REST_Response $response ): array {
		return array_reduce(
			$response->get_data(),
			function ( $c, $i ) {
				$c[ $i['id'] ] = $i;
				return $c;
			},
			[]
		);
	}

	public function test_get_products() {
		$product_1 = ProductHelper::create_simple_product();
		$product_2 = ProductHelper::create_simple_product();

		$this->add_metadata( $product_1->get_id(), $this->get_test_metadata() );
		$this->add_metadata( $product_2->get_id(), $this->get_test_metadata( 'dont-sync-and-show' ) );

		$response = $this->do_request( '/wc/v3/products', 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'        => 'public',
			'_wc_gla_visibility' => 'sync-and-show',
		];

		$this->assertEquals( $product_1->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_products_without_gla_visibility_metadata() {
		$product_1 = ProductHelper::create_simple_product();
		$product_2 = ProductHelper::create_simple_product();

		$this->add_metadata( $product_1->get_id(), $this->get_test_metadata( null ) );
		$this->add_metadata( $product_2->get_id(), $this->get_test_metadata() );

		$response = $this->do_request( '/wc/v3/products', 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'        => 'public',
			'_wc_gla_visibility' => 'sync-and-show',
		];

		$this->assertEquals( $product_2->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_product_without_gla_visibility_metadata() {
		// If _wc_gla_visibility is not set it should not be returned.
		$product = ProductHelper::create_simple_product();
		$this->add_metadata( $product->get_id(), $this->get_test_metadata( null ) );

		delete_post_meta( $product->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'gla_rest_item_no_syncable', $response->get_data()['code'] );
		$this->assertEquals( 'Item not syncable', $response->get_data()['message'] );
	}

	public function test_get_product_with_gla_visibility_metadata() {
		$product = ProductHelper::create_simple_product();
		$this->add_metadata( $product->get_id(), $this->get_test_metadata() );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $product->get_id(), $response->get_data()['id'] );
	}


	public function test_get_product_without_gla_syncable_param() {
		$product = ProductHelper::create_simple_product();
		$this->add_metadata( $product->get_id(), $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $product->get_id(), $response->get_data()['id'] );
	}

	public function test_get_products_without_gla_syncable_param() {
		$product_1 = ProductHelper::create_simple_product();
		$product_2 = ProductHelper::create_simple_product();
		$this->add_metadata( $product_1->get_id(), $this->get_test_metadata() );
		$this->add_metadata( $product_2->get_id(), $this->get_test_metadata( 'dont-sync-and-show' ) );

		$response = $this->do_request( '/wc/v3/products', 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertArrayHasKey( $product_1->get_id(), $response_mapped );
		$this->assertArrayHasKey( $product_2->get_id(), $response_mapped );

		$this->assertEquals( $this->get_test_metadata(), $this->format_metadata( $response_mapped[ $product_1->get_id() ]['meta_data'] ) );
		$this->assertEquals( $this->get_test_metadata( 'dont-sync-and-show' ), $this->format_metadata( $response_mapped[ $product_2->get_id() ]['meta_data'] ) );
	}

	public function test_get_variations() {
		$product    = ProductHelper::create_variation_product();
		$variations = $product->get_available_variations();

		foreach ( $variations as $variation ) {
			// Variations don't have the _wc_gla_visibility metadata, the parent product has it. For now we can only filter the private metadata.
			$this->add_metadata( $variation['variation_id'], $this->get_test_metadata( null ) );
		}

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id() . '/variations', 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( count( $variations ), $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$expected_metadata = [
			'public_meta' => 'public',
		];

		foreach ( $variations as $variation ) {
			$this->assertArrayHasKey( $variation['variation_id'], $response_mapped );
			$this->assertEquals( $expected_metadata, $this->format_metadata( $response_mapped[ $variation['variation_id'] ]['meta_data'] ) );
		}
	}

	public function test_get_variations_without_gla_syncable_param() {
		$product    = ProductHelper::create_variation_product();
		$variations = $product->get_available_variations();

		foreach ( $variations as $variation ) {
			// Variations don't have the _wc_gla_visibility metadata, the parent product has it. For now we can only filter the private metadata.
			$this->add_metadata( $variation['variation_id'], $this->get_test_metadata( null ) );
		}

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id() . '/variations' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( count( $variations ), $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$expected_metadata = [
			'public_meta'   => 'public',
			'_private_meta' => 'private',
		];

		foreach ( $variations as $variation ) {
			$this->assertArrayHasKey( $variation['variation_id'], $response_mapped );
			$this->assertEquals( $expected_metadata, $this->format_metadata( $response_mapped[ $variation['variation_id'] ]['meta_data'] ) );
		}
	}

	public function test_get_coupons() {
		$coupon_1 = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata() );
		$coupon_2 = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata( 'dont-sync-and-show' ) );

		delete_post_meta( $coupon_1->get_id(), 'customer_email' );
		delete_post_meta( $coupon_2->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'        => 'public',
			'_wc_gla_visibility' => 'sync-and-show',
		];

		$this->assertEquals( $coupon_1->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_coupons_with_customer_email_and_syncable() {
		// Even that this coupon has the _wc_gla_visibility set to sync-and-show, it should not be returned because it has a customer_email set.
		CouponHelper::create_coupon( 'dummycoupon-1', 'publish', array_merge( $this->get_test_metadata(), [ 'customer_email' => 'john@smith.com' ] ) );
		$coupon = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata() );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'        => 'public',
			'_wc_gla_visibility' => 'sync-and-show',
		];

		$this->assertEquals( $coupon->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_coupons_without_gla_visibility_metadata() {
		// If _wc_gla_visibility is not set it should not be returned.
		CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata( null ) );
		$coupon = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata() );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'        => 'public',
			'_wc_gla_visibility' => 'sync-and-show',
		];

		$this->assertEquals( $coupon->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_coupon_without_gla_visibility_metadata() {
		// If _wc_gla_visibility is not set it should not be returned.
		$coupon = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata( null ) );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons/' . $coupon->get_id(), 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'gla_rest_item_no_syncable', $response->get_data()['code'] );
		$this->assertEquals( 'Item not syncable', $response->get_data()['message'] );
	}

	public function test_get_coupon_with_gla_visibility_metadata() {
		$coupon = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata() );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons/' . $coupon->get_id(), 'GET', [ 'gla_syncable' => 1 ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $coupon->get_id(), $response->get_data()['id'] );
	}

	public function test_get_coupon_without_gla_syncable_param() {
		$coupon = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/coupons/' . $coupon->get_id(), 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $coupon->get_id(), $response->get_data()['id'] );
	}

	public function test_get_coupons_without_gla_syncable_param() {
		$coupon_1 = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata() );
		$coupon_2 = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata( 'dont-sync-and-show' ) );

		$response = $this->do_request( '/wc/v3/coupons', 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertArrayHasKey( $coupon_1->get_id(), $response_mapped );
		$this->assertArrayHasKey( $coupon_2->get_id(), $response_mapped );

		$this->assertEquals( $this->get_test_metadata(), $this->format_metadata( $response_mapped[ $coupon_1->get_id() ]['meta_data'] ) );
		$this->assertEquals( $this->get_test_metadata( 'dont-sync-and-show' ), $this->format_metadata( $response_mapped[ $coupon_2->get_id() ]['meta_data'] ) );
	}
}
