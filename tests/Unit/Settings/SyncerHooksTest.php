<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Settings;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\SettingsNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Settings\SyncerHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SyncerHooksTest
 *
 * @group SyncerHooks
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Settings
 */
class SyncerHooksTest extends UnitTest {

	/** @var MockObject|NotificationsService $notification_service */
	protected $notification_service;

	/** @var MockObject|SettingsNotificationJob $settings_notification_job */
	protected $settings_notification_job;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var SyncerHooks $syncer_hooks */
	protected $syncer_hooks;

	/**
	 * @var int The ID of an example shipping class stored in DB.
	 */
	protected $sample_class_id;

	/**
	 * WooCommerce General Settings IDs
	 * Copied from https://github.com/woocommerce/woocommerce/blob/af03815134385c72feb7a70abc597eca57442820/plugins/woocommerce/includes/admin/settings/class-wc-settings-general.php#L34
	 */
	protected const ALLOWED_SETTINGS = [
		'store_address',
		'woocommerce_store_address',
		'woocommerce_store_address_2',
		'woocommerce_store_city',
		'woocommerce_default_country',
		'woocommerce_store_postcode',
		'store_address',
		'general_options',
		'woocommerce_allowed_countries',
		'woocommerce_all_except_countries',
		'woocommerce_specific_allowed_countries',
		'woocommerce_ship_to_countries',
		'woocommerce_specific_ship_to_countries',
		'woocommerce_default_customer_address',
		'woocommerce_calc_taxes',
		'woocommerce_enable_coupons',
		'woocommerce_calc_discounts_sequentially',
		'general_options',
		'pricing_options',
		'woocommerce_currency',
		'woocommerce_currency_pos',
		'woocommerce_price_thousand_sep',
		'woocommerce_price_decimal_sep',
		'woocommerce_price_num_decimals',
		'pricing_options',
	];

	public function test_saving_woocommerce_general_settings_schedules_notification_job() {
		$this->notification_service->expects( $this->once() )
			->method( 'is_ready' )
			->willReturn( true );

		$this->settings_notification_job->expects( $this->exactly( count( self::ALLOWED_SETTINGS ) ) )
			->method( 'schedule' );

		$this->syncer_hooks->register();
		foreach ( self::ALLOWED_SETTINGS as $setting ) {
			do_action( 'update_option', $setting );
		}
	}

	public function test_saving_other_settings_dont_schedules_notification_job() {
		$this->notification_service->expects( $this->once() )
			->method( 'is_ready' )
			->willReturn( true );

		$this->settings_notification_job->expects( $this->never() )
			->method( 'schedule' );

		$this->syncer_hooks->register();
		do_action( 'update_option', 'other_option' );
	}

	public function test_dont_register_if_notifications_disabled() {
		$this->notification_service->expects( $this->once() )
			->method( 'is_ready' )
			->willReturn( false );

		$this->settings_notification_job->expects( $this->never() )
			->method( 'schedule' );

		$this->syncer_hooks->register();
		do_action( 'update_option', 'store_address' );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->notification_service      = $this->createMock( NotificationsService::class );
		$this->settings_notification_job = $this->createMock( SettingsNotificationJob::class );
		$this->job_repository            = $this->createMock( JobRepository::class );
		$this->job_repository->expects( $this->any() )
			->method( 'get' )
			->willReturnMap(
				[
					[ SettingsNotificationJob::class, $this->settings_notification_job ],
				]
			);

		$this->syncer_hooks = new SyncerHooks( $this->job_repository, $this->notification_service );
	}
}
