<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class OnboardingCompletedTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class OnboardingCompletedTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options              = $this->createMock( OptionsInterface::class );
		$this->merchant_center      = $this->createMock( MerchantCenterService::class );
		$this->onboarding_completed = new OnboardingCompleted();
		$this->onboarding_completed->set_options_object( $this->options );
		$this->onboarding_completed->set_merchant_center_object( $this->merchant_center );
	}

	/**
	 * Test that register method adds the action hook.
	 */
	public function test_register_adds_action_hook(): void {
		$this->onboarding_completed->register();

		$this->assertTrue(
			has_action( 'woocommerce_gla_onboarding_completed' ),
			'woocommerce_gla_onboarding_completed action should be registered'
		);
	}

	/**
	 * Test that the action hook fires and updates the timestamp.
	 */
	public function test_onboarding_completed_action_updates_timestamp(): void {
		$this->onboarding_completed->register();

		// Mock the options update to expect a timestamp
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::ONBOARDING_COMPLETED_AT,
				$this->callback(
					function ( $timestamp ) {
						// Verify it's a valid timestamp (integer)
						return is_int( $timestamp ) && $timestamp > 0;
					}
				)
			);

		// Fire the action
		do_action( 'woocommerce_gla_onboarding_completed' );
	}

	/**
	 * Test that the timestamp is approximately the current time.
	 */
	public function test_onboarding_completed_sets_current_timestamp(): void {
		$this->onboarding_completed->register();

		$before_time = time();

		// Capture the timestamp that was set
		$captured_timestamp = null;
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::ONBOARDING_COMPLETED_AT,
				$this->callback(
					function ( $timestamp ) use ( &$captured_timestamp ) {
						$captured_timestamp = $timestamp;
						return true;
					}
				)
			);

		// Fire the action
		do_action( 'woocommerce_gla_onboarding_completed' );

		$after_time = time();

		// Verify the timestamp is between before and after (allowing 1 second difference)
		$this->assertGreaterThanOrEqual( $before_time, $captured_timestamp );
		$this->assertLessThanOrEqual( $after_time + 1, $captured_timestamp );
	}

	/**
	 * Test is_onboarding_complete returns true when ONBOARDING_COMPLETED_AT is set to a timestamp.
	 */
	public function test_is_onboarding_complete_returns_true_when_timestamp_set(): void {
		$this->options->method( 'get' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT )
			->willReturn( time() );

		$this->assertTrue( $this->onboarding_completed->is_onboarding_complete() );
	}

	/**
	 * Test is_onboarding_complete returns false when ONBOARDING_COMPLETED_AT is set to 0 or false.
	 */
	public function test_is_onboarding_complete_returns_false_when_falsy_value_set(): void {
		$this->options->method( 'get' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT )
			->willReturn( 0 );

		$this->assertFalse( $this->onboarding_completed->is_onboarding_complete() );
	}

	/**
	 * Test is_onboarding_complete backfills ONBOARDING_COMPLETED_AT from MC_SETUP_COMPLETED_AT
	 * for existing users who completed setup before the ONBOARDING_COMPLETED_AT option existed.
	 */
	public function test_is_onboarding_complete_backfills_from_mc_setup(): void {
		$mc_timestamp = 1234567890;

		// Set up options mock: ONBOARDING_COMPLETED_AT is null, MC_SETUP_COMPLETED_AT is set
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $name ) use ( $mc_timestamp ) {
					if ( $name === OptionsInterface::ONBOARDING_COMPLETED_AT ) {
						return null; // Not set yet
					}
					if ( $name === OptionsInterface::MC_SETUP_COMPLETED_AT ) {
						return $mc_timestamp; // Existing user has MC setup complete
					}
					return null;
				}
			);

		// Set up merchant center mock: setup is complete
		$this->merchant_center->method( 'is_setup_complete' )
			->willReturn( true );

		// Should update ONBOARDING_COMPLETED_AT with MC_SETUP_COMPLETED_AT timestamp
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT, $mc_timestamp );

		$result = $this->onboarding_completed->is_onboarding_complete();

		$this->assertTrue( $result );
	}

	/**
	 * Test is_onboarding_complete returns false when both options are not set.
	 */
	public function test_is_onboarding_complete_returns_false_when_not_set(): void {
		// Set up options mock: both options return null
		$this->options->method( 'get' )
			->willReturn( null );

		// Set up merchant center mock: setup is not complete
		$this->merchant_center->method( 'is_setup_complete' )
			->willReturn( false );

		$this->assertFalse( $this->onboarding_completed->is_onboarding_complete() );
	}
}
