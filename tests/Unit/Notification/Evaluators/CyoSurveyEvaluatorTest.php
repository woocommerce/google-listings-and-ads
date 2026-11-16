<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbstractCyoSurveyEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CyoSurveyNoSpendEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CyoSurveySpendEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class CyoSurveyEvaluatorTest
 *
 * Covers AbstractCyoSurveyEvaluator through its two concrete evaluators, with the clock pinned.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class CyoSurveyEvaluatorTest extends UnitTest {

	private const CACHE_KEY = 'gla_notif_cyo-survey';

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var MockObject|AdsReport $ads_report */
	protected $ads_report;

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->onboarding_completed = $this->createMock( OnboardingCompleted::class );
		$this->ads_report           = $this->createMock( AdsReport::class );
		$this->ads_service          = $this->createMock( AdsService::class );
		$this->options              = $this->createMock( OptionsInterface::class );

		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Build the spend evaluator with a pinned clock.
	 *
	 * @param int $now Pinned "now" timestamp.
	 *
	 * @return CyoSurveySpendEvaluator
	 */
	private function spend_evaluator( int $now ): CyoSurveySpendEvaluator {
		return $this->pinned( CyoSurveySpendEvaluator::class, $now );
	}

	/**
	 * Build the no-spend evaluator with a pinned clock.
	 *
	 * @param int $now Pinned "now" timestamp.
	 *
	 * @return CyoSurveyNoSpendEvaluator
	 */
	private function no_spend_evaluator( int $now ): CyoSurveyNoSpendEvaluator {
		return $this->pinned( CyoSurveyNoSpendEvaluator::class, $now );
	}

	/**
	 * Build an evaluator with the shared dependencies injected and get_now() pinned.
	 *
	 * @param string $class_name Concrete evaluator class.
	 * @param int    $now        Pinned "now" timestamp.
	 *
	 * @return MockObject|AbstractCyoSurveyEvaluator
	 */
	private function pinned( string $class_name, int $now ): AbstractCyoSurveyEvaluator {
		$evaluator = $this->getMockBuilder( $class_name )
			->setConstructorArgs( [ $this->onboarding_completed, $this->ads_report ] )
			->onlyMethods( [ 'get_now' ] )
			->getMock();
		$evaluator->method( 'get_now' )->willReturn( $now );
		$evaluator->set_ads_object( $this->ads_service );
		$evaluator->set_options_object( $this->options );

		return $evaluator;
	}

	/**
	 * Mock a completed onboarding with the given stored completion value.
	 *
	 * @param mixed $completed_at Value stored in the onboarding_completed_at option.
	 */
	private function mock_onboarding( $completed_at ): void {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->options->method( 'get' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT )
			->willReturn( $completed_at );
	}

	/**
	 * Parse a UTC ISO 8601 string into a timestamp.
	 *
	 * @param string $iso
	 *
	 * @return int
	 */
	private static function ts( string $iso ): int {
		return strtotime( $iso );
	}

	public function test_get_id() {
		$this->assertSame( 'cyo-survey-spend', $this->spend_evaluator( 0 )->get_id() );
		$this->assertSame( 'cyo-survey-no-spend', $this->no_spend_evaluator( 0 )->get_id() );
	}

	public function test_get_priority() {
		$this->assertSame( 160, NotificationPriorities::CYO_SURVEY );
		$this->assertSame( NotificationPriorities::CYO_SURVEY, $this->spend_evaluator( 0 )->get_priority() );
		$this->assertSame( NotificationPriorities::CYO_SURVEY, $this->no_spend_evaluator( 0 )->get_priority() );
	}

	public function test_get_snooze_duration_is_permanent() {
		$this->assertNull( $this->spend_evaluator( 0 )->get_snooze_duration() );
		$this->assertNull( $this->no_spend_evaluator( 0 )->get_snooze_duration() );
	}

	public function test_both_are_site_scoped() {
		$this->assertInstanceOf( SiteScopedNotificationEvaluatorInterface::class, $this->spend_evaluator( 0 ) );
		$this->assertInstanceOf( SiteScopedNotificationEvaluatorInterface::class, $this->no_spend_evaluator( 0 ) );
	}

	/**
	 * AE1: eligible store without Ads setup sees the short survey, and no report is requested.
	 */
	public function test_no_spend_shows_when_ads_not_set_up() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->ads_report->expects( $this->never() )->method( 'get_report_data' );

		$this->assertTrue( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	/**
	 * AE2: onboarding one second before the CYO launch is ineligible.
	 */
	public function test_not_shown_when_onboarded_before_launch() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-05-18T23:59:59Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	public function test_shown_when_onboarded_exactly_at_launch() {
		$completed = self::ts( '2026-05-19T00:00:00Z' );
		$this->mock_onboarding( $completed );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertTrue( $this->no_spend_evaluator( $completed + 60 * DAY_IN_SECONDS )->should_show() );
	}

	/**
	 * AE3: 46 days after onboarding nothing shows; at exactly 60 days the matching survey shows.
	 */
	public function test_requires_sixty_days_since_onboarding() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->options->method( 'get' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT )
			->willReturnOnConsecutiveCalls(
				$now - 46 * DAY_IN_SECONDS,
				$now - 46 * DAY_IN_SECONDS,
				$now - 60 * DAY_IN_SECONDS,
				$now - 60 * DAY_IN_SECONDS
			);
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );

		$this->assertTrue( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	/**
	 * AE5: from 2027-01-16 UTC nothing shows, and no report is requested.
	 */
	public function test_not_shown_on_or_after_cutoff() {
		$now = self::ts( '2027-01-16T00:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_report->expects( $this->never() )->method( 'get_report_data' );

		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	public function test_shown_one_second_before_cutoff() {
		$now = self::ts( '2027-01-15T23:59:59Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertTrue( $this->no_spend_evaluator( $now )->should_show() );
	}

	public function test_not_shown_when_onboarding_incomplete() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->options->method( 'get' )->willReturn( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	/**
	 * @dataProvider invalid_completed_at_provider
	 *
	 * @param mixed $completed_at
	 */
	public function test_not_shown_when_completed_at_is_not_a_positive_timestamp( $completed_at ) {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( $completed_at );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	/**
	 * @return array
	 */
	public function invalid_completed_at_provider(): array {
		return [
			'zero'        => [ 0 ],
			'false'       => [ false ],
			'non-numeric' => [ 'yes' ],
		];
	}

	/**
	 * AE4: spend since onboarding shows the long survey; the report covers onboarding date to today.
	 */
	public function test_spend_shows_when_report_has_spend() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T08:30:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_report->expects( $this->once() )
			->method( 'get_report_data' )
			->with(
				'campaigns',
				$this->callback(
					function ( array $args ): bool {
						return in_array( 'spend', $args['fields'] ?? [], true )
							&& '2026-06-01' === $args['after']
							&& '2026-11-16' === $args['before'];
					}
				)
			)
			->willReturn( [ 'totals' => [ 'spend' => 12.4 ] ] );

		$this->assertTrue( $this->spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
	}

	/**
	 * @dataProvider no_spend_report_provider
	 *
	 * @param array $report
	 */
	public function test_no_spend_shows_when_report_has_no_spend( array $report ) {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_report->method( 'get_report_data' )->willReturn( $report );

		$this->assertTrue( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	/**
	 * @return array
	 */
	public function no_spend_report_provider(): array {
		return [
			'zero spend'    => [ [ 'totals' => [ 'spend' => 0 ] ] ],
			'missing spend' => [ [] ],
		];
	}

	/**
	 * AE6: a failed report hides both surveys, caches "unknown" for 15 minutes, and is not retried
	 * inside that window by either evaluator.
	 *
	 * @dataProvider report_failure_provider
	 *
	 * @param Throwable $failure
	 */
	public function test_report_failure_hides_both_and_caches_unknown( Throwable $failure ) {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_report->expects( $this->once() )->method( 'get_report_data' )->willThrowException( $failure );

		$written = [];
		add_action(
			'set_transient_' . self::CACHE_KEY,
			function ( $value, $expiration ) use ( &$written ) {
				$written[] = [ $value, $expiration ];
			},
			10,
			2
		);

		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
		$this->assertSame( [ [ 'unknown', 15 * MINUTE_IN_SECONDS ] ], $written );

		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );
	}

	/**
	 * @return array
	 */
	public function report_failure_provider(): array {
		return [
			'response exception' => [ new ExceptionWithResponseData( 'Unable to retrieve report data', 500 ) ],
			'other throwable'    => [ new RuntimeException( 'boom' ) ],
		];
	}

	public function test_spend_status_is_cached_for_an_hour() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_report->method( 'get_report_data' )->willReturn( [ 'totals' => [ 'spend' => 3.5 ] ] );

		$written = [];
		add_action(
			'set_transient_' . self::CACHE_KEY,
			function ( $value, $expiration ) use ( &$written ) {
				$written[] = [ $value, $expiration ];
			},
			10,
			2
		);

		$this->spend_evaluator( $now )->should_show();

		$this->assertSame( [ [ 'spent', HOUR_IN_SECONDS ] ], $written );
	}

	public function test_report_is_queried_again_after_unknown_expires() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );

		$calls = 0;
		$this->ads_report->expects( $this->exactly( 2 ) )
			->method( 'get_report_data' )
			->willReturnCallback(
				function () use ( &$calls ) {
					++$calls;
					if ( 1 === $calls ) {
						throw new ExceptionWithResponseData( 'Unable to retrieve report data', 500 );
					}
					return [ 'totals' => [ 'spend' => 12.4 ] ];
				}
			);

		$this->assertFalse( $this->spend_evaluator( $now )->should_show() );

		// Simulate the 15-minute "unknown" entry expiring.
		delete_transient( NotificationCacheKeys::for_site( 'cyo-survey' ) );

		$this->assertTrue( $this->spend_evaluator( $now )->should_show() );
	}

	public function test_both_evaluators_answer_from_shared_cache() {
		$now = self::ts( '2026-11-16T12:00:00Z' );
		$this->mock_onboarding( self::ts( '2026-06-01T00:00:00Z' ) );
		set_transient( NotificationCacheKeys::for_site( 'cyo-survey' ), 'spent', HOUR_IN_SECONDS );
		$this->ads_service->expects( $this->never() )->method( 'is_setup_complete' );
		$this->ads_report->expects( $this->never() )->method( 'get_report_data' );

		$this->assertTrue( $this->spend_evaluator( $now )->should_show() );
		$this->assertFalse( $this->no_spend_evaluator( $now )->should_show() );
	}
}
