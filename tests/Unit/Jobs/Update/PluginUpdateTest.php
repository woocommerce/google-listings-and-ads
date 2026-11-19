<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\CleanupProductTargetCountriesJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\DeleteYouTubeConversionReports;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\PluginUpdate;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class PluginUpdateTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update
 */
class PluginUpdateTest extends UnitTest {

	protected const PENDING = OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var array In-memory option storage backing the options mock. */
	protected $stored_options = [];

	/** @var string[] Messages sent to woocommerce_gla_debug_message. */
	protected $logged = [];

	/** @var string[] Context (method) sent with each logged message. */
	protected $logged_methods = [];

	/** @var string[] Job classes fetched from the job repository, in order. */
	protected $fetched = [];

	/** @var PluginUpdate $plugin_update */
	protected $plugin_update;

	public function setUp(): void {
		parent::setUp();
		$this->job_repository = $this->createMock( JobRepository::class );
		$this->options        = $this->createMock( OptionsInterface::class );

		$this->stored_options = [];
		$this->options->method( 'get' )->willReturnCallback(
			function ( $name, $default_value = null ) {
				return $this->stored_options[ $name ] ?? $default_value;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( $name, $value ) {
				$this->stored_options[ $name ] = $value;
				return true;
			}
		);

		$this->logged         = [];
		$this->logged_methods = [];
		$this->fetched        = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message, $method ) {
				$this->logged[]         = $message;
				$this->logged_methods[] = $method;
			},
			10,
			2
		);

		$this->plugin_update = $this->create_plugin_update( true );
	}

	public function test_upgrading_to_mapi_release_schedules_full_product_resync() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		// 3.7.3 is above the older 1.x entries, so the 3.8.0 re-sync and later entries fire. The 3.8.0
		// and 3.10.2 entries both ask for a full re-sync, which is scheduled once: a store upgrading
		// from the Content API era must re-sync every product to pick up Merchant API ids.
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertSame( [], $this->logged );
	}

	public function test_upgrading_within_mapi_versions_does_not_reschedule_resync() {
		// No-refire boundary guard, not fix verification: the fires-on-upgrade case is covered by
		// test_upgrading_to_mapi_release_schedules_full_product_resync. This pins the gate at `<` so
		// a store already past the latest re-sync entry (>= 3.10.2) never re-runs the re-sync.
		$this->stub_jobs( [] );

		$this->plugin_update->install( '3.10.2', '3.10.3' );

		$this->assertNotContains( UpdateAllProducts::class, $this->fetched );
	}

	public function test_upgrading_to_exchange_rate_fix_release_schedules_full_product_resync() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		// Products marked invalid by the exchange-rate validation bug are excluded from the
		// expiring-products resync, so the fix release re-syncs every product once.
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		$this->plugin_update->install( '3.10.1', '3.10.2' );
	}

	public function test_upgrading_from_earlier_version_schedules_conversion_report_cleanup() {
		$cleanup = $this->create_job( DeleteYouTubeConversionReports::class, [ true ] );
		$cleanup->expects( $this->once() )->method( 'schedule' );
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$this->stub_jobs(
			[
				DeleteYouTubeConversionReports::class => $cleanup,
				UpdateAllProducts::class              => $update_all_products,
			]
		);

		$this->plugin_update->install( '3.9.7', '3.10.0' );

		// 3.9.7 is also below the 3.10.2 re-sync entry, so that job is scheduled too.
		$this->assertSame( [ DeleteYouTubeConversionReports::class, UpdateAllProducts::class ], $this->fetched );
		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_upgrading_from_cleanup_release_does_not_reschedule_conversion_report_cleanup() {
		$this->stub_jobs( [] );

		// Past every entry (3.10.0 cleanup, 3.10.2 re-sync), so nothing is scheduled.
		$this->plugin_update->install( '3.10.2', '3.10.3' );

		$this->assertSame( [], $this->fetched );
	}

	public function test_upgrade_creates_the_empty_option_so_admin_requests_read_it_from_autoloaded_options() {
		$this->stub_jobs( [] );

		$this->plugin_update->install( '3.10.0', '3.10.1' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_job_requested_by_several_versions_is_scheduled_once() {
		$cleanup             = $this->create_job( CleanupProductTargetCountriesJob::class, [ true ] );
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$cleanup->expects( $this->once() )->method( 'schedule' );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		// Upgrading from before 1.0.1 crosses three versions that each ask for a full re-sync.
		$this->stub_jobs(
			[
				CleanupProductTargetCountriesJob::class => $cleanup,
				UpdateAllProducts::class                => $update_all_products,
			]
		);

		$this->plugin_update->install( '1.0.0', '3.8.0' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertSame(
			[
				CleanupProductTargetCountriesJob::class => 1,
				UpdateAllProducts::class                => 1,
				DeleteYouTubeConversionReports::class   => 1,
			],
			array_count_values( $this->fetched )
		);
	}

	public function test_job_not_ready_at_upgrade_is_retried_and_scheduled_once_ready() {
		// Not ready to sync during the upgrade request, ready on the retry an hour later.
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false, true ] );
		$update_all_products->expects( $this->once() )->method( 'schedule' );
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame(
			[ UpdateAllProducts::class ],
			array_keys( $this->stored_options[ self::PENDING ] ),
			'A job that could not be scheduled must be kept for a later retry.'
		);

		$this->age_pending_jobs();
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );

		// Nothing pending anymore: a later request must not schedule it again.
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( 2, array_count_values( $this->fetched )[ UpdateAllProducts::class ] );
	}

	public function test_pending_job_is_retried_at_most_once_per_interval() {
		$this->stored_options[ self::PENDING ] = [ UpdateAllProducts::class => time() - 60 ];
		$this->job_repository->expects( $this->never() )->method( 'get' );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->plugin_update->retry_pending_jobs();
	}

	public function test_job_stays_pending_while_store_is_not_ready() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$update_all_products->expects( $this->never() )->method( 'schedule' );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );

		$this->age_pending_jobs();
		$before = time();
		$this->plugin_update->retry_pending_jobs();

		$pending = $this->stored_options[ self::PENDING ];
		$this->assertSame( [ UpdateAllProducts::class ], array_keys( $pending ) );
		$this->assertGreaterThanOrEqual( $before, $pending[ UpdateAllProducts::class ], 'The attempt time must be updated so the next retry waits a full interval.' );
	}

	public function test_pending_job_is_kept_while_google_is_disconnected_after_setup() {
		// A Google 401 clears GOOGLE_CONNECTED but leaves Merchant Center setup complete, and
		// reconnecting Google runs no full sync: the pending re-sync must survive it.
		$this->plugin_update = $this->create_plugin_update( true, false );

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );

		$this->age_pending_jobs();
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [ UpdateAllProducts::class ], array_keys( $this->stored_options[ self::PENDING ] ) );
	}

	public function test_pending_job_is_stamped_before_it_is_scheduled() {
		// A concurrent admin request reading the option while this one schedules must see the
		// job as not due, so it doesn't schedule it a second time.
		$this->age_pending_jobs();
		$before = time();

		$update_all_products = $this->createMock( UpdateAllProducts::class );
		$update_all_products->method( 'can_schedule' )->willReturnCallback(
			function () use ( $before ) {
				$this->assertGreaterThanOrEqual( $before, $this->stored_options[ self::PENDING ][ UpdateAllProducts::class ] );
				return true;
			}
		);
		$update_all_products->expects( $this->once() )->method( 'schedule' );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );

		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_mixed_outcome_keeps_only_the_job_that_could_not_be_scheduled() {
		$cleanup             = $this->create_job( CleanupProductTargetCountriesJob::class, [ true ] );
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false, true ] );
		$cleanup->expects( $this->once() )->method( 'schedule' );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		$this->stub_jobs(
			[
				CleanupProductTargetCountriesJob::class => $cleanup,
				UpdateAllProducts::class                => $update_all_products,
			]
		);

		$this->plugin_update->install( '1.0.0', '3.8.0' );
		$this->assertSame( [ UpdateAllProducts::class ], array_keys( $this->stored_options[ self::PENDING ] ) );

		$this->age_pending_jobs();
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertSame(
			[
				CleanupProductTargetCountriesJob::class => 1,
				UpdateAllProducts::class                => 2,
				DeleteYouTubeConversionReports::class   => 1,
			],
			array_count_values( $this->fetched ),
			'The retry must only attempt the job that was still pending.'
		);
	}

	public function test_pending_job_is_not_duplicated_by_a_later_upgrade() {
		$this->stored_options[ self::PENDING ] = [ UpdateAllProducts::class => time() - 60 ];

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		// A second upgrade that asks for the same job again while it is still pending.
		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [ UpdateAllProducts::class ], array_keys( $this->stored_options[ self::PENDING ] ) );
		$this->assertSame( 1, array_count_values( $this->fetched )[ UpdateAllProducts::class ] );
	}

	public function test_pending_job_is_dropped_when_merchant_center_setup_is_not_complete() {
		// Setup was undone (full disconnect): nothing synced is left to migrate, and completing
		// setup again runs a full sync.
		$this->plugin_update = $this->create_plugin_update( false );

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );

		$this->age_pending_jobs();
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertCount( 1, $this->logged );
		$this->assertStringContainsString( UpdateAllProducts::class, $this->logged[0] );
	}

	public function test_upgrade_does_not_keep_job_when_merchant_center_setup_is_not_complete() {
		$this->plugin_update = $this->create_plugin_update( false );

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertSame( [], $this->logged, 'A store that never set up Merchant Center must not log about upgrade jobs.' );
	}

	public function test_state_changes_are_logged_once() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false, false, true ] );
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		// Deferred: one line.
		$this->plugin_update->install( '3.7.3', '3.8.0' );
		$this->assertCount( 1, $this->logged );
		$this->assertStringContainsString( UpdateAllProducts::class, $this->logged[0] );

		// Still not ready: no new line.
		$this->age_pending_jobs();
		$this->plugin_update->retry_pending_jobs();
		$this->assertCount( 1, $this->logged );

		// Scheduled: one more line.
		$this->age_pending_jobs();
		$this->plugin_update->retry_pending_jobs();
		$this->assertCount( 2, $this->logged );
		$this->assertStringContainsString( UpdateAllProducts::class, $this->logged[1] );

		// Each line names the method that wrote it, like the plugin's other log lines.
		foreach ( $this->logged_methods as $method ) {
			$this->assertStringStartsWith( PluginUpdate::class . '::', $method );
		}
	}

	public function test_retry_does_nothing_when_no_job_is_pending() {
		$this->stored_options[ self::PENDING ] = [];
		$this->job_repository->expects( $this->never() )->method( 'get' );
		$this->merchant_center->expects( $this->never() )->method( 'is_setup_complete' );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->plugin_update->retry_pending_jobs();
	}

	public function test_retry_recreates_the_deleted_option_so_later_requests_read_it_from_autoloaded_options() {
		// A Merchant Center disconnect deletes the option.
		unset( $this->stored_options[ self::PENDING ] );
		$this->job_repository->expects( $this->never() )->method( 'get' );
		$this->options->expects( $this->once() )->method( 'update' )->with( self::PENDING, [] );

		$this->plugin_update->retry_pending_jobs();
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_retry_is_skipped_during_ajax_requests() {
		$this->age_pending_jobs();
		$stored = $this->stored_options[ self::PENDING ];
		$this->job_repository->expects( $this->never() )->method( 'get' );

		add_filter( 'wp_doing_ajax', '__return_true' );
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( $stored, $this->stored_options[ self::PENDING ] );
	}

	public function test_register_retries_pending_jobs_on_admin_init() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$update_all_products->expects( $this->once() )->method( 'schedule' );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );
		$this->age_pending_jobs();

		// Only this service's callback: the plugin's other admin_init callbacks are out of scope.
		remove_all_actions( 'admin_init' );
		$this->plugin_update->register();
		do_action( 'admin_init' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_unexpected_pending_values_are_discarded() {
		$this->stored_options[ self::PENDING ] = [
			'Some\Unknown\Job' => 1,
			0                  => UpdateAllProducts::class,
			'nested'           => [ 'x' ],
		];
		$this->job_repository->expects( $this->never() )->method( 'get' );

		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_job_missing_from_repository_is_reported_and_not_kept_pending() {
		$exception = JobException::job_does_not_exist( UpdateAllProducts::class );
		$this->stub_jobs( [ UpdateAllProducts::class => $exception ] );

		$reported = [];
		add_action(
			'woocommerce_gla_exception',
			function ( $e ) use ( &$reported ) {
				$reported[] = $e;
			}
		);

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [ $exception ], $reported );
		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_pending_job_missing_from_repository_is_reported_and_removed() {
		// A job already pending gets stamped before the attempt, so the failed attempt must
		// remove it again rather than keep it pending with a fresh stamp.
		$this->age_pending_jobs();
		$exception = JobException::job_does_not_exist( UpdateAllProducts::class );
		$this->job_repository->method( 'get' )->willThrowException( $exception );

		$reported = [];
		add_action(
			'woocommerce_gla_exception',
			function ( $e ) use ( &$reported ) {
				$reported[] = $e;
			}
		);

		$this->plugin_update->retry_pending_jobs();

		$this->assertSame( [ $exception ], $reported );
		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
	}

	public function test_job_that_is_not_an_action_scheduler_job_is_not_kept_pending() {
		// It can't say whether it can be scheduled, so it must not stay pending forever.
		$this->stub_jobs( [ UpdateAllProducts::class => $this->createMock( JobInterface::class ) ] );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertSame( [], $this->logged );
	}

	public function test_retry_attempts_only_the_jobs_that_are_due() {
		$recent                                = time() - 60;
		$this->stored_options[ self::PENDING ] = [
			CleanupProductTargetCountriesJob::class => $recent,
			UpdateAllProducts::class                => time() - HOUR_IN_SECONDS - 1,
		];

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->job_repository->expects( $this->once() )
			->method( 'get' )
			->with( UpdateAllProducts::class )
			->willReturn( $update_all_products );

		$this->plugin_update->retry_pending_jobs();

		$pending = $this->stored_options[ self::PENDING ];
		$this->assertSame( $recent, $pending[ CleanupProductTargetCountriesJob::class ], 'A job that is not due keeps its stamp.' );
		$this->assertGreaterThan( $recent, $pending[ UpdateAllProducts::class ] );
	}

	public function test_job_already_queued_from_its_first_batch_counts_as_scheduled() {
		// A concurrent request running the same upgrade queued the re-sync first: that run
		// processes every product with the current code, so this request must not keep the
		// job pending (which would run a second full re-sync an hour later).
		$this->plugin_update = $this->create_plugin_update( true, true, true );

		$update_all_products = $this->createMock( UpdateAllProducts::class );
		$update_all_products->method( 'can_schedule' )->willReturnMap(
			[
				[ null, false ],
				[ [ 1 ], false ],
			]
		);
		$update_all_products->expects( $this->never() )->method( 'schedule' );
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [], $this->stored_options[ self::PENDING ] );
		$this->assertSame( [], $this->logged );
	}

	public function test_job_running_past_its_first_batch_stays_pending() {
		// A run past its first batch may have started before the upgrade, with the old code.
		$this->plugin_update = $this->create_plugin_update( true, true, true );

		$update_all_products = $this->createMock( UpdateAllProducts::class );
		$update_all_products->method( 'can_schedule' )->willReturnMap(
			[
				[ null, false ],
				[ [ 1 ], true ],
			]
		);
		$update_all_products->expects( $this->never() )->method( 'schedule' );
		$this->stub_jobs( [ UpdateAllProducts::class => $update_all_products ] );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame( [ UpdateAllProducts::class ], array_keys( $this->stored_options[ self::PENDING ] ) );
	}

	public function test_retry_runs_after_the_installer_on_admin_init() {
		$order = [];

		$update_all_products = $this->createMock( UpdateAllProducts::class );
		$update_all_products->method( 'can_schedule' )->willReturn( true );
		$update_all_products->method( 'schedule' )->willReturnCallback(
			function () use ( &$order ) {
				$order[] = 'retry';
			}
		);
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );
		$this->age_pending_jobs();

		remove_all_actions( 'admin_init' );
		$this->plugin_update->register();
		// The installer's callback is registered at the default priority.
		add_action(
			'admin_init',
			function () use ( &$order ) {
				$order[] = 'installer';
			}
		);
		do_action( 'admin_init' );

		$this->assertSame( [ 'installer', 'retry' ], $order );
	}

	/**
	 * @param bool $setup_complete Whether Merchant Center setup is complete.
	 * @param bool $connected      Whether Merchant Center is connected (Google included).
	 * @param bool $ready          Whether the store is ready to sync.
	 *
	 * @return PluginUpdate
	 */
	protected function create_plugin_update( bool $setup_complete, bool $connected = true, bool $ready = false ): PluginUpdate {
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( $setup_complete );
		$this->merchant_center->method( 'is_connected' )->willReturn( $setup_complete && $connected );
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( $ready );

		$plugin_update = new PluginUpdate( $this->job_repository );
		$plugin_update->set_options_object( $this->options );
		$plugin_update->set_merchant_center_object( $this->merchant_center );

		return $plugin_update;
	}

	/**
	 * Mock a job whose `can_schedule( null )` answers the given values in turn.
	 *
	 * @param string $job_class
	 * @param bool[] $can_schedule
	 *
	 * @return MockObject
	 */
	protected function create_job( string $job_class, array $can_schedule ): MockObject {
		$job = $this->createMock( $job_class );
		$job->method( 'can_schedule' )->with( null )->willReturnOnConsecutiveCalls( ...$can_schedule );

		return $job;
	}

	/**
	 * Have the job repository return the given job for each class, record every fetch in
	 * $this->fetched, and return a job that schedules normally for any other class.
	 *
	 * @param array $jobs Job mock, or exception to throw, keyed by job class name.
	 */
	protected function stub_jobs( array $jobs ): void {
		$this->job_repository->method( 'get' )->willReturnCallback(
			function ( string $job_class ) use ( $jobs ) {
				$this->fetched[] = $job_class;

				$job = $jobs[ $job_class ] ?? null;
				if ( $job instanceof \Throwable ) {
					throw $job;
				}
				if ( null === $job ) {
					$job = $this->createMock( $job_class );
					$job->method( 'can_schedule' )->willReturn( true );
				}

				return $job;
			}
		);
	}

	/**
	 * Make the pending jobs due for a retry, as if the retry interval had passed. With nothing
	 * pending yet, marks UpdateAllProducts as pending.
	 */
	protected function age_pending_jobs(): void {
		$pending = $this->stored_options[ self::PENDING ] ?? [];
		if ( empty( $pending ) ) {
			$pending = [ UpdateAllProducts::class => 0 ];
		}

		$this->stored_options[ self::PENDING ] = array_fill_keys( array_keys( $pending ), time() - HOUR_IN_SECONDS - 1 );
	}
}
