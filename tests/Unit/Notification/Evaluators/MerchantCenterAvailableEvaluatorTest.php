<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\MerchantCenterAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterAvailableEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class MerchantCenterAvailableEvaluatorTest extends UnitTest {

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MerchantCenterAvailableEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->merchant_center              = $this->createMock( MerchantCenterService::class );

		$this->evaluator = new MerchantCenterAvailableEvaluator( $this->service_based_merchant_state, $this->merchant_center );
	}

	public function test_get_id() {
		$this->assertEquals( 'merchant-center-available', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::MERCHANT_CENTER_AVAILABLE, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_permanent() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_switch_recorded_and_merchant_center_not_connected() {
		$this->service_based_merchant_state->method( 'has_switched_to_physical_products' )->willReturn( true );
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_switch_recorded() {
		$this->service_based_merchant_state->method( 'has_switched_to_physical_products' )->willReturn( false );
		$this->merchant_center->expects( $this->never() )->method( 'is_connected' );
		$this->service_based_merchant_state->expects( $this->never() )->method( 'is_service_based_merchant' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_merchant_center_connected() {
		$this->service_based_merchant_state->method( 'has_switched_to_physical_products' )->willReturn( true );
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->service_based_merchant_state->expects( $this->never() )->method( 'is_service_based_merchant' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_store_is_service_based_again() {
		$this->service_based_merchant_state->method( 'has_switched_to_physical_products' )->willReturn( true );
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
