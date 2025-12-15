<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

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
}
