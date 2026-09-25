<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SalesNotGrowingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SalesNotGrowingEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SalesNotGrowingEvaluatorTest extends UnitTest {

	/** @var SalesNotGrowingEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->evaluator = new SalesNotGrowingEvaluator();
	}

	public function test_get_id() {
		$this->assertEquals( 'sales-not-growing', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SALES_NOT_GROWING, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::SALES_NOT_GROWING, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_current_gmv_is_less_than_prior_year_gmv() {
		$evaluator = $this->create_evaluator( $this->years_ago( 2 ), 100.0, 250.0 );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_current_gmv_is_greater_than_prior_year_gmv() {
		$evaluator = $this->create_evaluator( $this->years_ago( 2 ), 300.0, 250.0 );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_less_than_one_year_of_sales() {
		$evaluator = $this->create_evaluator( $this->months_ago( 6 ), 100.0, 250.0 );

		$evaluator->expects( $this->never() )->method( 'get_gmv_for_period' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_there_are_no_sales() {
		$evaluator = $this->create_evaluator( null, 100.0, 250.0 );

		$evaluator->expects( $this->never() )->method( 'get_gmv_for_period' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator( $this->years_ago( 2 ), 100.0, 250.0 );
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'sales-not-growing' ), 0, HOUR_IN_SECONDS );

		$evaluator->expects( $this->never() )->method( 'get_first_order_date' );
		$evaluator->expects( $this->never() )->method( 'get_gmv_for_period' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_get_first_order_date_counts_paid_non_completed_order() {
		$this->create_order( 'processing', 20 );

		$this->assertNotNull( $this->invoke_get_first_order_date() );
	}

	public function test_get_first_order_date_ignores_unpaid_orders() {
		$this->create_order( 'pending', 99 );
		$this->create_order( 'failed', 99 );

		$this->assertNull( $this->invoke_get_first_order_date() );
	}

	public function test_get_gmv_for_period_sums_paid_orders_and_excludes_unpaid() {
		$this->create_order( 'completed', 20 );
		$this->create_order( 'processing', 30 );
		$this->create_order( 'pending', 99 );

		$start = ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->modify( '-1 month' );
		$end   = ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->modify( '+1 day' );

		$this->assertSame( 50.0, $this->invoke_get_gmv_for_period( $start, $end ) );
	}

	/**
	 * Create a test evaluator with a stubbed earliest-order date and GMV values.
	 *
	 * @param DateTime|null $first_order_date Earliest paid order date, or null when there are no sales.
	 * @param float         $current_gmv      GMV returned for the current month period.
	 * @param float         $prior_gmv        GMV returned for the prior-year period.
	 *
	 * @return SalesNotGrowingEvaluator|MockObject
	 */
	private function create_evaluator( ?DateTime $first_order_date, float $current_gmv, float $prior_gmv ): SalesNotGrowingEvaluator {
		$evaluator = $this->getMockBuilder( SalesNotGrowingEvaluator::class )
			->onlyMethods( [ 'get_first_order_date', 'get_gmv_for_period' ] )
			->getMock();

		$evaluator->method( 'get_first_order_date' )->willReturn( $first_order_date );

		$evaluator->method( 'get_gmv_for_period' )
			->willReturnCallback(
				function ( DateTime $start ) use ( $current_gmv, $prior_gmv ) {
					$timezone      = wp_timezone();
					$now           = new DateTime( 'now', $timezone );
					$current_start = new DateTime( $now->format( 'Y-m-01 00:00:00' ), $timezone );

					if ( $start->format( 'Y-m-d' ) === $current_start->format( 'Y-m-d' ) ) {
						return $current_gmv;
					}

					return $prior_gmv;
				}
			);

		return $evaluator;
	}

	/**
	 * Build a UTC DateTime a number of years in the past.
	 *
	 * @param int $years
	 *
	 * @return DateTime
	 */
	private function years_ago( int $years ): DateTime {
		return ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->modify( "-{$years} year" );
	}

	/**
	 * Build a UTC DateTime a number of months in the past.
	 *
	 * @param int $months
	 *
	 * @return DateTime
	 */
	private function months_ago( int $months ): DateTime {
		return ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->modify( "-{$months} month" );
	}

	/**
	 * Invoke the protected get_first_order_date() method on the real evaluator.
	 *
	 * @return DateTime|null
	 */
	private function invoke_get_first_order_date(): ?DateTime {
		$method = new \ReflectionMethod( $this->evaluator, 'get_first_order_date' );
		$method->setAccessible( true );

		return $method->invoke( $this->evaluator );
	}

	/**
	 * Invoke the protected get_gmv_for_period() method on the real evaluator.
	 *
	 * @param DateTime $start
	 * @param DateTime $end
	 *
	 * @return float
	 */
	private function invoke_get_gmv_for_period( DateTime $start, DateTime $end ): float {
		$method = new \ReflectionMethod( $this->evaluator, 'get_gmv_for_period' );
		$method->setAccessible( true );

		return $method->invoke( $this->evaluator, $start, $end );
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
