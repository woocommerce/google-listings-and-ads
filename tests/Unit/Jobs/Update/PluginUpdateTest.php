<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\CleanupProductTargetCountriesJob;
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

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var array In-memory option storage backing the options mock. */
	protected $stored_options = [];

	/** @var PluginUpdate $plugin_update */
	protected $plugin_update;

	public function setUp(): void {
		parent::setUp();
		$this->job_repository  = $this->createMock( JobRepository::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->options         = $this->createMock( OptionsInterface::class );

		$this->merchant_center->method( 'is_connected' )->willReturn( true );

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
		$this->options->method( 'delete' )->willReturnCallback(
			function ( $name ) {
				unset( $this->stored_options[ $name ] );
				return true;
			}
		);

		$this->plugin_update = $this->create_plugin_update();
	}

	public function test_upgrading_to_mapi_release_schedules_full_product_resync() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		// 3.7.3 is above the older 1.x entries, so only the 3.8.0 job fires: a store upgrading
		// from the Content API era must re-sync every product to pick up Merchant API ids.
		$this->job_repository->expects( $this->once() )
			->method( 'get' )
			->with( UpdateAllProducts::class )
			->willReturn( $update_all_products );

		$this->options->expects( $this->never() )->method( 'update' );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );
	}

	public function test_upgrading_within_mapi_versions_does_not_reschedule_resync() {
		// No-refire boundary guard, not fix verification: the fires-on-upgrade case is covered by
		// test_upgrading_to_mapi_release_schedules_full_product_resync. This pins the gate at `<` so
		// a store already on the Merchant API release (>= 3.8.0) never re-runs the re-sync.
		$this->job_repository->expects( $this->never() )->method( 'get' );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->plugin_update->install( '3.8.0', '3.8.1' );
	}

	public function test_job_requested_by_several_versions_is_scheduled_once() {
		$cleanup             = $this->create_job( CleanupProductTargetCountriesJob::class, [ true ] );
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ true ] );
		$cleanup->expects( $this->once() )->method( 'schedule' );
		$update_all_products->expects( $this->once() )->method( 'schedule' );

		// Upgrading from before 1.0.1 crosses three versions that each ask for a full re-sync.
		$this->job_repository->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ CleanupProductTargetCountriesJob::class, $cleanup ],
					[ UpdateAllProducts::class, $update_all_products ],
				]
			);

		$this->plugin_update->install( '1.0.0', '3.8.0' );

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );
	}

	public function test_job_not_ready_at_upgrade_is_retried_and_scheduled_once_ready() {
		// Not ready to sync during the upgrade request, ready on the next admin request.
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false, true ] );
		$update_all_products->expects( $this->once() )->method( 'schedule' );
		$this->job_repository->method( 'get' )->with( UpdateAllProducts::class )->willReturn( $update_all_products );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame(
			[ UpdateAllProducts::class ],
			$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ] ?? null,
			'A job that could not be scheduled must be kept for a later retry.'
		);

		$this->plugin_update->retry_pending_jobs();

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );

		// Nothing pending anymore: a later request must not schedule it again.
		$this->plugin_update->retry_pending_jobs();
	}

	public function test_job_stays_pending_while_store_is_not_ready() {
		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false, false ] );
		$update_all_products->expects( $this->never() )->method( 'schedule' );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );

		$this->plugin_update->install( '3.7.3', '3.8.0' );
		$this->plugin_update->retry_pending_jobs();

		$this->assertSame(
			[ UpdateAllProducts::class ],
			$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ] ?? null
		);
	}

	public function test_pending_job_is_not_duplicated_by_a_later_upgrade() {
		$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ] = [ UpdateAllProducts::class ];

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->job_repository->expects( $this->once() )->method( 'get' )->willReturn( $update_all_products );

		// A second upgrade that asks for the same job again while it is still pending.
		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertSame(
			[ UpdateAllProducts::class ],
			$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ]
		);
	}

	public function test_pending_job_is_dropped_when_merchant_center_is_not_connected() {
		// Nothing was synced, so there is nothing to migrate: connecting runs a full sync anyway.
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->plugin_update = $this->create_plugin_update();

		$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ] = [ UpdateAllProducts::class ];

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );

		$this->plugin_update->retry_pending_jobs();

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );
	}

	public function test_upgrade_does_not_store_pending_job_when_merchant_center_is_not_connected() {
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->plugin_update = $this->create_plugin_update();

		$update_all_products = $this->create_job( UpdateAllProducts::class, [ false ] );
		$this->job_repository->method( 'get' )->willReturn( $update_all_products );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );
	}

	public function test_retry_does_nothing_when_no_job_is_pending() {
		$this->job_repository->expects( $this->never() )->method( 'get' );
		$this->merchant_center->expects( $this->never() )->method( 'is_connected' );
		$this->options->expects( $this->never() )->method( 'update' );
		$this->options->expects( $this->never() )->method( 'delete' );

		$this->plugin_update->retry_pending_jobs();
	}

	public function test_retry_is_skipped_during_ajax_requests() {
		$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ] = [ UpdateAllProducts::class ];
		$this->job_repository->expects( $this->never() )->method( 'get' );

		add_filter( 'wp_doing_ajax', '__return_true' );
		$this->plugin_update->retry_pending_jobs();
		remove_filter( 'wp_doing_ajax', '__return_true' );

		$this->assertSame(
			[ UpdateAllProducts::class ],
			$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ]
		);
	}

	public function test_unexpected_pending_values_are_discarded() {
		$this->stored_options[ OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS ] = [ 'Some\Unknown\Job', 123, [ 'nested' ] ];
		$this->job_repository->expects( $this->never() )->method( 'get' );

		$this->plugin_update->retry_pending_jobs();

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );
	}

	public function test_job_missing_from_repository_is_not_kept_pending() {
		$this->job_repository->method( 'get' )->willThrowException( JobException::job_does_not_exist( UpdateAllProducts::class ) );

		$this->plugin_update->install( '3.7.3', '3.8.0' );

		$this->assertArrayNotHasKey( OptionsInterface::PLUGIN_UPDATE_PENDING_JOBS, $this->stored_options );
	}

	/**
	 * @return PluginUpdate
	 */
	protected function create_plugin_update(): PluginUpdate {
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
}
