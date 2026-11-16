<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantAccessCircuitBreaker;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantAccessCircuitBreakerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class MerchantAccessCircuitBreakerTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MerchantAccessCircuitBreaker $breaker */
	protected $breaker;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->breaker = new MerchantAccessCircuitBreaker();
		$this->breaker->set_options_object( $this->options );
	}

	public function test_trip_records_the_rejection() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, 0 )
			->willReturn( 0 );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, $this->greaterThan( 0 ) );

		$this->breaker->trip();
	}

	public function test_trip_does_not_extend_an_open_window() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, 0 )
			->willReturn( time() - 60 );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->breaker->trip();
	}

	public function test_is_open_within_the_pause_window() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, 0 )
			->willReturn( time() - 60 );

		$this->assertTrue( $this->breaker->is_open() );
	}

	public function test_is_not_open_after_the_pause_window() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, 0 )
			->willReturn( time() - HOUR_IN_SECONDS - 1 );

		$this->assertFalse( $this->breaker->is_open() );
	}

	public function test_is_not_open_without_a_recorded_rejection() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, 0 )
			->willReturn( 0 );

		$this->assertFalse( $this->breaker->is_open() );
	}

	public function test_reset_clears_the_rejection() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT )
			->willReturn( time() );
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT );

		$this->breaker->reset();
	}

	public function test_reset_without_a_rejection_does_not_write() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT )
			->willReturn( null );
		$this->options->expects( $this->never() )->method( 'delete' );

		$this->breaker->reset();
	}

	/**
	 * @dataProvider provide_account_change_hooks
	 *
	 * @param string $hook
	 */
	public function test_account_change_ends_the_pause( string $hook ) {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT )
			->willReturn( time() );
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::MERCHANT_ACCESS_DENIED_AT );

		$this->breaker->register();
		do_action( $hook );
	}

	/**
	 * @return array
	 */
	public function provide_account_change_hooks(): array {
		return [
			'Google connection changed'  => [ 'update_option_gla_google_connected' ],
			'Merchant Center ID added'   => [ 'add_option_gla_merchant_id' ],
			'Merchant Center ID changed' => [ 'update_option_gla_merchant_id' ],
			'Merchant Center ID deleted' => [ 'delete_option_gla_merchant_id' ],
		];
	}

	public function test_unrelated_option_change_keeps_the_pause() {
		$this->options->expects( $this->never() )->method( 'delete' );

		$this->breaker->register();
		do_action( 'update_option_gla_ads_id' );
	}
}
