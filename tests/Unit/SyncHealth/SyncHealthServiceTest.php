<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\SyncHealth;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\JetpackAuthCircuitBreaker;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\SyncHealthService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use wpdb;

/**
 * Class SyncHealthServiceTest
 *
 * The queue and product meta reads are stubbed with raw job counts; each job's
 * state, the pillars, reasons, `syncing` and `state` are computed by the real code.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\SyncHealth
 */
class SyncHealthServiceTest extends UnitTest {

	/** @var MockObject|JetpackAuthCircuitBreaker $circuit_breaker */
	protected $circuit_breaker;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var MockObject|UpdateMerchantProductStatuses $review_job */
	protected $review_job;

	/** @var MockObject|SyncHealthService $service */
	protected $service;

	/** @var array Stubbed state, read by the mocks when called. */
	protected $state = [];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->state = [
			'google_connected'  => true,
			'jetpack_connected' => true,
			'transients'        => [ TransientsInterface::URL_MATCHES => 'yes' ],
			'options'           => [],
			'jobs'              => [],
			// Last completed action time per job name ('' for any job).
			'completed'         => [],
			'pending_products'  => 0,
			'invalid_products'  => 0,
		];

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->method( 'is_google_connected' )->willReturnCallback( fn() => $this->state['google_connected'] );
		$merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$merchant_center->method( 'is_jetpack_owner_connected' )->willReturnCallback( fn() => $this->state['jetpack_connected'] );
		$merchant_center->method( 'is_connected' )->willReturnCallback( fn() => $this->state['google_connected'] );

		$this->circuit_breaker = $this->createMock( JetpackAuthCircuitBreaker::class );

		$market_service = $this->createMock( MarketService::class );
		$market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->transients = $this->createMock( TransientsInterface::class );
		$this->transients->method( 'get' )->willReturnCallback( fn( $name ) => $this->state['transients'][ $name ] ?? null );

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->willReturnCallback( fn( $name ) => $this->state['options'][ $name ] ?? null );

		$this->merchant_statuses = $this->createMock( MerchantStatuses::class );
		$this->review_job        = $this->createMock( UpdateMerchantProductStatuses::class );
		$job_repository          = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )->with( UpdateMerchantProductStatuses::class )->willReturn( $this->review_job );

		$this->service = $this->getMockBuilder( SyncHealthService::class )
			->setConstructorArgs(
				[
					$merchant_center,
					$this->circuit_breaker,
					$market_service,
					$this->transients,
					$this->createMock( wpdb::class ),
					$this->merchant_statuses,
					$job_repository,
				]
			)
			->onlyMethods( [ 'get_jobs_by_name', 'get_sync_status_counts', 'get_error_split', 'get_backoff_count', 'get_overdue_resubmit_count', 'query_last_completed_at' ] )
			->getMock();
		$this->service->method( 'query_last_completed_at' )->willReturnCallback(
			function ( $job_name = null, $since = null, $before = null ) {
				$this->state['completed_queries'][ $job_name ?? '' ] = [ $since, $before ];
				return $this->state['completed'][ $job_name ?? '' ] ?? null;
			}
		);
		$this->service->set_options_object( $options );

		$this->service->method( 'get_jobs_by_name' )->willReturnCallback(
			function () {
				// The real get_job_state() derives each job's state from its raw counts.
				$job_state = new ReflectionMethod( SyncHealthService::class, 'get_job_state' );
				// setAccessible() is required on PHP < 8.1 and a deprecated no-op from 8.1 onward.
				$job_state->setAccessible( true );

				$jobs = [];
				foreach ( $this->state['jobs'] as $name => $counts ) {
					$jobs[ $name ]          = $this->job( $name, $counts );
					$jobs[ $name ]['state'] = $job_state->invoke( $this->service, $jobs[ $name ], time() );
				}

				return $jobs;
			}
		);
		$this->service->method( 'get_sync_status_counts' )->willReturnCallback(
			function () {
				$counts = [ SyncStatus::PENDING => $this->state['pending_products'] ] + array_fill_keys( SyncStatus::ALLOWED_VALUES, 0 );

				return [
					'all'      => $counts,
					'products' => $counts,
				];
			}
		);
		$this->service->method( 'get_error_split' )->willReturnCallback(
			fn() => [
				'invalid'   => $this->state['invalid_products'],
				'transient' => 0,
			]
		);
	}

	public function test_synced_when_only_future_work_is_scheduled() {
		// The daily resubmit, scheduled for later, is not work in progress.
		$this->state['jobs'] = [ 'resubmit_expiring_products' => self::scheduled_later() ];

		$summary = $this->service->get_summary( true );

		$this->assertSame( SyncHealthService::STATE_SYNCED, $summary['state'] );
		$this->assertSame(
			[
				'products'  => 0,
				'full_sync' => false,
				'shipping'  => false,
			],
			$summary['syncing']
		);
	}

	/**
	 * @dataProvider syncing_cases
	 *
	 * @param array $jobs             Job overrides keyed by job name.
	 * @param int   $pending_products Products flagged as pending.
	 * @param array $expected         Expected `syncing` object.
	 */
	public function test_syncing_while_work_is_due_or_running( array $jobs, int $pending_products, array $expected ) {
		$this->state['jobs']             = $jobs;
		$this->state['pending_products'] = $pending_products;

		$summary = $this->service->get_summary( true );

		$this->assertSame( SyncHealthService::STATE_SYNCING, $summary['state'] );
		$this->assertSame( $expected, $summary['syncing'] );
	}

	/**
	 * @return array
	 */
	public function syncing_cases(): array {
		return [
			'pushes count pending products'  => [
				[ 'update_products' => self::running() ],
				3,
				[
					'products'  => 3,
					'full_sync' => false,
					'shipping'  => false,
				],
			],
			'resubmits count one per action' => [
				[ 'resubmit_expiring_products' => self::due( 2 ) ],
				0,
				[
					'products'  => 2,
					'full_sync' => false,
					'shipping'  => false,
				],
			],
			'deletions do not count stuck pending products' => [
				[ 'delete_products' => self::due( 2 ) ],
				50,
				[
					'products'  => 2,
					'full_sync' => false,
					'shipping'  => false,
				],
			],
			'full sync'                      => [
				[ 'update_all_products' => self::running() ],
				0,
				[
					'products'  => 0,
					'full_sync' => true,
					'shipping'  => false,
				],
			],
			'shipping'                       => [
				[ 'update_shipping_settings' => self::due( 1 ) ],
				0,
				[
					'products'  => 0,
					'full_sync' => false,
					'shipping'  => true,
				],
			],
		];
	}

	/**
	 * @dataProvider failure_cases
	 *
	 * @param array  $state         Overrides of the stubbed state.
	 * @param string $expected_code Reason code behind the failure.
	 */
	public function test_failed_when_data_is_not_reaching_google( array $state, string $expected_code ) {
		$breaker_open = ! empty( $state['breaker_open'] );
		unset( $state['breaker_open'] );

		$this->state = array_replace_recursive( $this->state, $state );
		$this->circuit_breaker->method( 'is_open' )->willReturn( $breaker_open );
		$this->circuit_breaker->method( 'get_retry_time' )->willReturn( time() + 60 );

		$summary = $this->service->get_summary( true );

		$this->assertSame( SyncHealthService::STATE_FAILED, $summary['state'] );
		$this->assertContains( $expected_code, array_column( $summary['reasons'], 'code' ) );
	}

	/**
	 * @return array
	 */
	public function failure_cases(): array {
		// A failure wins over work in progress.
		$full_sync_running = [ 'update_all_products' => self::running() ];

		return [
			'Google disconnected'        => [ [ 'google_connected' => false ], 'google_disconnected' ],
			'WordPress.com disconnected' => [ [ 'jetpack_connected' => false ], 'jetpack_disconnected' ],
			'auth circuit breaker'       => [
				[
					'breaker_open' => true,
					'jobs'         => $full_sync_running,
				],
				'auth_paused',
			],
			'claimed URL mismatch'       => [ [ 'transients' => [ TransientsInterface::URL_MATCHES => 'no' ] ], 'url_mismatch' ],
			'product job paused'         => [
				[ 'jobs' => [ 'update_products' => self::paused() ] + $full_sync_running ],
				'product_job_paused',
			],
			'product job stuck'          => [
				[ 'jobs' => [ 'update_products' => self::stuck() ] ],
				'product_job_stuck',
			],
			'full sync interrupted'      => [
				[ 'jobs' => [ 'update_all_products' => self::interrupted() ] ],
				'full_sync_interrupted',
			],
			'runner stalled'             => [
				[ 'jobs' => [ 'update_products' => self::stalled() ] ],
				'runner_stalled',
			],
			'shipping job paused'        => [
				[ 'jobs' => [ 'update_shipping_settings' => self::paused() ] ],
				'shipping_job_paused',
			],
			'shipping sync failed'       => [
				[
					'options' => [
						OptionsInterface::SHIPPING_SYNC_FAILURE => [
							'message'   => 'API rejected the settings.',
							'failed_at' => gmdate( 'Y-m-d H:i:s' ),
						],
					],
				],
				'shipping_failed',
			],
		];
	}

	public function test_review_results_product_problems_and_warnings_do_not_change_the_state() {
		// Review fetch running now, and the previous one failed.
		$this->state['jobs'] = [ 'update_merchant_product_statuses' => self::running() + self::due( 1 ) ];
		$this->state['transients'][ TransientsInterface::MC_STATUSES ] = [ 'error' => 'Google returned an error.' ];
		// Products refused by Google, stuck pending, and backing off.
		$this->state['invalid_products'] = 7;
		$this->state['pending_products'] = 4;
		$this->service->method( 'get_backoff_count' )->willReturn( 2 );

		$summary = $this->service->get_summary( true );

		$this->assertSame( SyncHealthService::STATE_SYNCED, $summary['state'] );
		$this->assertEqualsCanonicalizing(
			[ 'review_refresh_failed', 'products_stuck_pending', 'products_backoff' ],
			array_column( $summary['reasons'], 'code' )
		);
		$this->assertTrue( $summary['pillars']['review']['loading'] );
		$this->assertSame( 7, $summary['pillars']['products']['needs_fix'] );
	}

	public function test_dates_come_from_persisted_options() {
		$this->state['options'] = [
			OptionsInterface::MC_STATUSES_LAST_SUCCESS   => 1000,
			OptionsInterface::MC_STATUSES_LAST_ATTEMPT   => 2000,
			OptionsInterface::PRODUCT_SYNC_LAST_SUCCESS  => 3000,
			OptionsInterface::SHIPPING_SYNC_LAST_SUCCESS => 4000,
			OptionsInterface::UPDATE_ALL_PRODUCTS_LAST_SYNC => 5000,
		];
		// The review transient is cleared whenever a fetch starts.
		$this->state['transients'][ TransientsInterface::MC_STATUSES ] = null;

		$pillars = $this->service->get_summary( true )['pillars'];

		$this->assertSame( 1000, $pillars['review']['data_at'] );
		$this->assertSame( 2000 + 5 * MINUTE_IN_SECONDS, $pillars['review']['refresh_at'] );
		$this->assertSame( 3000, $pillars['products']['last_push_at'] );
		$this->assertSame( 5000, $pillars['products']['full_sync']['last_completed_at'] );
		$this->assertSame( 4000, $pillars['shipping']['last_success_at'] );
	}

	public function test_dates_fall_back_to_the_whole_job_history_of_a_store_that_never_disconnected() {
		// Right after upgrading: no option yet, and nothing ran in the last 24 hours.
		// Saving settings since the last push rewrote the setup time; that must not hide it.
		$this->state['options']   = [ OptionsInterface::MC_SETUP_COMPLETED_AT => 9000 ];
		$this->state['completed'] = [
			'update_products'                  => 6000,
			'update_merchant_product_statuses' => 7000,
			''                                 => 8000,
		];

		$summary = $this->service->get_summary( true );

		$this->assertSame( 6000, $summary['pillars']['products']['last_push_at'] );
		$this->assertSame( 7000, $summary['pillars']['review']['data_at'] );
		$this->assertSame( 8000, $summary['queue']['last_completed_at'] );
		$this->assertSame( [ 0, null ], $this->state['completed_queries']['update_products'] );
		$this->assertSame( [ 0, null ], $this->state['completed_queries']['update_merchant_product_statuses'] );
	}

	public function test_dates_fall_back_only_to_jobs_completed_since_the_last_disconnect() {
		$this->state['options'] = [
			OptionsInterface::MC_SETUP_COMPLETED_AT   => 9000,
			OptionsInterface::MC_LAST_DISCONNECTED_AT => 5000,
		];

		$this->service->get_summary( true );

		// Jobs that completed before belong to the previous account.
		$this->assertSame( [ 5000, null ], $this->state['completed_queries']['update_products'] );
		$this->assertSame( [ 5000, null ], $this->state['completed_queries']['update_merchant_product_statuses'] );
	}

	public function test_dates_do_not_fall_back_while_merchant_center_is_not_connected() {
		$this->state['google_connected'] = false;
		$this->state['completed']        = [
			'update_products'                  => 6000,
			'update_merchant_product_statuses' => 7000,
		];

		$pillars = $this->service->get_summary( true )['pillars'];

		$this->assertNull( $pillars['products']['last_push_at'] );
		$this->assertNull( $pillars['review']['data_at'] );
	}

	/**
	 * @dataProvider review_fetch_in_progress
	 *
	 * @param int|null $run_started_at Start of the running fetch, null when unknown.
	 * @param int|null $expected       Expected `review.data_at`.
	 */
	public function test_review_data_age_falls_back_to_the_fetch_before_the_running_one( ?int $run_started_at, ?int $expected ) {
		$this->state['options']   = [
			OptionsInterface::MC_SETUP_COMPLETED_AT      => 5000,
			OptionsInterface::MC_STATUSES_RUN_STARTED_AT => $run_started_at,
		];
		$this->state['completed'] = [ 'update_merchant_product_statuses' => 7000 ];
		$this->state['jobs']      = [ 'update_merchant_product_statuses' => self::running() ];

		$review = $this->service->get_summary( true )['pillars']['review'];

		$this->assertTrue( $review['loading'] );
		$this->assertSame( $expected, $review['data_at'] );
		if ( null !== $run_started_at ) {
			// Pages of the running fetch complete too; only earlier actions count.
			$this->assertSame( [ 0, $run_started_at ], $this->state['completed_queries']['update_merchant_product_statuses'] );
		}
	}

	/**
	 * @return array
	 */
	public function review_fetch_in_progress(): array {
		return [
			'run start known'   => [ 9000, 7000 ],
			'run start unknown' => [ null, null ],
		];
	}

	public function test_last_completed_actions_are_read_per_job_without_a_time_window() {
		global $wpdb;
		if ( ! isset( $wpdb->actionscheduler_actions, $wpdb->actionscheduler_groups ) ) {
			$this->markTestSkipped( 'Action Scheduler tables are not registered.' );
		}

		$wpdb->insert( $wpdb->actionscheduler_groups, [ 'slug' => 'gla' ] );
		$group = (int) $wpdb->insert_id;
		$add   = function ( string $hook, string $status, int $days_ago ) use ( $wpdb, $group ) {
			$at = gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS );
			$wpdb->insert(
				$wpdb->actionscheduler_actions,
				[
					'hook'                 => $hook,
					'status'               => $status,
					'scheduled_date_gmt'   => $at,
					'scheduled_date_local' => $at,
					'args'                 => '[]',
					'group_id'             => $group,
					'last_attempt_gmt'     => $at,
					'last_attempt_local'   => $at,
				]
			);
		};
		$add( 'gla/jobs/update_products/process_item', 'complete', 10 );
		$add( 'gla/jobs/update_products/process_item', 'failed', 1 );
		$add( 'gla/jobs/update_merchant_product_statuses/process_item', 'complete', 5 );

		$service = new SyncHealthService(
			$this->createMock( MerchantCenterService::class ),
			$this->circuit_breaker,
			$this->createMock( MarketService::class ),
			$this->transients,
			$wpdb,
			$this->merchant_statuses,
			$this->createMock( JobRepository::class )
		);
		$query   = new ReflectionMethod( SyncHealthService::class, 'query_last_completed_at' );
		// setAccessible() is required on PHP < 8.1 and a deprecated no-op from 8.1 onward.
		$query->setAccessible( true );

		$this->assertEqualsWithDelta( time() - 10 * DAY_IN_SECONDS, $query->invoke( $service, 'update_products' ), 5 );
		// An action completed before the last disconnect is not counted.
		$this->assertNull( $query->invoke( $service, 'update_products', time() - 7 * DAY_IN_SECONDS ) );
		// Nor one completed after the given bound.
		$this->assertNull( $query->invoke( $service, 'update_merchant_product_statuses', null, time() - 7 * DAY_IN_SECONDS ) );
		$this->assertEqualsWithDelta( time() - 5 * DAY_IN_SECONDS, $query->invoke( $service, 'update_merchant_product_statuses' ), 5 );
		$this->assertEqualsWithDelta( time() - 5 * DAY_IN_SECONDS, $query->invoke( $service ), 5 );
		$this->assertNull( $query->invoke( $service, 'update_shipping_settings' ) );
	}

	public function test_connection_diagnostics_expose_only_known_fields() {
		$this->assertSame(
			[
				'status',
				'google_connected',
				'mc_setup_complete',
				'jetpack_connected',
				'merchant_id',
				'circuit_breaker_open',
				'circuit_breaker_retry_at',
				'url_matches',
			],
			array_keys( $this->service->get_summary( true )['pillars']['connection'] )
		);
	}

	/**
	 * @dataProvider review_refresh_cases
	 *
	 * @param bool     $running         Whether a fetch is queued or running.
	 * @param int|null $attempt_ago     Seconds since the last fetch finished, or null if none has.
	 * @param bool     $last_failed     Whether that fetch failed.
	 * @param string   $expected_reason
	 */
	public function test_request_review_refresh( bool $running, ?int $attempt_ago, bool $last_failed, string $expected_reason ) {
		$attempt_at = null === $attempt_ago ? null : time() - $attempt_ago;

		$this->review_job->method( 'is_scheduled' )->willReturn( $running );
		$this->state['options'][ OptionsInterface::MC_STATUSES_LAST_ATTEMPT ] = $attempt_at;
		if ( $last_failed ) {
			$this->state['transients'][ TransientsInterface::MC_STATUSES ] = [ 'error' => 'Google returned an error.' ];
		}

		$started = 'started' === $expected_reason;
		$this->merchant_statuses->expects( $started ? $this->once() : $this->never() )
			->method( 'maybe_refresh_status_data' )
			->with( true );
		$this->transients->expects( $started ? $this->once() : $this->never() )
			->method( 'delete' )
			->with( TransientsInterface::SYNC_HEALTH );

		$result = $this->service->request_review_refresh();

		$this->assertSame( $started, $result['started'] );
		$this->assertSame( $expected_reason, $result['reason'] );
		$this->assertSame( 'recent' === $expected_reason ? $attempt_at + 5 * MINUTE_IN_SECONDS : null, $result['available_at'] );
	}

	/**
	 * @return array
	 */
	public function review_refresh_cases(): array {
		return [
			'already running'            => [ true, 60, false, 'running' ],
			'within the cooldown'        => [ false, 60, false, 'recent' ],
			'failed within the cooldown' => [ false, 60, true, 'recent' ],
			'after the cooldown'         => [ false, 600, false, 'started' ],
			'failed, after the cooldown' => [ false, 600, true, 'started' ],
			'never fetched'              => [ false, null, false, 'started' ],
		];
	}

	/**
	 * Raw counts of a job with actions due now.
	 *
	 * @param int $count
	 *
	 * @return array
	 */
	protected static function due( int $count ): array {
		return [
			'pending'            => $count,
			'past_due'           => $count,
			'oldest_past_due_at' => time() - MINUTE_IN_SECONDS,
		];
	}

	/**
	 * Raw counts of a job with an action that started a few seconds ago.
	 *
	 * @return array
	 */
	protected static function running(): array {
		return [
			'running'           => 1,
			'oldest_running_at' => time() - 10,
		];
	}

	/**
	 * Raw counts of a job scheduled for later only.
	 *
	 * @return array
	 */
	protected static function scheduled_later(): array {
		return [ 'pending' => 1 ];
	}

	/**
	 * Raw counts of a job whose hook the job monitor stopped rescheduling.
	 *
	 * @return array
	 */
	protected static function paused(): array {
		return [
			'failed_24h' => 3,
			'hooks'      => [ self::hook( 'process_item', [ 'paused' => true ] ) ],
		];
	}

	/**
	 * Raw counts of a job with an action running for over five minutes.
	 *
	 * @return array
	 */
	protected static function stuck(): array {
		return [
			'running'           => 1,
			'oldest_running_at' => time() - 10 * MINUTE_IN_SECONDS,
		];
	}

	/**
	 * Raw counts of a job with an action past due for over an hour.
	 *
	 * @return array
	 */
	protected static function stalled(): array {
		return [
			'pending'            => 1,
			'past_due'           => 1,
			'oldest_past_due_at' => time() - 2 * HOUR_IN_SECONDS,
		];
	}

	/**
	 * Raw counts of a batched job whose create_batch failed with nothing queued after.
	 *
	 * @return array
	 */
	protected static function interrupted(): array {
		return [
			'failed_24h'     => 1,
			'last_failed_at' => time() - MINUTE_IN_SECONDS,
			'hooks'          => [ self::hook( 'create_batch', [ 'last_failed_at' => time() - MINUTE_IN_SECONDS ] ) ],
		];
	}

	/**
	 * A hook row as built by SyncHealthService::query_jobs().
	 *
	 * @param string $stage
	 * @param array  $overrides
	 *
	 * @return array
	 */
	protected static function hook( string $stage, array $overrides = [] ): array {
		return array_merge(
			[
				'stage'             => $stage,
				'paused'            => false,
				'last_failed_at'    => null,
				'last_completed_at' => null,
			],
			$overrides
		);
	}

	/**
	 * A job row as built by SyncHealthService::query_jobs(), before its state is derived.
	 *
	 * @param string $name
	 * @param array  $overrides
	 *
	 * @return array
	 */
	protected function job( string $name, array $overrides = [] ): array {
		return array_merge(
			[
				'name'               => $name,
				'pending'            => 0,
				'past_due'           => 0,
				'running'            => 0,
				'failed_24h'         => 0,
				'oldest_past_due_at' => null,
				'oldest_running_at'  => null,
				'last_failed_at'     => null,
				'last_completed_at'  => null,
				'hooks'              => [],
			],
			$overrides
		);
	}
}
