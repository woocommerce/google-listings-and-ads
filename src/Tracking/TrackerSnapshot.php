<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;

/**
 * Include Google for WooCommerce data in the WC Tracker snapshot.
 *
 * ContainerAware used to access:
 * - AdsService
 * - MarketService
 * - MerchantCenterService
 * - MerchantMetrics
 * - ProductImageSizeAudit
 * - TargetAudience
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class TrackerSnapshot implements ContainerAwareInterface, OptionsAwareInterface, Registerable, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use PluginHelper;


	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) {
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
		$gcr_settings    = $this->options->get( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS );
		/** @var AdsService $ads_service */
		$ads_service = $this->container->get( AdsService::class );
		/** @var MerchantCenterService $mc_service */
		$mc_service = $this->container->get( MerchantCenterService::class );
		/** @var MerchantMetrics $merchant_metrics */
		$merchant_metrics = $this->container->get( MerchantMetrics::class );
		/** @var MarketService $market_service */
		$market_service = $this->container->get( MarketService::class );
		$market_counts  = $market_service->get_non_default_locale_market_counts();
		/** @var ProductImageSizeAudit $image_size_audit */
		$image_size_audit = $this->container->get( ProductImageSizeAudit::class );

		$settings = [
			'version'                         => $this->get_version(),
			'db_version'                      => $this->options->get( OptionsInterface::DB_VERSION ),
			'tos_accepted'                    => $this->get_boolean_value( OptionsInterface::WP_TOS_ACCEPTED ),
			'google_connected'                => $this->get_boolean_value( OptionsInterface::GOOGLE_CONNECTED ),
			'mc_setup'                        => $this->get_boolean_value( OptionsInterface::MC_SETUP_COMPLETED_AT ),
			'ads_setup'                       => $this->get_boolean_value( OptionsInterface::ADS_SETUP_COMPLETED_AT ),
			'enhanced_conversions'            => $this->get_boolean_value( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED ),
			'target_audience'                 => $target_audience->get_target_countries(),
			'shipping_rate'                   => $mc_settings['shipping_rate'] ?? '',
			'shipping_time'                   => $mc_settings['shipping_time'] ?? '',
			'has_account_issue'               => $mc_service->is_connected() && $mc_service->has_account_issues() ? 'yes' : 'no',
			'has_at_least_one_synced_product' => $mc_service->is_connected() && $mc_service->has_at_least_one_synced_product() ? 'yes' : 'no',
			'small_image_product_count'       => $mc_service->is_connected() ? $image_size_audit->get_small_image_product_count() : 0,
			'ads_setup_started'               => $ads_service->is_setup_started() ? 'yes' : 'no',
			'ads_customer_id'                 => $this->options->get_ads_id(),
			'ads_campaign_count'              => $merchant_metrics->get_campaign_count(),
			'youtube_connected'               => $this->get_boolean_value( OptionsInterface::YOUTUBE_THIRD_PARTY_LINK ),
			'reviews_collection'              => ! empty( $gcr_settings['gcr_collect_reviews_after_purchase'] ) ? 'yes' : 'no',
			'reviews_badge_widget'            => ! empty( $gcr_settings['gcr_badge_widget_enabled'] ) ? 'yes' : 'no',
			'multilingual_markets'            => $market_counts['multilingual'],
			'multicurrency_markets'           => $market_counts['multicurrency'],
		];

		return array_merge( $settings, $this->get_feed_issue_counts( $mc_service->is_connected() ) );
	}

	/**
	 * Get the feed-blocking issue counts saved by the last completed Merchant Center status check.
	 *
	 * @since x.x.x
	 *
	 * @param bool $is_connected Whether Merchant Center is connected.
	 *
	 * @return array Counts keyed by snapshot field name, each an int or null.
	 */
	protected function get_feed_issue_counts( bool $is_connected ): array {
		$fields  = MerchantStatuses::FEED_ISSUE_SUMMARY_FIELDS;
		$unknown = array_fill_keys( $fields, null );

		if ( ! $is_connected ) {
			return $unknown;
		}

		$summary = $this->options->get( OptionsInterface::FEED_ISSUE_SUMMARY );
		if ( ! is_array( $summary ) ) {
			return $unknown;
		}

		$counts = [];
		foreach ( $fields as $field ) {
			if ( ! isset( $summary[ $field ] ) || ! is_int( $summary[ $field ] ) || $summary[ $field ] < 0 ) {
				return $unknown;
			}

			$counts[ $field ] = $summary[ $field ];
		}

		return $counts;
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
