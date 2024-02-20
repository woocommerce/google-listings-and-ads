<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Settings;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
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


	public function test_saving_woocommerce_settings_schedules_notification_job() {
		$this->notification_service->expects( $this->once() )
			->method( 'is_enabled' )
			->willReturn( true );

		$this->settings_notification_job->expects( $this->once() )
			->method( 'schedule' );

		$this->syncer_hooks->register();
		do_action( 'update_option', 'woocommerce_sample_option' );
	}

	public function test_saving_other_settings_dont_schedules_notification_job() {
		$this->notification_service->expects( $this->once() )
			->method( 'is_enabled' )
			->willReturn( true );

		$this->settings_notification_job->expects( $this->never() )
			->method( 'schedule' );

		$this->syncer_hooks->register();
		do_action( 'update_option', 'other_option' );
	}

	public function test_dont_register_if_notifications_disabled() {
		$this->notification_service->expects( $this->once() )
			->method( 'is_enabled' )
			->willReturn( false );

		$this->settings_notification_job->expects( $this->never() )
			->method( 'schedule' );

		$this->syncer_hooks->register();
		do_action( 'update_option', 'woocommerce_sample_option' );
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
