<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPCOMProxy;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\AttributeMappingRulesTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container\PluginContainer as Container;
use WC_Meta_Data;
use WP_REST_Response;
use WP_REST_Request;


/**
 * Class WPCOMProxyTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration
 */
class WPCOMProxyTest extends RESTControllerUnitTest {
	use PluginHelper;

	/**
	 * @var Container
	 */
	protected $container;

	/** @var Stub|OptionsInterface $options */
	protected $options;

	/** @var Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	public function setUp(): void {
		parent::setUp();

		$plugin_container = woogle_get_container();
		// Since the tables for shipping rate, time, and attribute mapping rules
		// aren't set up in the test environment, install them to prevent warnings.
		$plugin_container->get( AttributeMappingRulesTable::class )->install();
		$plugin_container->get( ShippingRateTable::class )->install();
		$plugin_container->get( ShippingTimeTable::class )->install();

		$this->options         = $this->createStub( OptionsInterface::class );
		$this->merchant_center = $this->createStub( MerchantCenterService::class );

		$this->container = new Container();
		$this->container->addShared( ShippingRateQuery::class, $plugin_container->get( ShippingRateQuery::class ) );
		$this->container->addShared( ShippingTimeQuery::class, $plugin_container->get( ShippingTimeQuery::class ) );
		$this->container->addShared( AttributeManager::class, $plugin_container->get( AttributeManager::class ) );
		$this->container->addShared( ProductRepository::class, $plugin_container->get( ProductRepository::class ) );
		$this->container->addShared( MerchantCenterService::class, $this->merchant_center );

		$this->controller = new WPCOMProxy();
		$this->controller->set_container( $this->container );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();

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
	protected function get_test_metadata( $visibility = ChannelVisibility::SYNC_AND_SHOW ): array {
		$args = [
			'_private_meta' => 'private',
			'public_meta'   => 'public',
		];

		if ( $visibility ) {
			$args[ WPCOMProxy::KEY_VISIBILITY ] = $visibility;
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
		$product_3 = ProductHelper::create_simple_product();

		// Only products with opt-out channel visibility will be excluded.
		$this->add_metadata( $product_1->get_id(), $this->get_test_metadata() );
		$this->add_metadata( $product_2->get_id(), $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ) );
		$this->add_metadata( $product_3->get_id(), $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/products', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );

		$expected_metadata = [
			'public_meta'              => 'public',
			WPCOMProxy::KEY_VISIBILITY => ChannelVisibility::SYNC_AND_SHOW,
		];

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		foreach ( [ $product_1, $product_3 ] as $source_product ) {
			$this->assertArrayHasKey( $source_product->get_id(), $response_mapped );

			$product = $response_mapped[ $source_product->get_id() ];

			$this->assertEmpty( array_diff_assoc( $this->format_metadata( $product['meta_data'] ), $expected_metadata ) );
			$this->assertArrayHasKey( 'gla_attributes', $product );
			$this->assertEquals( 'object', gettype( $product['gla_attributes'] ) );
		}
	}

	/**
	 * The existing meta query and its relation should be followed.
	 */
	public function test_get_products_combination_with_existing_meta_query() {
		$filter_product_meta_query = function ( array $args ) {
			$args['meta_query'] = [
				'relation' => 'AND',
				[
					'key'     => 'test_level',
					'value'   => 0,
					'compare' => '>',
				],
				[
					'key'     => 'test_level',
					'value'   => 2,
					'compare' => '<=',
				],
			];
			return $args;
		};

		add_filter( 'woocommerce_rest_product_object_query', $filter_product_meta_query, 9 );

		$product_1 = ProductHelper::create_simple_product();
		$product_2 = ProductHelper::create_simple_product();
		$product_3 = ProductHelper::create_simple_product();

		$this->add_metadata(
			$product_1->get_id(),
			array_merge(
				$this->get_test_metadata( null ),
				[ 'test_level' => 1 ]
			)
		);
		$this->add_metadata(
			$product_2->get_id(),
			array_merge(
				$this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ),
				[ 'test_level' => 2 ]
			)
		);
		$this->add_metadata(
			$product_3->get_id(),
			array_merge(
				$this->get_test_metadata(),
				[ 'test_level' => 3 ]
			)
		);

		$response = $this->do_request( '/wc/v3/products', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
		$this->assertEquals( $product_1->get_id(), $response->get_data()[0]['id'] );

		remove_filter( 'woocommerce_rest_product_object_query', $filter_product_meta_query );
	}

	public function test_get_products_with_gla_syncable_false() {
		$product_1 = ProductHelper::create_simple_product();
		$product_2 = ProductHelper::create_simple_product();
		$product_3 = ProductHelper::create_simple_product();

		$this->add_metadata( $product_1->get_id(), $this->get_test_metadata() );
		$this->add_metadata( $product_2->get_id(), $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ) );
		$this->add_metadata( $product_3->get_id(), $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/products', 'GET', [ 'gla_syncable' => '0' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 3, $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		foreach ( [ $product_1, $product_2, $product_3 ] as $source_product ) {
			$this->assertArrayHasKey( $source_product->get_id(), $response_mapped );

			$product = $response_mapped[ $source_product->get_id() ];

			$this->assertEquals( $source_product->get_meta_data(), $product['meta_data'] );
			$this->assertArrayNotHasKey( 'gla_attributes', $product );
		}
	}

	public function test_get_product_with_opt_out_gla_visibility() {
		// Requesting an opt-out product should get 403 error.
		$product = ProductHelper::create_simple_product();
		$this->add_metadata( $product->get_id(), $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ) );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'gla_rest_item_no_syncable', $response->get_data()['code'] );
		$this->assertEquals( 'Item not syncable', $response->get_data()['message'] );
	}

	public function test_get_product() {
		$product = ProductHelper::create_simple_product();
		$this->add_metadata( $product->get_id(), $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $product->get_id(), $response->get_data()['id'] );
	}

	public function test_get_product_with_gla_visibility_metadata() {
		$product = ProductHelper::create_simple_product();
		$this->add_metadata( $product->get_id(), $this->get_test_metadata() );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET', [ 'gla_syncable' => '1' ] );

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
		$this->add_metadata( $product_2->get_id(), $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ) );

		$response = $this->do_request( '/wc/v3/products', 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertArrayHasKey( $product_1->get_id(), $response_mapped );
		$this->assertArrayHasKey( $product_2->get_id(), $response_mapped );

		$this->assertEquals( $this->get_test_metadata(), $this->format_metadata( $response_mapped[ $product_1->get_id() ]['meta_data'] ) );
		$this->assertEquals( $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ), $this->format_metadata( $response_mapped[ $product_2->get_id() ]['meta_data'] ) );
	}

	public function test_get_variations() {
		$product    = ProductHelper::create_variation_product();
		$variations = $product->get_available_variations();

		foreach ( $variations as $variation ) {
			// Variations don't have the _wc_gla_visibility metadata, the parent product has it. For now we can only filter the private metadata.
			$this->add_metadata( $variation['variation_id'], $this->get_test_metadata( null ) );
		}

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id() . '/variations', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( count( $variations ), $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$expected_metadata = [
			'public_meta' => 'public',
		];

		foreach ( $variations as $variation ) {
			$this->assertArrayHasKey( $variation['variation_id'], $response_mapped );
			$this->assertEquals( $expected_metadata, $this->format_metadata( $response_mapped[ $variation['variation_id'] ]['meta_data'] ) );
			$this->assertArrayHasKey( 'gla_attributes', $response->get_data()[0] );
			$this->assertEquals( 'object', gettype( $response->get_data()[0]['gla_attributes'] ) );
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
			$this->assertArrayNotHasKey( 'gla_attributes', $response->get_data()[0] );
		}
	}

	public function test_get_specific_variation_with_gla_syncable() {
		$product   = ProductHelper::create_variation_product();
		$variation = $product->get_available_variations()[0];

		$this->add_metadata( $variation['variation_id'], $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation['variation_id'], 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );

		$expected_metadata = [
			'public_meta' => 'public',
		];

		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()['meta_data'] ) );
		$this->assertArrayHasKey( 'gla_attributes', $response->get_data() );
		$this->assertEquals( 'object', gettype( $response->get_data()['gla_attributes'] ) );
	}

	public function test_get_specific_variation_without_gla_syncable() {
		$product   = ProductHelper::create_variation_product();
		$variation = $product->get_available_variations()[0];

		$this->add_metadata( $variation['variation_id'], $this->get_test_metadata( null ) );

		$response = $this->do_request( '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation['variation_id'], 'GET', [ 'gla_syncable' => '0' ] );

		$this->assertEquals( 200, $response->get_status() );

		$expected_metadata = [
			'public_meta'   => 'public',
			'_private_meta' => 'private',
		];

		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()['meta_data'] ) );
		$this->assertArrayNotHasKey( 'gla_attributes', $response->get_data() );
	}

	public function test_get_coupons() {
		$coupon_1 = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata() );
		$coupon_2 = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ) );

		delete_post_meta( $coupon_1->get_id(), 'customer_email' );
		delete_post_meta( $coupon_2->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'              => 'public',
			WPCOMProxy::KEY_VISIBILITY => ChannelVisibility::SYNC_AND_SHOW,
		];

		$this->assertEquals( $coupon_1->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_coupons_with_customer_email_and_syncable() {
		// Even that this coupon has the _wc_gla_visibility set to sync-and-show, it should not be returned because it has a customer_email set.
		CouponHelper::create_coupon( 'dummycoupon-1', 'publish', array_merge( $this->get_test_metadata(), [ 'customer_email' => 'john@smith.com' ] ) );
		$coupon = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata() );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'              => 'public',
			WPCOMProxy::KEY_VISIBILITY => ChannelVisibility::SYNC_AND_SHOW,
		];

		$this->assertEquals( $coupon->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_coupons_without_gla_visibility_metadata() {
		// If _wc_gla_visibility is not set it should not be returned.
		CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata( null ) );
		$coupon = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata() );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$expected_metadata = [
			'public_meta'              => 'public',
			WPCOMProxy::KEY_VISIBILITY => ChannelVisibility::SYNC_AND_SHOW,
		];

		$this->assertEquals( $coupon->get_id(), $response->get_data()[0]['id'] );
		$this->assertEquals( $expected_metadata, $this->format_metadata( $response->get_data()[0]['meta_data'] ) );
	}

	public function test_get_coupon_without_gla_visibility_metadata() {
		// If _wc_gla_visibility is not set it should not be returned.
		$coupon = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata( null ) );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons/' . $coupon->get_id(), 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'gla_rest_item_no_syncable', $response->get_data()['code'] );
		$this->assertEquals( 'Item not syncable', $response->get_data()['message'] );
	}

	public function test_get_coupon_with_gla_visibility_metadata() {
		$coupon = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $this->get_test_metadata() );

		delete_post_meta( $coupon->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons/' . $coupon->get_id(), 'GET', [ 'gla_syncable' => '1' ] );

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
		$coupon_2 = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $this->get_test_metadata( ChannelVisibility::DONT_SYNC_AND_SHOW ) );

		$response = $this->do_request( '/wc/v3/coupons', 'GET' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertArrayHasKey( $coupon_1->get_id(), $response_mapped );
		$this->assertArrayHasKey( $coupon_2->get_id(), $response_mapped );

		$this->assertEquals( $this->get_test_metadata(), $this->format_metadata( $response_mapped[ $coupon_1->get_id() ]['meta_data'] ) );
		$this->assertEquals( $this->get_test_metadata( 'dont-sync-and-show' ), $this->format_metadata( $response_mapped[ $coupon_2->get_id() ]['meta_data'] ) );
	}

	public function test_get_settings_without_gla_syncable_param() {
		$response = $this->do_request( '/wc/v3/settings/google-for-woocommerce', 'GET' );

		$this->assertEquals( 200, $response->get_status() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertArrayNotHasKey( 'gla_plugin_version', $response_mapped );
		$this->assertArrayNotHasKey( 'gla_google_connected', $response_mapped );
		$this->assertArrayNotHasKey( 'gla_language', $response_mapped );
		$this->assertArrayNotHasKey( 'gla_merchant_center', $response_mapped );
		$this->assertArrayNotHasKey( 'gla_shipping_rates', $response_mapped );
		$this->assertArrayNotHasKey( 'gla_shipping_times', $response_mapped );
		$this->assertArrayNotHasKey( 'gla_target_audience', $response_mapped );
	}

	public function test_get_settings_with_gla_syncable_param() {
		$response = $this->do_request( '/wc/v3/settings/google-for-woocommerce', 'GET', [ 'gla_syncable' => '1' ] );

		$this->assertEquals( 200, $response->get_status() );

		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertArrayHasKey( 'gla_plugin_version', $response_mapped );
		$this->assertArrayHasKey( 'gla_google_connected', $response_mapped );
		$this->assertArrayHasKey( 'gla_language', $response_mapped );
		$this->assertArrayHasKey( 'gla_merchant_center', $response_mapped );
		$this->assertArrayHasKey( 'gla_shipping_rates', $response_mapped );
		$this->assertArrayHasKey( 'gla_shipping_times', $response_mapped );
		$this->assertArrayHasKey( 'gla_target_audience', $response_mapped );

		$this->assertEquals( $this->get_version(), $response_mapped['gla_plugin_version']['value'] );
		$this->assertEquals( false, $response_mapped['gla_google_connected']['value'] );
		$this->assertEquals( null, $response_mapped['gla_merchant_center']['value'] );
	}

	public function test_get_settings_with_connected_google_account() {
		$this->merchant_center
			->method( 'is_google_connected' )
			->willReturn( true );

		$response        = $this->do_request( '/wc/v3/settings/google-for-woocommerce', 'GET', [ 'gla_syncable' => '1' ] );
		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $response_mapped['gla_google_connected']['value'] );
	}

	public function test_get_settings_with_connected_google_merchant_center_account() {
		$options = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'tax_rate'      => 'destination',
		];

		$this->options
			->method( 'get' )
			->willReturnCallback(
				function ( $name, $default_value = null ) use ( $options ) {
					if ( $name === OptionsInterface::MERCHANT_CENTER ) {
						return $options;
					}
					return $default_value;
				}
			);

		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( true );

		$response        = $this->do_request( '/wc/v3/settings/google-for-woocommerce', 'GET', [ 'gla_syncable' => '1' ] );
		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $options, $response_mapped['gla_merchant_center']['value'] );
	}

	/**
	 * Fall back to null in case the WP option value doesn't exist
	 */
	public function test_get_settings_with_connected_google_merchant_center_account_but_getting_fallback() {
		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( true );

		$response        = $this->do_request( '/wc/v3/settings/google-for-woocommerce', 'GET', [ 'gla_syncable' => '1' ] );
		$response_mapped = $this->maps_the_response_with_the_item_id( $response );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $response_mapped['gla_merchant_center']['value'] );
	}

	public function test_get_empty_settings_for_shipping_zone_methods_as_object() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/4/methods' );

		// dummy data
		$data = [
			[
				'id'       => '1',
				'settings' => [],
			],
		];

		$this->assertEquals(
			[
				[
					'id'       => '1',
					'settings' => (object) [],
				],
			],
			$this->controller->prepare_data( $data, $request )
		);

		// If the request is not for shipping zone methods, the data should not be modified.
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );

		$this->assertEquals(
			[
				[
					'id'       => '1',
					'settings' => [],
				],
			],
			$this->controller->prepare_data( $data, $request )
		);
	}

	public function test_product_types() {
		add_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'alter_product_price_types' ], 10, 3 );

		$product          = ProductHelper::create_simple_product();
		$product_variable = ProductHelper::create_variation_product();
		$variation        = $product_variable->get_available_variations()[0];
		$this->add_metadata( $product->get_id(), $this->get_test_metadata() );

		$request = $this->do_request( '/wc/v3/products', 'GET', [ 'gla_syncable' => '1' ] );
		$this->assertEquals( 'string', gettype( $request->get_data()[0]['price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()[0]['regular_price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()[0]['sale_price'] ) );

		$request = $this->do_request( '/wc/v3/products/' . $product->get_id(), 'GET', [ 'gla_syncable' => '1' ] );
		$this->assertEquals( 'string', gettype( $request->get_data()['price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()['regular_price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()['sale_price'] ) );

		$request = $this->do_request( '/wc/v3/products/' . $product_variable->get_id() . '/variations', 'GET', [ 'gla_syncable' => '1' ] );
		$this->assertEquals( 'string', gettype( $request->get_data()[0]['price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()[0]['regular_price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()[0]['sale_price'] ) );

		$request = $this->do_request( '/wc/v3/products/' . $product_variable->get_id() . '/variations/' . $variation['variation_id'], 'GET', [ 'gla_syncable' => '1' ] );
		$this->assertEquals( 'string', gettype( $request->get_data()['price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()['regular_price'] ) );
		$this->assertEquals( 'string', gettype( $request->get_data()['sale_price'] ) );

		// Doesn't apply if here is not 'gla_syncable'
		$request = $this->do_request( '/wc/v3/products' );
		$this->assertEquals( 'integer', gettype( $request->get_data()[0]['price'] ) );
		$this->assertEquals( 'integer', gettype( $request->get_data()[0]['regular_price'] ) );
		$this->assertEquals( 'integer', gettype( $request->get_data()[0]['sale_price'] ) );

		remove_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'alter_product_price_types' ] );
	}

	public function alter_product_price_types( $response ) {
		$response->data['price']         = intval( $response->data['price'] );
		$response->data['regular_price'] = intval( $response->data['regular_price'] );
		$response->data['sale_price']    = intval( $response->data['sale_price'] );

		return $response;
	}
}
