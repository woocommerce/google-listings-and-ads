<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ServiceBasedMerchantStateTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class ServiceBasedMerchantStateTest extends ContainerAwareUnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	public function setUp(): void {
		parent::setUp();

		$this->options                      = $this->createMock( OptionsInterface::class );
		$this->transients                   = $this->createMock( TransientsInterface::class );
		$this->service_based_merchant_state = new ServiceBasedMerchantState();
		$this->service_based_merchant_state->set_options_object( $this->options );
		$this->service_based_merchant_state->set_transients_object( $this->transients );
	}

	/**
	 * Helper method to unregister all hooks registered by ServiceBasedMerchantState.
	 */
	private function unregister_all_hooks(): void {
		remove_action( 'woocommerce_new_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'woocommerce_update_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'woocommerce_new_product_variation', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'woocommerce_update_product_variation', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'wp_trash_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ], 10 );
		remove_action( 'before_delete_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ], 10 );
		remove_action( 'deleted_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ], 10 );
		remove_action( 'untrashed_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ], 10 );
		remove_action( 'woocommerce_delete_product_transients', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
	}

	public function test_is_service_based_merchant_returns_cached_value_when_option_exists() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT )
			->willReturn( true );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		$this->assertTrue( $result );
	}

	public function test_is_service_based_merchant_calculates_when_option_is_null() {
		// Create a physical product that requires shipping
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT )
			->willReturn( null );

		// Should calculate and save when option is null
		$this->transients->expects( $this->once() )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		// Has physical products, so NOT service-based
		$this->assertFalse( $result );

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_calculate_service_based_merchant_calculates_and_saves_value() {
		// Create a physical product that requires shipping
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		$result = $this->service_based_merchant_state->calculate_service_based_merchant();

		// Has physical products, so NOT service-based
		$this->assertFalse( $result );

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_calculate_service_based_merchant_returns_true_when_no_physical_products() {
		// Create a virtual product
		$virtual_product = WC_Helper_Product::create_simple_product();
		$virtual_product->set_virtual( true );
		$virtual_product->save();

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, true );

		$result = $this->service_based_merchant_state->calculate_service_based_merchant();

		// No physical products, so service-based
		$this->assertTrue( $result );

		// Cleanup
		$virtual_product->delete( true );
	}

	public function test_has_physical_products_returns_true_when_physical_products_exist() {
		// Mock cache miss
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		// Mock cache set
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Create a physical product that requires shipping
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertTrue( $result );

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_has_physical_products_returns_false_when_no_physical_products_exist() {
		// Mock cache miss
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		// Mock cache set
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 0, \HOUR_IN_SECONDS );

		// Create a virtual product
		$virtual_product = WC_Helper_Product::create_simple_product();
		$virtual_product->set_virtual( true );
		$virtual_product->save();

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertFalse( $result );

		// Cleanup
		$virtual_product->delete( true );
	}

	public function test_has_physical_products_ignores_virtual_products() {
		// Mock cache miss
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		// Mock cache set
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 0, \HOUR_IN_SECONDS );

		$virtual_product = WC_Helper_Product::create_simple_product();
		$virtual_product->set_virtual( true );
		$virtual_product->save();

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertFalse( $result );

		// Cleanup
		$virtual_product->delete( true );
	}

	public function test_has_physical_products_exits_early_when_match_is_found() {
		// Mock cache miss
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		// Mock cache set
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Create a physical product
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		// Create a virtual product
		$virtual_product = WC_Helper_Product::create_simple_product();
		$virtual_product->set_virtual( true );
		$virtual_product->save();

		$result = $this->service_based_merchant_state->has_physical_products();

		// Should return true as soon as physical product is found
		$this->assertTrue( $result );

		// Cleanup
		$physical_product->delete( true );
		$virtual_product->delete( true );
	}

	public function test_has_physical_products_uses_cache_when_available() {
		// Mock cache hit
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( 1 );

		// Should not call set when cache exists
		$this->transients->expects( $this->never() )
			->method( 'set' );

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertTrue( $result );
	}

	public function test_has_physical_products_filter_bypasses_cache() {
		// Add filter to bypass cache
		add_filter(
			'woocommerce_gla_has_physical_products',
			function () {
				return true;
			}
		);

		// Should not check cache when filter returns a value
		$this->transients->expects( $this->never() )
			->method( 'get' );

		// Should not set cache when filter is used
		$this->transients->expects( $this->never() )
			->method( 'set' );

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertTrue( $result );

		// Cleanup
		remove_all_filters( 'woocommerce_gla_has_physical_products' );
	}

	public function test_has_physical_products_filter_modifies_result() {
		// Add filter to modify result
		add_filter(
			'woocommerce_gla_has_physical_products',
			function () {
				return false;
			}
		);

		// Should not check cache when filter returns a value
		$this->transients->expects( $this->never() )
			->method( 'get' );

		// Should not set cache when filter is used
		$this->transients->expects( $this->never() )
			->method( 'set' );

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertFalse( $result );

		// Cleanup
		remove_all_filters( 'woocommerce_gla_has_physical_products' );
	}

	public function test_clear_physical_products_cache() {
		$this->transients->expects( $this->once() )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		$result = $this->service_based_merchant_state->clear_physical_products_cache();

		$this->assertTrue( $result );
	}

	public function test_calculate_service_based_merchant_clears_cache_before_calculation() {
		// Create a physical product
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		// Mock cache clear (called before calculation to ensure fresh data)
		$this->transients->expects( $this->once() )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Mock cache operations for has_physical_products (cache miss after clear, then set)
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		$result = $this->service_based_merchant_state->calculate_service_based_merchant();

		$this->assertFalse( $result );

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_register_hooks_are_added() {
		$this->service_based_merchant_state->register();

		// Verify product hooks are registered.
		$this->assertEquals( 10, has_action( 'woocommerce_new_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ] ) );
		$this->assertEquals( 10, has_action( 'woocommerce_update_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ] ) );
		$this->assertEquals( 10, has_action( 'woocommerce_new_product_variation', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ] ) );
		$this->assertEquals( 10, has_action( 'woocommerce_update_product_variation', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ] ) );

		// Verify post hooks are registered.
		$this->assertEquals( 10, has_action( 'wp_trash_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ] ) );
		$this->assertEquals( 10, has_action( 'before_delete_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ] ) );
		$this->assertEquals( 10, has_action( 'deleted_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ] ) );
		$this->assertEquals( 10, has_action( 'untrashed_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ] ) );

		// Verify WooCommerce transient clearing hook is registered.
		$this->assertEquals( 10, has_action( 'woocommerce_delete_product_transients', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ] ) );
	}

	public function test_clear_cache_on_product_change_clears_cache_and_deletes_option() {
		// Create a physical product for recalculation
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation (cache miss after clear, then set)
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		$this->service_based_merchant_state->clear_cache_on_product_change( 123 );

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_maybe_clear_cache_on_post_change_clears_cache_and_deletes_option_for_products() {
		// Create a physical product post.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();
		$product_id = $product->get_id();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation (cache miss after clear, then set)
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		$this->service_based_merchant_state->maybe_clear_cache_on_post_change( $product_id );

		// Cleanup.
		$product->delete( true );
	}

	public function test_maybe_clear_cache_on_post_change_does_not_clear_cache_for_non_products() {
		// Create a non-product post.
		$post_id = $this->factory()->post->create( [ 'post_type' => 'post' ] );

		$this->transients->expects( $this->never() )
			->method( 'delete' );

		$this->service_based_merchant_state->maybe_clear_cache_on_post_change( $post_id );

		// Cleanup.
		wp_delete_post( $post_id, true );
	}

	public function test_product_update_hook_clears_cache_and_deletes_option() {
		$this->service_based_merchant_state->register();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		// Trigger the update hook.
		do_action( 'woocommerce_update_product', $product->get_id(), $product );

		// Unregister all hooks before cleanup to avoid triggering them during product deletion.
		$this->unregister_all_hooks();

		// Cleanup.
		$product->delete( true );
	}

	public function test_product_new_hook_clears_cache_and_deletes_option() {
		$this->service_based_merchant_state->register();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		// Trigger the new product hook with both arguments that WooCommerce passes.
		do_action( 'woocommerce_new_product', $product->get_id(), $product );

		// Unregister all hooks before cleanup to avoid triggering them during product deletion.
		$this->unregister_all_hooks();

		// Cleanup.
		$product->delete( true );
	}

	public function test_product_delete_hook_clears_cache_and_deletes_option() {
		$this->service_based_merchant_state->register();

		// Create a physical product
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();
		$product_id = $product->get_id();

		// Get the current state before deletion to determine expected result
		// If there are other physical products in the database, the result will be different
		$products_before    = wc_get_products(
			[
				'limit'  => -1,
				'status' => 'publish',
			]
		);
		$has_other_physical = false;
		foreach ( $products_before as $p ) {
			if ( $p->get_id() !== $product_id && ! $p->is_virtual() && $p->needs_shipping() ) {
				$has_other_physical = true;
				break;
			}
		}

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation
		// Note: deleted_post hook fires AFTER deletion, so the product no longer exists
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		// Expected result depends on whether there are other physical products
		$expected_has_physical  = $has_other_physical ? 1 : 0;
		$expected_service_based = ! $has_other_physical;

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, $expected_has_physical, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, $expected_service_based );

		// Unregister product hooks and before_delete_post before deleting to avoid double-triggering.
		// wp_delete_post fires before_delete_post (product still exists) and deleted_post (product deleted).
		// We only want to test deleted_post, so unregister before_delete_post.
		remove_action( 'woocommerce_new_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'woocommerce_update_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'before_delete_post', [ $this->service_based_merchant_state, 'maybe_clear_cache_on_post_change' ], 10 );

		// Actually delete the product (this will trigger the deleted_post hook)
		// The deleted_post hook fires AFTER deletion, so the product no longer exists
		wp_delete_post( $product_id, true );

		// Unregister all remaining hooks.
		$this->unregister_all_hooks();
	}

	public function test_woocommerce_delete_product_transients_hook_clears_cache_and_deletes_option() {
		$this->service_based_merchant_state->register();

		// Create a physical product for recalculation
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		// Trigger the WooCommerce transient clearing hook.
		do_action( 'woocommerce_delete_product_transients', 0 );

		// Unregister all hooks before cleanup to avoid triggering them during product deletion.
		$this->unregister_all_hooks();

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_wp_trash_post_hook_clears_cache_and_deletes_option() {
		$this->service_based_merchant_state->register();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();
		$product_id = $product->get_id();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation
		// Note: wp_trash_post hook fires BEFORE trashing, so the product still exists as published
		// However, we want to test that the hook triggers recalculation
		// Since the product still exists when the hook fires, it will find 1 physical product
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		// The product still exists when the hook fires, so service_based = false
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		// Unregister product hooks before trashing to avoid double-triggering.
		remove_action( 'woocommerce_new_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'woocommerce_update_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );

		// Actually trash the product (this will trigger the wp_trash_post hook)
		// The wp_trash_post hook fires BEFORE the post is trashed
		wp_trash_post( $product_id );

		// Unregister all remaining hooks before cleanup.
		$this->unregister_all_hooks();

		// Cleanup - delete permanently since it's already trashed.
		wp_delete_post( $product_id, true );
	}

	public function test_untrashed_post_hook_clears_cache_and_deletes_option() {
		$this->service_based_merchant_state->register();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( false );
		$product->save();
		$product_id = $product->get_id();

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( true );

		// Option should be deleted
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// Cache operations for recalculation
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS )
			->willReturn( null );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::HAS_PHYSICAL_PRODUCTS, 1, \HOUR_IN_SECONDS );

		// Option should be updated with recalculated value
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, false );

		// Unregister product hooks before triggering untrash to avoid double-triggering.
		remove_action( 'woocommerce_new_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );
		remove_action( 'woocommerce_update_product', [ $this->service_based_merchant_state, 'clear_cache_on_product_change' ], 10 );

		// Trigger the untrash hook with both arguments that WordPress passes (post_id, previous_status).
		do_action( 'untrashed_post', $product_id, 'publish' );

		// Unregister all remaining hooks before cleanup.
		$this->unregister_all_hooks();

		// Cleanup.
		$product->delete( true );
	}
}
