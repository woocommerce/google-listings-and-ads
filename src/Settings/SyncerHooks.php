<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Settings;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
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
 * @since 2.8.0
 */
class SyncerHooks implements Service, Registerable {

	/**
	 * @var NotificationsService $notifications_service
	 */
	protected $notifications_service;

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

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


	/**
	 * SyncerHooks constructor.
	 *
	 * @param JobRepository        $job_repository
	 * @param NotificationsService $notifications_service
	 */
	public function __construct( JobRepository $job_repository, NotificationsService $notifications_service ) {
		$this->job_repository        = $job_repository;
		$this->notifications_service = $notifications_service;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		if ( ! $this->notifications_service->is_ready( NotificationsService::DATATYPE_SETTINGS, false ) ) {
			return;
		}

		$update_rest = function ( $option ) {
			if ( in_array( $option, self::ALLOWED_SETTINGS, true ) ) {
				$this->job_repository->get( SettingsNotificationJob::class )->schedule();
			}
		};

		add_action( 'update_option', $update_rest, 90, 1 );
	}
}
