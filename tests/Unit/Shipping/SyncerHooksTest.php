<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings as GoogleSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\SyncerHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Shipping_Method;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * Class SyncerHooksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|MerchantCenterService  $merchant_center
 * @property MockObject|GoogleSettings         $google_settings
 * @property MockObject|UpdateShippingSettings $update_shipping_job
 * @property MockObject|JobRepository          $job_repository
 * @property SyncerHooks                       $syncer_hooks
 */
class SyncerHooksTest extends UnitTest {

	/**
	 * @var int The ID of an example shipping zone stored in DB.
	 */
	protected $sample_zone_id;

	/**
	 * @var int The ID of an example shipping class stored in DB.
	 */
	protected $sample_class_id;

	public function test_saving_shipping_zone_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'GB' );
		$zone->add_location( 'GB', 'country' );
		$zone->save();
	}

	public function test_saving_shipping_zone_multiple_times_schedules_update_job_only_once() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'EU' );
		$zone->add_location( 'GB', 'country' );
		$zone->save();

		$zone->add_location( 'NL', 'country' );
		$zone->save();
	}

	public function test_adding_new_method_to_shipping_zone_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		$zone = WC_Shipping_Zones::get_zone( $this->sample_zone_id );
		$zone->add_shipping_method( 'flat_rate' );
	}

	public function test_deleting_method_from_shipping_zone_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		$zone = WC_Shipping_Zones::get_zone( $this->sample_zone_id );

		$methods   = $zone->get_shipping_methods();
		$method_id = $methods[ array_key_first( $methods ) ]->instance_id;

		$zone->delete_shipping_method( $method_id );
	}

	public function test_updating_method_options_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		$zone = WC_Shipping_Zones::get_zone( $this->sample_zone_id );

		$methods = $zone->get_shipping_methods();
		/** @var WC_Shipping_Method $method */
		$method      = $methods[ array_key_first( $methods ) ];
		$method_id   = $method->id;
		$instance_id = $method->instance_id;

		update_option( "woocommerce_{$method_id}_{$instance_id}_settings", $method->instance_settings );
	}

	public function test_updating_unrelated_option_does_not_schedule_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->never() )
								  ->method( 'schedule' );

		$method_id   = 'unsupported_gla_shipping_method';
		$instance_id = 0;

		update_option( "woocommerce_{$method_id}_{$instance_id}_settings", [] );
	}

	public function test_adding_shipping_class_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		// Create a shipping class.
		$this->factory()->term->create( [
			'taxonomy' => 'product_shipping_class',
			'name'     => 'light',
		] );
	}

	public function test_deleting_shipping_class_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		// Delete a shipping class.
		wp_delete_term( $this->sample_class_id, 'product_shipping_class' );
	}

	public function test_updating_shipping_class_schedules_update_job() {
		$this->mock_sync_ready_flags_and_register_hooks( true, true );

		$this->update_shipping_job->expects( $this->once() )
								  ->method( 'schedule' );

		// Update a shipping class.
		wp_update_term( $this->sample_class_id, 'product_shipping_class', [ 'name' => 'heavyweight' ] );
	}

	public function test_does_not_schedule_update_if_mc_account_not_connected() {
		$this->mock_sync_ready_flags_and_register_hooks( false, true );

		$this->update_shipping_job->expects( $this->never() )
								  ->method( 'schedule' );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'GB' );
		$zone->add_location( 'GB', 'country' );
		$zone->save();
	}

	public function test_does_not_schedule_update_if_shipping_option_not_automatic() {
		$this->mock_sync_ready_flags_and_register_hooks( true, false );

		$this->update_shipping_job->expects( $this->never() )
								  ->method( 'schedule' );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'GB' );
		$zone->add_location( 'GB', 'country' );
		$zone->save();
	}

	/**
	 * Mocks the validation methods that allow/disallow the shipping settings to be synced.
	 *
	 * @param bool $mc_connected    Is merchant center account connected?
	 * @param bool $automatic_rates Should GLA get the shipping rates automatically from WooCommerce?
	 */
	protected function mock_sync_ready_flags_and_register_hooks( bool $mc_connected, bool $automatic_rates ): void {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( $mc_connected );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( $automatic_rates );

		$this->syncer_hooks->register();
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		// Create a sample zone before registering the hooks.
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'NL' );
		$zone->add_location( 'NL', 'country' );
		$zone->add_shipping_method( 'free_shipping' );
		$zone->save();
		$this->sample_zone_id = $zone->get_id();

		// Create a sample shipping class.
		$this->sample_class_id = $this->factory()->term->create( [
			'taxonomy' => 'product_shipping_class',
			'name'     => 'heavy',
		] );

		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->google_settings = $this->createMock( GoogleSettings::class );
		$this->update_shipping_job = $this->createMock( UpdateShippingSettings::class );
		$this->job_repository      = $this->createMock( JobRepository::class );
		$this->job_repository->expects( $this->any() )
							 ->method( 'get' )
							 ->willReturnMap(
								 [
									 [ UpdateShippingSettings::class, $this->update_shipping_job ],
								 ]
							 );

		$this->syncer_hooks = new SyncerHooks( $this->merchant_center, $this->google_settings, $this->job_repository );
	}
}
