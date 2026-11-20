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
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED ],
				[ OptionsInterface::IS_SERVICE_BASED_MERCHANT ]
			)
			->willReturnOnConsecutiveCalls( null, 'yes' );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		$this->assertTrue( $result );
	}

	public function test_is_service_based_merchant_returns_false() {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED ],
				[ OptionsInterface::IS_SERVICE_BASED_MERCHANT ]
			)
			->willReturnOnConsecutiveCalls( null, 'no' );

		$result = $this->service_based_merchant_state->is_service_based_merchant();

		$this->assertFalse( $result );
	}

	public function test_is_service_based_merchant_calculates_when_option_is_null() {
		// Create a physical product that requires shipping
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->save();

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED ],
				[ OptionsInterface::IS_SERVICE_BASED_MERCHANT ]
			)
			->willReturnOnConsecutiveCalls( null, null );

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

	public function test_supported_products_confirmation_overrides_service_based_status() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED )
			->willReturn( 'yes' );

		$this->assertFalse( $this->service_based_merchant_state->is_service_based_merchant() );
	}

	public function test_confirm_supported_products_updates_confirmation_option() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED )
			->willReturn( null );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED, 'yes' )
			->willReturn( true );

		$this->assertTrue( $this->service_based_merchant_state->confirm_supported_products() );
	}

	public function test_confirm_supported_products_returns_true_when_already_confirmed() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED )
			->willReturn( 'yes' );

		$this->options->expects( $this->never() )
			->method( 'update' );

		$this->assertTrue( $this->service_based_merchant_state->confirm_supported_products() );
	}

	public function test_confirm_supported_products_returns_false_when_update_fails() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED )
			->willReturn( null );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED, 'yes' )
			->willReturn( false );

		$this->assertFalse( $this->service_based_merchant_state->confirm_supported_products() );
	}

	public function test_reset_supported_products_confirmation_deletes_confirmation_and_cached_status_options() {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive(
				[ OptionsInterface::SUPPORTED_PRODUCTS_CONFIRMED ],
				[ OptionsInterface::IS_SERVICE_BASED_MERCHANT ]
			);

		$this->service_based_merchant_state->reset_supported_products_confirmation();
	}

	public function test_reset_service_based_merchant_status_preserves_supported_products_confirmation() {
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		$this->service_based_merchant_state->reset_service_based_merchant_status();
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

	public function test_has_physical_products_stops_when_the_query_keeps_returning_the_same_batch() {
		$products = [];

		// More products than a single batch, none of them reporting that they need shipping.
		for ( $i = 0; $i < 101; $i++ ) {
			$products[] = WC_Helper_Product::create_simple_product();
		}

		$queries       = 0;
		$ignore_offset = function ( $args ) use ( &$queries ) {
			++$queries;
			$args['offset'] = 0;

			return $args;
		};

		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $ignore_offset );
		add_filter( 'woocommerce_product_needs_shipping', '__return_false' );

		$result = $this->service_based_merchant_state->has_physical_products();

		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $ignore_offset );
		remove_filter( 'woocommerce_product_needs_shipping', '__return_false' );

		// The second batch repeats the first one, so the loop must end instead of running forever.
		$this->assertFalse( $result );
		$this->assertSame( 2, $queries );

		// Cleanup
		foreach ( $products as $product ) {
			$product->delete( true );
		}
	}

	public function test_has_physical_products_finds_the_physical_product_when_the_query_ignores_the_offset() {
		$products = [];

		// The only physical product is the oldest one, so it is the last one a full scan would reach.
		$physical_product = WC_Helper_Product::create_simple_product();
		$physical_product->set_virtual( false );
		$physical_product->set_date_created( '2020-01-01 00:00:00' );
		$physical_product->save();
		$products[] = $physical_product;

		// More virtual products than a single batch.
		for ( $i = 0; $i < 101; $i++ ) {
			$virtual_product = WC_Helper_Product::create_simple_product();
			$virtual_product->set_virtual( true );
			$virtual_product->save();
			$products[] = $virtual_product;
		}

		$ignore_offset = function ( $args ) {
			$args['offset'] = 0;

			return $args;
		};

		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $ignore_offset );

		$result = $this->service_based_merchant_state->has_physical_products();

		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $ignore_offset );

		// Virtual products are not part of the query, so the physical one is found in the first batch.
		$this->assertTrue( $result );

		// Cleanup
		foreach ( $products as $product ) {
			$product->delete( true );
		}
	}
}
