<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\PaidOrdersEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class PaidOrdersEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class PaidOrdersEvaluatorTest extends UnitTest {

	/** @var PaidOrdersEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->evaluator = new PaidOrdersEvaluator();
	}

	public function test_get_id() {
		$this->assertEquals( 'paid-orders', $this->evaluator->get_id() );
	}

	public function test_implements_site_scoped_notification_interface() {
		$this->assertInstanceOf( SiteScopedNotificationEvaluatorInterface::class, $this->evaluator );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::PAID_ORDERS, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::PAID_ORDERS, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_ten_or_more_revenue_orders() {
		$evaluator = $this->create_evaluator_with_revenue_order_threshold( true );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_fewer_than_ten_revenue_orders() {
		$evaluator = $this->create_evaluator_with_revenue_order_threshold( false );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator_with_revenue_order_threshold( false );

		set_transient( NotificationCacheKeys::for_site( 'paid-orders' ), 1, HOUR_IN_SECONDS );

		$evaluator->expects( $this->never() )->method( 'has_minimum_revenue_orders' );

		$this->assertTrue( $evaluator->should_show() );
	}

	/**
	 * Integration: exercises the real order query and confirms only paid orders that
	 * generated revenue are counted (zero-total and unpaid orders are excluded).
	 *
	 * Runs against the post-based (legacy) order store, which is what the unit-test
	 * environment uses. The HPOS branch cannot be exercised here because the WP test
	 * suite's transactional temporary tables break HPOS order reads; it is verified
	 * separately against a real orders-table store.
	 */
	public function test_has_minimum_revenue_orders_excludes_zero_total_and_unpaid_orders() {
		// Paid orders that generated revenue.
		$this->create_order( 'completed', 50 );
		$this->create_order( 'processing', 20 );
		$this->create_order( 'completed', 10 );

		// Excluded: zero-total paid orders and unpaid orders.
		$this->create_order( 'completed', 0 );
		$this->create_order( 'pending', 99 );
		$this->create_order( 'failed', 99 );

		$this->assertTrue( $this->invoke_has_minimum_revenue_orders( 3 ) );
		$this->assertFalse( $this->invoke_has_minimum_revenue_orders( 4 ) );
	}

	/**
	 * Create a test evaluator with a stubbed revenue-order threshold result.
	 *
	 * @param bool $meets_threshold
	 *
	 * @return PaidOrdersEvaluator|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_evaluator_with_revenue_order_threshold( bool $meets_threshold ): PaidOrdersEvaluator {
		$evaluator = $this->getMockBuilder( PaidOrdersEvaluator::class )
			->onlyMethods( [ 'has_minimum_revenue_orders' ] )
			->getMock();

		$evaluator->method( 'has_minimum_revenue_orders' )->willReturn( $meets_threshold );

		return $evaluator;
	}

	/**
	 * Invoke the protected has_minimum_revenue_orders() method.
	 *
	 * @param int $minimum
	 *
	 * @return bool
	 */
	private function invoke_has_minimum_revenue_orders( int $minimum ): bool {
		$method = new \ReflectionMethod( $this->evaluator, 'has_minimum_revenue_orders' );
		$method->setAccessible( true );

		return $method->invoke( $this->evaluator, $minimum );
	}

	/**
	 * Create a WooCommerce order with the given status and total.
	 *
	 * @param string $status
	 * @param float  $total
	 */
	private function create_order( string $status, float $total ): void {
		$order = wc_create_order();
		$order->set_status( $status );
		$order->set_total( $total );
		$order->save();
	}
}
