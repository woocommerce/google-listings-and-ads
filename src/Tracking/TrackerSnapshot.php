<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

/**
 * Include Google Listings and Ads data in the WC Tracker snapshot.
 *
 * ContainerAware used to access:
 * - AdsService
 * - MerchantCenterService
 * - MerchantMetrics
 * - TargetAudience
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class TrackerSnapshot implements Conditional, ContainerAwareInterface, OptionsAwareInterface, Registerable, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * Not needed if allow_tracking is disabled.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
	}

	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_tracker_data',
			function( $data ) {
				return $this->include_snapshot_data( $data );
			}
		);
	}

	/**
	 * Add extension data to the WC Tracker snapshot.
	 *
	 * @param array $data The existing array of tracker data.
	 *
	 * @return array The updated array of tracker data.
	 */
	protected function include_snapshot_data( $data = [] ): array {
		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = [];
		}

		$data['extensions'][ $this->get_slug() ] = [
			'settings' => $this->get_settings(),
		];

		return $data;
	}

	/**
	 * Get general extension and settings data for the extension.
	 *
	 * @return array
	 */
	protected function get_settings(): array {
		/** @var TargetAudience $target_audience */
		$target_audience = $this->container->get( TargetAudience::class );
		$mc_settings     = $this->options->get( OptionsInterface::MERCHANT_CENTER );
		/** @var AdsService $ads_service */
		$ads_service = $this->container->get( AdsService::class );
		/** @var MerchantCenterService $mc_service */
		$mc_service = $this->container->get( MerchantCenterService::class );
		/** @var MerchantMetrics $merchant_metrics */
		$merchant_metrics = $this->container->get( MerchantMetrics::class );

		return [
			'version'                         => $this->get_version(),
			'db_version'                      => $this->options->get( OptionsInterface::DB_VERSION ),
			'tos_accepted'                    => $this->get_boolean_value( OptionsInterface::WP_TOS_ACCEPTED ),
			'google_connected'                => $this->get_boolean_value( OptionsInterface::GOOGLE_CONNECTED ),
			'mc_setup'                        => $this->get_boolean_value( OptionsInterface::MC_SETUP_COMPLETED_AT ),
			'ads_setup'                       => $this->get_boolean_value( OptionsInterface::ADS_SETUP_COMPLETED_AT ),
			'target_audience'                 => $target_audience->get_target_countries(),
			'shipping_rate'                   => $mc_settings['shipping_rate'] ?? '',
			'shipping_time'                   => $mc_settings['shipping_time'] ?? '',
			'tax_rate'                        => $mc_settings['tax_rate'] ?? '',
			'has_account_issue'               => $mc_service->is_connected() && $mc_service->has_account_issues() ? 'yes' : 'no',
			'has_at_least_one_synced_product' => $mc_service->is_connected() && $mc_service->has_at_least_one_synced_product() ? 'yes' : 'no',
			'ads_setup_started'               => $ads_service->is_setup_started() ? 'yes' : 'no',
			'ads_customer_id'                 => $this->options->get_ads_id(),
			'ads_campaign_count'              => $merchant_metrics->get_campaign_count(),
		];
	}

	/**
	 * Get boolean value from options, return as yes or no.
	 *
	 * @param string $key Option key name.
	 *
	 * @return string
	 */
	protected function get_boolean_value( string $key ): string {
		return (bool) $this->options->get( $key ) ? 'yes' : 'no';
	}
}
