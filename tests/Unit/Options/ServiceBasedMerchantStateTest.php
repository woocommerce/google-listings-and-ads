<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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

	/** @var ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	public function setUp(): void {
		parent::setUp();

		$this->options                      = $this->createMock( OptionsInterface::class );
		$this->service_based_merchant_state = new ServiceBasedMerchantState();
		$this->service_based_merchant_state->set_options_object( $this->options );
	}

	public function test_is_service_based_merchant_returns_cached_value_when_option_exists() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT )
			->willReturn( 'yes' );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		$this->assertTrue( $result );
	}

	public function test_is_service_based_merchant_returns_false() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT )
			->willReturn( 'no' );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		$this->assertFalse( $result );
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
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT, 'no' );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		// Has physical products, so NOT service-based
		$this->assertFalse( $result );

		// Cleanup
		$physical_product->delete( true );
	}

	public function test_has_physical_products_returns_true_when_physical_products_exist() {
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
		$virtual_product = WC_Helper_Product::create_simple_product();
		$virtual_product->set_virtual( true );
		$virtual_product->save();

		$result = $this->service_based_merchant_state->has_physical_products();

		$this->assertFalse( $result );

		// Cleanup
		$virtual_product->delete( true );
	}

	public function test_has_physical_products_exits_early_when_match_is_found() {
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

	public function test_has_physical_products_handles_batch_processing() {
		$products = [];

		// Create 150 virtual products (exceeds BATCH_SIZE of 100)
		for ( $i = 0; $i < 150; $i++ ) {
			$virtual_product = WC_Helper_Product::create_simple_product();
			$virtual_product->set_virtual( true );
			$virtual_product->save();
			$products[] = $virtual_product;
		}

		// Create 1 physical product at the end
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();
		$products[] = $physical_product;

		$result = $this->service_based_merchant_state->has_physical_products();

		// Should process multiple batches and find the physical product
		$this->assertTrue( $result );

		// Cleanup
		foreach ( $products as $product ) {
			$product->delete( true );
		}
	}
}
