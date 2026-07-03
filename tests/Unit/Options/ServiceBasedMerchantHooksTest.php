<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class ServiceBasedMerchantHooksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class ServiceBasedMerchantHooksTest extends UnitTest {

	/** @var MockObject|ServiceBasedMerchantState */
	protected $state;

	/** @var ServiceBasedMerchantHooks */
	protected $hooks;

	public function setUp(): void {
		parent::setUp();

		$this->state = $this->createMock( ServiceBasedMerchantState::class );
		$this->hooks = new ServiceBasedMerchantHooks( $this->state );
	}

	public function test_register_adds_expected_hooks() {
		$this->hooks->register();

		$this->assertGreaterThan( 0, has_action( 'woocommerce_new_product', [ $this->hooks, 'handle_product_change' ] ) );
		$this->assertGreaterThan( 0, has_action( 'woocommerce_update_product', [ $this->hooks, 'handle_product_change' ] ) );
		$this->assertGreaterThan( 0, has_action( 'untrashed_post', [ $this->hooks, 'handle_product_restore' ] ) );
		$this->assertGreaterThan( 0, has_action( 'trashed_post', [ $this->hooks, 'handle_product_removal' ] ) );
		$this->assertGreaterThan( 0, has_action( 'deleted_post', [ $this->hooks, 'handle_product_removal' ] ) );
	}

	public function test_handle_product_change_resets_flag_when_physical_product_added_to_service_based_store() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();

		$this->state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->state->expects( $this->once() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_change( $product->get_id(), $product );

		$product->delete( true );
	}

	public function test_handle_product_change_does_not_reset_when_virtual_product_added_to_service_based_store() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( true );
		$product->save();

		$this->state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->state->expects( $this->never() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_change( $product->get_id(), $product );

		$product->delete( true );
	}

	public function test_handle_product_change_does_not_reset_when_store_is_already_product_based() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();

		$this->state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->state->expects( $this->never() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_change( $product->get_id(), $product );

		$product->delete( true );
	}

	public function test_handle_product_change_loads_product_when_not_passed() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();

		$this->state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->state->expects( $this->once() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_change( $product->get_id() );

		$product->delete( true );
	}

	public function test_handle_product_removal_resets_flag_when_product_trashed_in_product_based_store() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();
		$product_id = $product->get_id();

		$this->state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->state->expects( $this->once() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_removal( $product_id );

		$product->delete( true );
	}

	public function test_handle_product_removal_does_not_reset_when_store_is_already_service_based() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		$product_id = $product->get_id();

		$this->state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->state->expects( $this->never() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_removal( $product_id );

		$product->delete( true );
	}

	public function test_handle_product_removal_ignores_non_product_post_types() {
		$post_id = wp_insert_post(
			[
				'post_type'   => 'post',
				'post_title'  => 'Test Post',
				'post_status' => 'publish',
			]
		);

		$this->state->expects( $this->never() )->method( 'is_service_based_merchant' );
		$this->state->expects( $this->never() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_removal( $post_id );

		wp_delete_post( $post_id, true );
	}

	public function test_handle_product_restore_resets_flag_when_physical_product_restored_to_service_based_store() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();

		$this->state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->state->expects( $this->once() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_restore( $product->get_id() );

		$product->delete( true );
	}

	public function test_handle_product_restore_does_not_reset_when_virtual_product_restored() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( true );
		$product->save();

		$this->state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->state->expects( $this->never() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_restore( $product->get_id() );

		$product->delete( true );
	}

	public function test_handle_product_restore_ignores_non_product_post_types() {
		$post_id = wp_insert_post(
			[
				'post_type'   => 'post',
				'post_title'  => 'Test Post',
				'post_status' => 'publish',
			]
		);

		$this->state->expects( $this->never() )->method( 'is_service_based_merchant' );
		$this->state->expects( $this->never() )->method( 'reset_service_based_merchant_status' );

		$this->hooks->handle_product_restore( $post_id );

		wp_delete_post( $post_id, true );
	}
}
