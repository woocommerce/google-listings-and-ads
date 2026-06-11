<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ReadyButNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReadyButNoSalesEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class ReadyButNoSalesEvaluatorTest extends UnitTest {

	/** @var MockObject|PolicyComplianceCheck $policy_compliance_check */
	protected $policy_compliance_check;

	/** @var MockObject|WC $wc */
	protected $wc;

	/** @var ReadyButNoSalesEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->policy_compliance_check = $this->createMock( PolicyComplianceCheck::class );
		$this->wc                      = $this->createMock( WC::class );
		$this->evaluator               = new ReadyButNoSalesEvaluator( $this->policy_compliance_check, $this->wc );
	}

	public function test_get_id() {
		$this->assertEquals( 'ready-but-no-sales', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::READY_BUT_NO_SALES, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::READY_BUT_NO_SALES, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_store_is_ready_and_has_no_completed_orders() {
		$evaluator = $this->create_evaluator_with_order_count( 0 );

		$this->policy_compliance_check->method( 'has_payment_gateways' )->willReturn( true );
		$this->wc->method( 'get_shipping_zones' )->willReturn( [ [ 'zone_id' => 1 ] ] );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_payment_gateways_are_missing() {
		$evaluator = $this->create_evaluator_with_order_count( 0 );

		$this->policy_compliance_check->method( 'has_payment_gateways' )->willReturn( false );
		$this->wc->expects( $this->never() )->method( 'get_shipping_zones' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_shipping_zones_are_missing() {
		$evaluator = $this->create_evaluator_with_order_count( 0 );

		$this->policy_compliance_check->method( 'has_payment_gateways' )->willReturn( true );
		$this->wc->method( 'get_shipping_zones' )->willReturn( [] );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_completed_orders_exist() {
		$evaluator = $this->create_evaluator_with_order_count( 1 );

		$this->policy_compliance_check->method( 'has_payment_gateways' )->willReturn( true );
		$this->wc->method( 'get_shipping_zones' )->willReturn( [ [ 'zone_id' => 1 ] ] );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator_with_order_count( 0 );
		$user_id   = $this->login_as_administrator();

		set_transient( 'gla_notif_ready-but-no-sales_' . $user_id, 1, HOUR_IN_SECONDS );

		$this->policy_compliance_check->expects( $this->never() )->method( 'has_payment_gateways' );
		$this->wc->expects( $this->never() )->method( 'get_shipping_zones' );

		$this->assertTrue( $evaluator->should_show() );
		$this->assertFalse( $evaluator->query_called );
	}

	/**
	 * Create a test evaluator with a stubbed completed order count.
	 *
	 * @param int $order_count
	 *
	 * @return ReadyButNoSalesEvaluator&object{query_called:bool}
	 */
	private function create_evaluator_with_order_count( int $order_count ): ReadyButNoSalesEvaluator {
		return new class( $this->policy_compliance_check, $this->wc, $order_count ) extends ReadyButNoSalesEvaluator {
			/** @var bool */
			public $query_called = false;

			/** @var int */
			private $order_count;

			/**
			 * @param PolicyComplianceCheck $policy_compliance_check
			 * @param WC                    $wc
			 * @param int                   $order_count
			 */
			public function __construct( PolicyComplianceCheck $policy_compliance_check, WC $wc, int $order_count ) {
				parent::__construct( $policy_compliance_check, $wc );
				$this->order_count = $order_count;
			}

			protected function get_completed_order_count(): int {
				$this->query_called = true;

				return $this->order_count;
			}
		};
	}
}
