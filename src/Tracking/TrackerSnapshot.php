<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
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
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;
use Throwable;

/**
 * Include Google for WooCommerce data in the WC Tracker snapshot.
 *
 * ContainerAware used to access:
 * - Ads
 * - AdsService
 * - MarketService
 * - MerchantCenterService
 * - MerchantMetrics
 * - Middleware
 * - ProductImageSizeAudit
 * - TargetAudience
 * - TransientsInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class TrackerSnapshot implements ContainerAwareInterface, OptionsAwareInterface, Registerable, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * Issue codes and counts saved beside the feed issue counts, each validated on its own.
	 *
	 * @since x.x.x
	 */
	private const FEED_ISSUE_DETAIL_FIELDS = [
		'feed_issue_summary_checked_at',
		'approved_product_count',
		'critical_account_issue_count',
		'account_issue_codes',
		'critical_covers_all_targets',
		'plugin_issue_codes',
		'top_product_issue_codes',
		'top_presync_issue_codes',
	];

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

		return array_merge(
			$settings,
			$this->get_feed_issue_counts( $mc_service->is_connected() ),
			$this->get_feed_issue_details( $mc_service->is_connected() ),
			[
				'ads_serving_campaign_count' => $this->get_serving_campaign_count( $merchant_metrics ),
				'ads_billing_status'         => $this->get_ads_billing_status(),
				'mc_is_subaccount'           => $this->get_mc_is_subaccount( $mc_service->is_connected() ),
			]
		);
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
	 * Get the issue codes and approved product count saved beside the feed issue counts.
	 *
	 * Each key is validated on its own, so a summary saved before these keys existed, or one with a
	 * single invalid key, still sends every other key. A missing or invalid key is sent as null.
	 *
	 * @since x.x.x
	 *
	 * @param bool $is_connected Whether Merchant Center is connected.
	 *
	 * @return array Values keyed by snapshot field name, each null when missing or invalid.
	 */
	private function get_feed_issue_details( bool $is_connected ): array {
		$details = array_fill_keys( self::FEED_ISSUE_DETAIL_FIELDS, null );

		if ( ! $is_connected ) {
			return $details;
		}

		$summary = $this->options->get( OptionsInterface::FEED_ISSUE_SUMMARY );
		if ( ! is_array( $summary ) ) {
			return $details;
		}

		foreach ( self::FEED_ISSUE_DETAIL_FIELDS as $field ) {
			if ( isset( $summary[ $field ] ) && $this->is_valid_feed_issue_detail( $field, $summary[ $field ] ) ) {
				$details[ $field ] = $summary[ $field ];
			}
		}

		return $details;
	}

	/**
	 * Check that a saved feed issue detail has the shape the snapshot sends.
	 *
	 * @since x.x.x
	 *
	 * @param string $field Snapshot field name.
	 * @param mixed  $value Saved value.
	 *
	 * @return bool
	 */
	private function is_valid_feed_issue_detail( string $field, $value ): bool {
		switch ( $field ) {
			case 'critical_covers_all_targets':
				return in_array( $value, [ 'yes', 'partial', 'no' ], true );

			case 'account_issue_codes':
				return $this->is_issue_code_map(
					$value,
					function ( $severity ) {
						return in_array( $severity, [ 'critical', 'error' ], true );
					}
				);

			case 'plugin_issue_codes':
				if ( ! is_array( $value ) || array_values( $value ) !== $value ) {
					return false;
				}
				foreach ( $value as $code ) {
					if ( ! in_array( $code, MerchantStatuses::PLUGIN_ACCOUNT_ISSUE_CODES, true ) ) {
						return false;
					}
				}
				return true;

			case 'top_product_issue_codes':
			case 'top_presync_issue_codes':
				return $this->is_issue_code_map( $value, [ $this, 'is_count' ] );

			default:
				return $this->is_count( $value );
		}
	}

	/**
	 * Check that a value is a map of issue codes that are safe as warehouse keys to valid values.
	 *
	 * PHP stores a numeric code as an int key, so keys are checked as strings.
	 *
	 * @since x.x.x
	 *
	 * @param mixed    $value          The map to check.
	 * @param callable $is_valid_value Returns whether one of the map's values is valid.
	 *
	 * @return bool
	 */
	private function is_issue_code_map( $value, callable $is_valid_value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $code => $code_value ) {
			if ( ! preg_match( '/^[A-Za-z0-9_]{1,64}\z/', (string) $code ) || ! $is_valid_value( $code_value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check that a value is a non-negative int.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private function is_count( $value ): bool {
		return is_int( $value ) && $value >= 0;
	}

	/**
	 * Get the number of Ads campaigns that are currently serving.
	 *
	 * @since x.x.x
	 *
	 * @param MerchantMetrics $merchant_metrics
	 *
	 * @return int|null The count, or null when it can't be read.
	 */
	private function get_serving_campaign_count( MerchantMetrics $merchant_metrics ): ?int {
		try {
			return $merchant_metrics->get_serving_campaign_count();
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get the billing setup status of the connected Ads account, cached for 12 hours.
	 *
	 * The status passes through the `woocommerce_gla_ads_billing_setup_status` filter, so any value
	 * that isn't a known status is sent and cached as unknown. A failed read is sent as unknown and
	 * isn't cached.
	 *
	 * @since x.x.x
	 *
	 * @return string|null A BillingSetupStatus label or unknown, or null when Google or Ads isn't connected.
	 */
	private function get_ads_billing_status(): ?string {
		if ( ! $this->options->get_ads_id() || ! $this->options->get( OptionsInterface::GOOGLE_CONNECTED ) ) {
			return null;
		}

		$valid_statuses = array_merge( BillingSetupStatus::labels(), [ BillingSetupStatus::UNKNOWN ] );

		try {
			/** @var TransientsInterface $transients */
			$transients = $this->container->get( TransientsInterface::class );

			$status = $transients->get( TransientsInterface::ADS_BILLING_STATUS );
			if ( in_array( $status, $valid_statuses, true ) ) {
				return $status;
			}

			/** @var Ads $ads */
			$ads    = $this->container->get( Ads::class );
			$status = $ads->get_billing_status();
			if ( ! in_array( $status, $valid_statuses, true ) ) {
				$status = BillingSetupStatus::UNKNOWN;
			}

			$transients->set( TransientsInterface::ADS_BILLING_STATUS, $status, HOUR_IN_SECONDS * 12 );

			return $status;
		} catch ( Throwable $e ) {
			return BillingSetupStatus::UNKNOWN;
		}
	}

	/**
	 * Get whether the Merchant Center account is a sub-account of Woo's manager account.
	 *
	 * @since x.x.x
	 *
	 * @param bool $is_connected Whether Merchant Center is connected.
	 *
	 * @return string|null 'yes' or 'no', or null when not connected or the lookup fails.
	 */
	private function get_mc_is_subaccount( bool $is_connected ): ?string {
		if ( ! $is_connected ) {
			return null;
		}

		try {
			/** @var Middleware $middleware */
			$middleware = $this->container->get( Middleware::class );

			return $middleware->is_subaccount() ? 'yes' : 'no';
		} catch ( Throwable $e ) {
			return null;
		}
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
