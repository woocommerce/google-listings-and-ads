<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\JetpackAuthCircuitBreaker;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class JetpackAuthCircuitBreakerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class JetpackAuthCircuitBreakerTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var JetpackAuthCircuitBreaker $breaker */
	protected $breaker;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->breaker = new JetpackAuthCircuitBreaker();
		$this->breaker->set_options_object( $this->options );
	}

	public function test_trip_records_the_failure_and_short_circuits_the_request() {
		$this->options->method( 'get' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT, 0 )
			->willReturn( 0 );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT, $this->greaterThan( 0 ) );

		$this->assertFalse( $this->breaker->was_tripped_in_request() );

		$this->breaker->trip();

		$this->assertTrue( $this->breaker->was_tripped_in_request() );
	}

	public function test_trip_does_not_extend_an_open_window() {
		$this->options->method( 'get' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT, 0 )
			->willReturn( time() - 60 );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->breaker->trip();

		$this->assertTrue( $this->breaker->was_tripped_in_request() );
	}

	public function test_is_open_within_the_pause_window() {
		$failed_at = time() - 60;
		$this->options->method( 'get' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT, 0 )
			->willReturn( $failed_at );

		$this->assertTrue( $this->breaker->is_open() );
		$this->assertSame( $failed_at + HOUR_IN_SECONDS, $this->breaker->get_retry_time() );
	}

	public function test_is_not_open_after_the_pause_window() {
		$this->options->method( 'get' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT, 0 )
			->willReturn( time() - HOUR_IN_SECONDS - 1 );

		$this->assertFalse( $this->breaker->is_open() );
	}

	public function test_is_not_open_without_a_recorded_failure() {
		$this->options->method( 'get' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT, 0 )
			->willReturn( 0 );

		$this->assertFalse( $this->breaker->is_open() );
		$this->assertSame( 0, $this->breaker->get_retry_time() );
	}

	public function test_reset_clears_the_failure() {
		$this->options->method( 'get' )
			->willReturn( time() );
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT );

		$this->breaker->trip();
		$this->breaker->reset();

		$this->assertFalse( $this->breaker->was_tripped_in_request() );
	}

	public function test_reset_without_a_failure_does_not_write() {
		$this->options->method( 'get' )
			->with( OptionsInterface::JETPACK_AUTH_FAILED_AT )
			->willReturn( null );
		$this->options->expects( $this->never() )->method( 'delete' );

		$this->breaker->reset();
	}
}
