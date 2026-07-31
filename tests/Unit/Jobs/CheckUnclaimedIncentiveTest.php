<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CheckUnclaimedIncentive;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class CheckUnclaimedIncentiveTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class CheckUnclaimedIncentiveTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|AdsIncentives $ads_incentives */
	protected $ads_incentives;

	/** @var MockObject|Middleware $middleware */
	protected $middleware;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var CheckUnclaimedIncentive $job */
	protected $job;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->ads_incentives   = $this->createMock( AdsIncentives::class );
		$this->middleware       = $this->createMock( Middleware::class );
		$this->options          = $this->createMock( OptionsInterface::class );

		$this->job = new CheckUnclaimedIncentive(
			$this->action_scheduler,
			$this->monitor,
			$this->ads_incentives,
			$this->middleware
		);

		$this->job->set_options_object( $this->options );
	}

	public function test_get_name_and_start_hook() {
		$this->assertEquals( 'check_unclaimed_incentive', $this->job->get_name() );
		$this->assertEquals(
			'gla/jobs/check_unclaimed_incentive/start',
			$this->job->get_start_hook()->get_hook()
		);
	}

	public function test_schedule_queues_process_item() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( 'gla/jobs/check_unclaimed_incentive/process_item' );

		$this->job->schedule();
	}

	public function test_process_items_does_nothing_when_error_flag_not_set() {
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR )
			->willReturn( null );

		$this->ads_incentives->expects( $this->never() )->method( 'fetch_incentives' );
		$this->middleware->expects( $this->never() )->method( 'get_incentive_credits' );
		$this->options->expects( $this->never() )->method( 'update' );
		$this->options->expects( $this->never() )->method( 'delete' );

		$this->invoke_process_items();
	}

	public function test_process_items_clears_flag_and_marks_no_unclaimed_when_no_incentives_available() {
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR )
			->willReturn( 'error' );

		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn(
				[
					'type'                  => 'CYO_INCENTIVE',
					'termsAndConditionsUrl' => '',
					'incentives'            => [],
				]
			);

		$this->middleware->expects( $this->never() )->method( 'get_incentive_credits' );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE, false );

		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR );

		$this->invoke_process_items();
	}

	public function test_process_items_clears_flag_and_marks_no_unclaimed_when_credits_already_applied() {
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR )
			->willReturn( 'error' );

		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn(
				[
					'type'                  => 'CYO_INCENTIVE',
					'termsAndConditionsUrl' => '',
					'incentives'            => [
						[
							'id'    => '1',
							'offer' => 'low',
						],
					],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'get_incentive_credits' )
			->willReturn( [ [ 'amount' => '100' ] ] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE, false );

		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR );

		$this->invoke_process_items();
	}

	public function test_process_items_marks_unclaimed_when_incentives_available_and_no_credits_applied() {
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR )
			->willReturn( 'error' );

		$this->ads_incentives->expects( $this->once() )
			->method( 'fetch_incentives' )
			->willReturn(
				[
					'type'                  => 'CYO_INCENTIVE',
					'termsAndConditionsUrl' => '',
					'incentives'            => [
						[
							'id'    => '1',
							'offer' => 'low',
						],
					],
				]
			);

		$this->middleware->expects( $this->once() )
			->method( 'get_incentive_credits' )
			->willReturn( [] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE, true );

		$this->options->expects( $this->never() )->method( 'delete' );

		$this->invoke_process_items();
	}

	/**
	 * Invoke the protected process_items() method.
	 */
	protected function invoke_process_items(): void {
		$method = new ReflectionMethod( CheckUnclaimedIncentive::class, 'process_items' );
		$method->setAccessible( true );
		$method->invoke( $this->job, [] );
	}
}
