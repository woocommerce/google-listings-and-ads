<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SalesNotGrowingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SalesNotGrowingEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SalesNotGrowingEvaluatorTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var SalesNotGrowingEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options   = $this->createMock( OptionsInterface::class );
		$this->evaluator = new SalesNotGrowingEvaluator();
		$this->evaluator->set_options_object( $this->options );
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
		$evaluator = $this->create_evaluator_with_gmv( 100.0, 250.0 );
		$evaluator->set_options_object( $this->options );

		$this->options->method( 'get' )
			->with( OptionsInterface::INSTALL_TIMESTAMP )
			->willReturn( time() - ( 2 * YEAR_IN_SECONDS ) );

		$this->assertTrue( $evaluator->should_show() );
	}

	public function test_should_not_show_when_current_gmv_is_greater_than_prior_year_gmv() {
		$evaluator = $this->create_evaluator_with_gmv( 300.0, 250.0 );
		$evaluator->set_options_object( $this->options );

		$this->options->method( 'get' )
			->with( OptionsInterface::INSTALL_TIMESTAMP )
			->willReturn( time() - ( 2 * YEAR_IN_SECONDS ) );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_should_not_show_when_installed_less_than_one_year() {
		$evaluator = $this->create_evaluator_with_gmv( 100.0, 250.0 );
		$evaluator->set_options_object( $this->options );

		$this->options->method( 'get' )
			->with( OptionsInterface::INSTALL_TIMESTAMP )
			->willReturn( time() - ( 6 * MONTH_IN_SECONDS ) );

		$evaluator->expects( $this->never() )->method( 'get_gmv_for_period' );

		$this->assertFalse( $evaluator->should_show() );
	}

	public function test_cache_hit_skips_query() {
		$evaluator = $this->create_evaluator_with_gmv( 100.0, 250.0 );
		$evaluator->set_options_object( $this->options );
		$user_id = $this->login_as_administrator();

		set_transient( 'gla_notif_sales-not-growing_' . $user_id, 0, HOUR_IN_SECONDS );

		$this->options->expects( $this->never() )->method( 'get' );
		$evaluator->expects( $this->never() )->method( 'get_gmv_for_period' );

		$this->assertFalse( $evaluator->should_show() );
	}

	/**
	 * Create a test evaluator with stubbed GMV values.
	 *
	 * @param float $current_gmv
	 * @param float $prior_gmv
	 *
	 * @return SalesNotGrowingEvaluator|MockObject
	 */
	private function create_evaluator_with_gmv( float $current_gmv, float $prior_gmv ): SalesNotGrowingEvaluator {
		$evaluator = $this->getMockBuilder( SalesNotGrowingEvaluator::class )
			->onlyMethods( [ 'get_gmv_for_period' ] )
			->getMock();

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
}
