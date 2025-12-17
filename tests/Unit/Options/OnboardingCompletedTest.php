<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

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

	/** @var OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options              = $this->createMock( OptionsInterface::class );
		$this->onboarding_completed = new OnboardingCompleted();
		$this->onboarding_completed->set_options_object( $this->options );
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
}
