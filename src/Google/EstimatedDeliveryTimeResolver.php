<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountShippingSettingsService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves a single-country estimated delivery time for the post-purchase GCR opt-in prompt.
 *
 * Routes on the merchant's declared `shipping_time` setting (`flat`/`manual`) rather than
 * checking both sources as a fallback chain: `flat` merchants are resolved from this plugin's
 * own local shipping-time table only, `manual` merchants from the connected Merchant Center
 * account only. There is no `automatic` value for this setting and no case where both sources
 * are consulted for the same merchant.
 *
 * ContainerAware used for:
 * - ShippingTimeQuery
 */
class EstimatedDeliveryTimeResolver implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use OptionsAwareTrait;

	/** @var string Value of the MERCHANT_CENTER option's `shipping_time` field when delivery time is sourced from Merchant Center. */
	protected const SHIPPING_TIME_MANUAL = 'manual';

	/** How long a Merchant-Center-sourced shipping-settings read is cached before being re-fetched live. */
	protected const CACHE_TTL = HOUR_IN_SECONDS;

	/** @var MapiAccountShippingSettingsService */
	protected $mapi_shipping_settings_service;

	/**
	 * EstimatedDeliveryTimeResolver constructor.
	 *
	 * @param MapiAccountShippingSettingsService $mapi_shipping_settings_service
	 */
	public function __construct( MapiAccountShippingSettingsService $mapi_shipping_settings_service ) {
		$this->mapi_shipping_settings_service = $mapi_shipping_settings_service;
	}

	/**
	 * Resolve the maximum transit days for an order's destination country, from whichever
	 * source the merchant's declared `shipping_time` setting makes authoritative. Returns null
	 * (never a fabricated value) when that source has no entry for the country, when a
	 * `manual` merchant isn't connected to Merchant Center, or when the Merchant-API read fails.
	 *
	 * @param string $country Destination country code.
	 *
	 * @return int|null
	 */
	public function get_max_transit_days_for_country( string $country ): ?int {
		if ( self::SHIPPING_TIME_MANUAL === $this->get_shipping_time_mode() ) {
			return $this->get_max_transit_days_from_merchant_center( $country );
		}

		return $this->get_max_transit_days_from_local_table( $country );
	}

	/**
	 * The merchant's declared `shipping_time` setting (`flat`/`manual`) from the MERCHANT_CENTER
	 * option. Read directly rather than via `shipping_rate` — the two settings are independent
	 * (a merchant can pair a `flat` shipping rate with `manual` delivery-time sourcing, or vice
	 * versa), so `shipping_rate`'s value is never authoritative for this decision.
	 *
	 * @return string
	 */
	private function get_shipping_time_mode(): string {
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return $settings['shipping_time'] ?? '';
	}

	/**
	 * Resolve maximum transit days from this plugin's own local shipping-time table,
	 * single-country-scoped, mirroring ShippingTimeController::get_shipping_time_for_country().
	 *
	 * A fresh ShippingTimeQuery is fetched from the container on every call rather than
	 * constructor-injected, since Query::where()/get_results() accumulate and memoize state on
	 * the instance — a shared instance would silently return a previous call's country's
	 * results on a second lookup for a different country within the same request.
	 *
	 * @param string $country
	 *
	 * @return int|null
	 */
	private function get_max_transit_days_from_local_table( string $country ): ?int {
		$rows = $this->container->get( ShippingTimeQuery::class )->where( 'country', $country )->get_results();
		if ( empty( $rows ) ) {
			return null;
		}

		// `??` (key existence), not `?:` (truthiness) — a legitimately-configured 0-day
		// max_time must not be discarded in favour of the time fallback.
		return (int) ( $rows[0]['max_time'] ?? $rows[0]['time'] );
	}

	/**
	 * Resolve maximum transit days from the connected account's Merchant-Center-sourced shipping
	 * settings. Not connected, an empty account (no shipping policy configured), no matching
	 * country entry, or a failed read all resolve to null.
	 *
	 * @param string $country
	 *
	 * @return int|null
	 */
	private function get_max_transit_days_from_merchant_center( string $country ): ?int {
		if ( ! $this->options->get_merchant_id() ) {
			return null;
		}

		$shipping_settings = $this->get_cached_shipping_settings();
		if ( null === $shipping_settings ) {
			return null;
		}

		// A merchant's own Merchant-Center-configured services[] can have multiple countries
		// per service or overlapping services (unlike this plugin's own one-service-per-country
		// write path) — first matching service with a usable deliveryTime wins.
		foreach ( $shipping_settings['services'] ?? [] as $service ) {
			if ( ! in_array( $country, $service['deliveryCountries'] ?? [], true ) ) {
				continue;
			}

			$max_transit_days = $service['deliveryTime']['maxTransitDays'] ?? null;
			if ( null !== $max_transit_days ) {
				return (int) $max_transit_days;
			}
		}

		return null;
	}

	/**
	 * The connected account's Merchant-Center-sourced shipping settings, cached by merchant ID
	 * so this read isn't performed live on every order. Follows AdsReport::get_report_data()'s
	 * precedent of a dynamic per-key raw transient rather than the TransientsInterface allowlist,
	 * which only supports a fixed set of static keys. A failed read is not cached, so the next
	 * order for a `manual` merchant retries it live rather than treating a transient API error as
	 * a longer-lived "no data" result.
	 *
	 * @return array|null Null when the read fails.
	 */
	private function get_cached_shipping_settings(): ?array {
		$cache_key = 'gla_estimated_delivery_time_shipping_settings_' . $this->options->get_merchant_id();
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		try {
			$shipping_settings = $this->mapi_shipping_settings_service->get_shipping_settings();
		} catch ( MerchantApiException $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			return null;
		}

		set_transient( $cache_key, $shipping_settings, self::CACHE_TTL );

		return $shipping_settings;
	}
}
