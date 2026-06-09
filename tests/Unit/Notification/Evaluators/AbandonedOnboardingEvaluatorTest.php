<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbandonedOnboardingEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class AbandonedOnboardingEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantAccountState $merchant_account_state */
	protected $merchant_account_state;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var AbandonedOnboardingEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_account_state = $this->createMock( MerchantAccountState::class );
		$this->merchant_center        = $this->createMock( MerchantCenterService::class );
		$this->options                = $this->createMock( OptionsInterface::class );

		$this->evaluator = new AbandonedOnboardingEvaluator( $this->merchant_account_state );
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
		$this->evaluator->set_options_object( $this->options );
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

	public function test_should_show_when_mc_incomplete_and_google_connected() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, true ],
				[ OptionsInterface::WP_TOS_ACCEPTED, false, false ],
			]
		);
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn( [] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_mc_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_onboarding_step_started() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->options->method( 'get' )->willReturn( false );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn( [] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_merchant_account_step_completed() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->options->method( 'get' )->willReturn( false );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn(
			[
				'set_id' => [
					'status'  => AccountState::STEP_DONE,
					'message' => '',
					'data'    => [],
				],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}
}
