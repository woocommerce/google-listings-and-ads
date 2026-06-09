<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\NotOnboarded90DaysEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotOnboarded90DaysEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class NotOnboarded90DaysEvaluatorTest extends UnitTest {

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var NotOnboarded90DaysEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->onboarding_completed = $this->createMock( OnboardingCompleted::class );
		$this->options              = $this->createMock( OptionsInterface::class );

		$this->evaluator = new NotOnboarded90DaysEvaluator( $this->onboarding_completed );
		$this->evaluator->set_options_object( $this->options );
	}

	public function test_get_id() {
		$this->assertEquals( 'not-onboarded-90-days', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::NOT_ONBOARDED_90_DAYS, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::NOT_ONBOARDED_90_DAYS, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_not_onboarded_and_wc_installed_over_90_days_ago() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( time() - ( 91 * DAY_IN_SECONDS ) );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_onboarding_complete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_wc_install_timestamp_missing() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_wc_installed_less_than_90_days_ago() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( time() - ( 30 * DAY_IN_SECONDS ) );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
