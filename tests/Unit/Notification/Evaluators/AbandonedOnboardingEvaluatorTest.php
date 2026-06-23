<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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

	/** @var MockObject|MerchantAccountState $merchant_account_state */
	protected $merchant_account_state;

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var AbandonedOnboardingEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_account_state       = $this->createMock( MerchantAccountState::class );
		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->merchant_center              = $this->createMock( MerchantCenterService::class );
		$this->ads_service                  = $this->createMock( AdsService::class );
		$this->options                      = $this->createMock( OptionsInterface::class );

		$this->evaluator = new AbandonedOnboardingEvaluator(
			$this->merchant_account_state,
			$this->service_based_merchant_state
		);
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
		$this->evaluator->set_ads_object( $this->ads_service );
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

	public function test_should_show_when_accounts_step_and_google_not_connected() {
		$this->mock_onboarding_started_via_tos();
		$this->mock_accounts_setup_step();
		$this->merchant_center->method( 'is_google_connected' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_accounts_step_and_ads_not_connected() {
		$this->mock_onboarding_started_via_google();
		$this->mock_accounts_setup_step();
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->ads_service->method( 'connected_account' )->willReturn( false );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_accounts_step_and_merchant_center_not_connected() {
		$this->mock_onboarding_started_via_google();
		$this->mock_accounts_setup_step();
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->ads_service->method( 'connected_account' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->merchant_account_state->method( 'last_incomplete_step' )->willReturn( 'set_id' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_accounts_step_and_merchant_center_not_connected_for_service_based_merchant() {
		$this->mock_onboarding_started_via_google();
		$this->mock_accounts_setup_step();
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->ads_service->method( 'connected_account' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_product_listings_step() {
		$this->mock_onboarding_started_via_google();
		$this->merchant_center->method( 'get_setup_status' )->willReturn(
			[
				'status' => 'incomplete',
				'step'   => 'product_listings',
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_setup_complete() {
		$this->mock_onboarding_started_via_google();
		$this->merchant_center->method( 'get_setup_status' )->willReturn( [ 'status' => 'complete' ] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_paid_ads_step() {
		$this->mock_onboarding_started_via_google();
		$this->merchant_center->method( 'get_setup_status' )->willReturn(
			[
				'status' => 'incomplete',
				'step'   => 'paid_ads',
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_onboarding_step_started() {
		$this->mock_accounts_setup_step();
		$this->options->method( 'get' )->willReturn( false );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->options->method( 'get_ads_id' )->willReturn( 0 );
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn( [] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_merchant_account_step_completed() {
		$this->mock_accounts_setup_step();
		$this->options->method( 'get' )->willReturn( false );
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );
		$this->options->method( 'get_ads_id' )->willReturn( 0 );
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn(
			[
				'set_id' => [
					'status'  => AccountState::STEP_DONE,
					'message' => '',
					'data'    => [],
				],
			]
		);
		$this->merchant_center->method( 'is_google_connected' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_accounts_step_and_all_accounts_connected() {
		$this->mock_onboarding_started_via_google();
		$this->mock_accounts_setup_step();
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->ads_service->method( 'connected_account' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->options->method( 'get_merchant_id' )->willReturn( 1234 );
		$this->merchant_account_state->method( 'last_incomplete_step' )->willReturn( '' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * Mock onboarding started via accepted TOS.
	 */
	private function mock_onboarding_started_via_tos(): void {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WP_TOS_ACCEPTED, false, true ],
			]
		);
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn( [] );
	}

	/**
	 * Mock onboarding started via Google connection.
	 */
	private function mock_onboarding_started_via_google(): void {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, true ],
				[ OptionsInterface::WP_TOS_ACCEPTED, false, false ],
			]
		);
		$this->merchant_account_state->method( 'get' )->with( false )->willReturn( [] );
	}

	/**
	 * Mock the mc/setup accounts step.
	 */
	private function mock_accounts_setup_step(): void {
		$this->merchant_center->method( 'get_setup_status' )->willReturn(
			[
				'status' => 'incomplete',
				'step'   => 'accounts',
			]
		);
	}
}
