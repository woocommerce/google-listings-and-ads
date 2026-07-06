<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
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

	/** @var AbandonedOnboardingEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center = $this->createMock( MerchantCenterService::class );

		$this->evaluator = new AbandonedOnboardingEvaluator();
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
		$this->merchant_center->method( 'get_setup_status' )->willReturn( [ 'status' => 'complete' ] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_accounts_step() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->mock_setup_step( 'accounts' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_product_listings_step() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->mock_setup_step( 'product_listings' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_paid_ads_step() {
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
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
