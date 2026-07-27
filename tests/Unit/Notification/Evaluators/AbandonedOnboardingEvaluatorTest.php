<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbandonedOnboardingEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class AbandonedOnboardingEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var AbandonedOnboardingEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center              = $this->createMock( MerchantCenterService::class );
		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->onboarding_completed         = $this->createMock( OnboardingCompleted::class );

		$this->evaluator = new AbandonedOnboardingEvaluator( $this->service_based_merchant_state, $this->onboarding_completed );
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
	}

	public function test_get_id() {
		$this->assertEquals( 'abandoned-onboarding', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::ABANDONED_ONBOARDING, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::ABANDONED_ONBOARDING, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_onboarding_not_started() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( false );
		$this->merchant_center->expects( $this->never() )->method( 'get_setup_status' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_setup_complete() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->merchant_center->method( 'get_setup_status' )->willReturn( [ 'status' => 'complete' ] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_for_completed_service_based_merchant() {
		// Regression: a service-based merchant who finished the ads-only onboarding never
		// completes Merchant Center setup, so the MC setup status must not be consulted and
		// the notification must not fire.
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->merchant_center->expects( $this->never() )->method( 'get_setup_status' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_for_service_based_merchant_mid_onboarding() {
		// A service-based merchant who has not finished onboarding is still genuinely
		// mid-onboarding and should receive the notification (Merchant Center step excluded).
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->merchant_center->expects( $this->never() )->method( 'get_setup_status' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_accounts_step() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->mock_setup_step( 'accounts' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_product_listings_step() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->mock_setup_step( 'product_listings' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_paid_ads_step() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->mock_setup_step( 'paid_ads' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * Mock the mc/setup status to report an incomplete setup at the given step.
	 *
	 * @param string $step
	 */
	private function mock_setup_step( string $step ): void {
		$this->merchant_center->method( 'get_setup_status' )->willReturn(
			[
				'status' => 'incomplete',
				'step'   => $step,
			]
		);
	}
}
