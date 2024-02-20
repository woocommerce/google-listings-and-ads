<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Settings;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\SettingsNotificationJob;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncerHooks
 *
 * Hooks to various WooCommerce and WordPress actions to automatically sync WooCommerce General Settings.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Settings
 *
 * @since x.x.x
 */
class SyncerHooks implements Service, Registerable {

	/**
	 * This property is used to avoid scheduling duplicate jobs in the same request.
	 *
	 * @var bool
	 */
	protected $already_scheduled = false;

	/**
	 * @var NotificationsService $notifications_service
	 */
	protected $notifications_service;

	/**
	 * @var SettingsNotificationJob $settings_notification_job
	 */
	protected $settings_notification_job;

	/**
	 * SyncerHooks constructor.
	 *
	 * @param JobRepository        $job_repository
	 * @param NotificationsService $notifications_service
	 */
	public function __construct( JobRepository $job_repository, NotificationsService $notifications_service ) {
		$this->settings_notification_job = $job_repository->get( SettingsNotificationJob::class );
		$this->notifications_service     = $notifications_service;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		if ( ! $this->notifications_service->is_enabled() ) {
			return;
		}

		$update_rest = function ( $option ) {
			if ( strpos( $option, 'woocommerce_' ) === 0 ) {
				$this->handle_update_shipping_settings();
			}
		};

		add_action( 'update_option', $update_rest, 90, 1 );
	}

	/**
	 * Handle updating of Merchant Center shipping settings.
	 *
	 * @return void
	 */
	protected function handle_update_shipping_settings() {
		// Bail if an event is already scheduled in the current request
		if ( $this->already_scheduled ) {
			return;
		}

		$this->settings_notification_job->schedule();
		$this->already_scheduled = true;
	}
}
