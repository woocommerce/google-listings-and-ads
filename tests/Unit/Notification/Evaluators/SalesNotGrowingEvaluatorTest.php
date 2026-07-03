<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SalesNotGrowingEvaluator;
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
		$user_id   = $this->login_as_administrator();

		set_transient( 'gla_notif_sales-not-growing_' . $user_id, 0, HOUR_IN_SECONDS );

		$evaluator->expects( $this->never() )->method( 'get_first_order_date' );
		$evaluator->expects( $this->never() )->method( 'get_gmv_for_period' );

		$this->assertFalse( $evaluator->should_show() );
	}

	/**
	 * Create a test evaluator with a stubbed earliest-order date and GMV values.
	 *
	 * @param DateTime|null $first_order_date Earliest completed order date, or null when there are no sales.
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
}
