<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use BadMethodCallException;
use WC_Helper_Product;
use WP_UnitTestCase;

/**
 * Class ProductMetaHandlerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductMetaHandlerTest extends WP_UnitTestCase {

	use ProductTrait;

	/** @var ProductMetaHandler $product_meta_handler */
	protected $product_meta_handler;

	public function test_magic_call_throws_exception_invalid_method_name() {
		$this->expectException( BadMethodCallException::class );
		$this->product_meta_handler->in1va2lid_method();
	}

	public function test_magic_call_throws_exception_method_not_allowed() {
		$this->expectException( BadMethodCallException::class );
		$this->product_meta_handler->need_synced_at();
	}

	public function test_magic_update_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->update_invalid_meta_key_test( $this->generate_simple_product_mock(), 1 );
	}

	public function test_magic_delete_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->delete_invalid_meta_key_test( $this->generate_simple_product_mock() );
	}

	public function test_magic_get_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->get_invalid_meta_key_test( $this->generate_simple_product_mock() );
	}

	public function test_magic_call() {
		$product = $this->generate_simple_product_mock();
		$key     = 'synced_at';
		$value   = 12345;

		$product_meta_handler = $this->getMockBuilder( ProductMetaHandler::class )
			->setMethodsExcept( [ '__call' ] )
			->getMock();

		$product_meta_handler->expects( $this->once() )
			->method( 'update' )
			->with( $this->equalTo( $product ), $this->equalTo( $key ), $this->equalTo( $value ) );
		$product_meta_handler->update_synced_at( $product, $value );

		$product_meta_handler->expects( $this->once() )
			->method( 'get' )
			->with( $this->equalTo( $product ), $this->equalTo( $key ) );
		$product_meta_handler->get_synced_at( $product );

		$product_meta_handler->expects( $this->once() )
			->method( 'delete' )
			->with( $this->equalTo( $product ), $this->equalTo( $key ) );
		$product_meta_handler->delete_synced_at( $product );
	}

	public function test_update_type_cast() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_meta_handler->update( $product, ProductMetaHandler::KEY_SYNCED_AT, '12345' );
		$value = $product->get_meta( '_wc_gla_synced_at', true );
		$this->assertEquals( 12345, $value );
	}

	public function test_update() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_meta_handler->update( $product, ProductMetaHandler::KEY_SYNCED_AT, 12345 );
		$value = $product->get_meta( '_wc_gla_synced_at', true );
		$this->assertEquals( 12345, $value );
	}

	public function test_sync_hash_stores_and_returns_the_keyed_array() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_meta_handler->update( $product, ProductMetaHandler::KEY_SYNC_HASH, [ 'en|US' => 'hash-a' ] );

		$this->assertSame( [ 'en|US' => 'hash-a' ], $product->get_meta( '_wc_gla_sync_hash', true ) );
		$this->assertSame( [ 'en|US' => 'hash-a' ], $this->product_meta_handler->get( $product, ProductMetaHandler::KEY_SYNC_HASH ) );
	}

	public function test_update_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->update( $this->generate_simple_product_mock(), 'invalid_meta_key_test', 1 );
	}

	public function test_delete() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_wc_gla_synced_at', 12345 );
		$product->save_meta_data();

		$this->product_meta_handler->delete( $product, ProductMetaHandler::KEY_SYNCED_AT );

		$value = $product->get_meta( '_wc_gla_synced_at', true );
		$this->assertEmpty( $value );
	}

	public function test_delete_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->delete( $this->generate_simple_product_mock(), 'invalid_meta_key_test' );
	}

	public function test_get_returns_null_if_no_value_exist() {
		$product = WC_Helper_Product::create_simple_product();

		$value = $this->product_meta_handler->get( $product, ProductMetaHandler::KEY_SYNCED_AT );
		$this->assertNull( $value );
	}

	public function test_get() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_wc_gla_synced_at', 12345 );
		$product->save_meta_data();

		$value = $this->product_meta_handler->get( $product, ProductMetaHandler::KEY_SYNCED_AT );
		$this->assertEquals( 12345, $value );
	}

	public function test_get_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->get( $this->generate_simple_product_mock(), 'invalid_meta_key_test' );
	}

	public function test_is_meta_key_valid() {
		$this->assertFalse( ProductMetaHandler::is_meta_key_valid( 'invalid_meta_key_test' ) );
		$this->assertTrue( ProductMetaHandler::is_meta_key_valid( ProductMetaHandler::KEY_SYNCED_AT ) );
	}

	public function test_prefix_meta_query_keys() {
		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => ProductMetaHandler::KEY_VISIBILITY,
				'compare' => 'NOT EXISTS',
			],
			[
				'relation' => 'AND',
				[
					'key'     => ProductMetaHandler::KEY_VISIBILITY,
					'compare' => '!=',
					'value'   => 'dont-sync-and-show',
				],
				[
					'relation' => 'OR',
					[
						'key'     => ProductMetaHandler::KEY_SYNCED_AT,
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => ProductMetaHandler::KEY_SYNCED_AT,
						'compare' => '>',
						'value'   => 0,
					],
				],
			],
		];

		$result   = $this->product_meta_handler->prefix_meta_query_keys( $meta_query );
		$expected = [
			'relation' => 'OR',
			[
				'key'     => '_wc_gla_visibility',
				'compare' => 'NOT EXISTS',
			],
			[
				'relation' => 'AND',
				[
					'key'     => '_wc_gla_visibility',
					'compare' => '!=',
					'value'   => 'dont-sync-and-show',
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_wc_gla_synced_at',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_wc_gla_synced_at',
						'compare' => '>',
						'value'   => 0,
					],
				],
			],
		];

		$this->assertEqualSets( $expected, $result );
	}

	public function test_prefix_meta_query_keys_returns_unsupported_meta_keys_intact() {
		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => ProductMetaHandler::KEY_VISIBILITY,
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => 'a_meta_key_we_dont_support',
				'compare' => '!=',
				'value'   => null,
			],
			[
				'key'     => 'some_other_meta_key_we_dont_support',
				'compare' => '=',
				'value'   => 1,
			],
		];

		$result   = $this->product_meta_handler->prefix_meta_query_keys( $meta_query );
		$expected = [
			'relation' => 'OR',
			[
				'key'     => '_wc_gla_visibility',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => 'a_meta_key_we_dont_support',
				'compare' => '!=',
				'value'   => null,
			],
			[
				'key'     => 'some_other_meta_key_we_dont_support',
				'compare' => '=',
				'value'   => 1,
			],
		];

		$this->assertEqualSets( $expected, $result );
	}

	public function test_stage_update_changes_only_the_product_in_memory() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_meta_handler->stage_update( $product, ProductMetaHandler::KEY_VISIBILITY, 'dont-sync-and-show' );

		$this->assertEquals( 'dont-sync-and-show', $this->product_meta_handler->get_visibility( $product ) );
		$this->assertEmpty( get_post_meta( $product->get_id(), '_wc_gla_visibility', true ) );

		$product->save();

		$this->assertEquals( 'dont-sync-and-show', get_post_meta( $product->get_id(), '_wc_gla_visibility', true ) );
	}

	public function test_stage_delete_changes_only_the_product_in_memory() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_meta_handler->update_visibility( $product, 'dont-sync-and-show' );

		$this->product_meta_handler->stage_delete( $product, ProductMetaHandler::KEY_VISIBILITY );

		$this->assertNull( $this->product_meta_handler->get_visibility( $product ) );
		$this->assertEquals( 'dont-sync-and-show', get_post_meta( $product->get_id(), '_wc_gla_visibility', true ) );
	}

	public function test_stage_update_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );
		$this->product_meta_handler->stage_update( WC_Helper_Product::create_simple_product(), 'invalid_key', 1 );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_meta_handler = new ProductMetaHandler();
	}
}
