<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\NotOnboardedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotOnboardedEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class NotOnboardedEvaluatorTest extends UnitTest {

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var NotOnboardedEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->onboarding_completed = $this->createMock( OnboardingCompleted::class );
		$this->options              = $this->createMock( OptionsInterface::class );
		$this->wp                   = $this->createMock( WP::class );

		$this->evaluator = new NotOnboardedEvaluator( $this->onboarding_completed );
		$this->evaluator->set_options_object( $this->options );
		$this->evaluator->set_wp_proxy_object( $this->wp );
	}

	public function test_get_id() {
		$this->assertEquals( 'not-onboarded', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::NOT_ONBOARDED, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::NOT_ONBOARDED, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_not_onboarded_and_wc_installed_over_90_days_ago() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'completed' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 91 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_not_onboarded_and_plugin_installed_over_90_days_ago() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'completed' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, null ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, time() - ( 91 * DAY_IN_SECONDS ) ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_wc_is_older_than_plugin_and_over_90_days() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'completed' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 91 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, time() - ( 10 * DAY_IN_SECONDS ) ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_wc_onboarding_was_skipped() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'skipped' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 91 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_exactly_90_days_have_elapsed() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'completed' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 90 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_onboarding_complete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );

		$this->wp->expects( $this->never() )->method( 'get_option' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_wc_onboarding_incomplete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile(
			[
				'completed' => false,
				'skipped'   => false,
			]
		);

		$this->options->expects( $this->never() )->method( 'get' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_wc_onboarding_profile_missing() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [] );

		$this->options->expects( $this->never() )->method( 'get' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_install_timestamps_missing() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'completed' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, null ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_installed_less_than_90_days_ago() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->mock_wc_onboarding_profile( [ 'completed' => true ] );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 30 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, time() - ( 30 * DAY_IN_SECONDS ) ],
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * Mock the WooCommerce onboarding profile option.
	 *
	 * @param array $profile Onboarding profile data.
	 */
	private function mock_wc_onboarding_profile( array $profile ): void {
		$this->wp->method( 'get_option' )
			->with( 'woocommerce_onboarding_profile', [] )
			->willReturn( $profile );
	}
}
