<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Throwable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20260813T1653383133
 *
 * Stores the secondary markets that flat shipping mode used to derive from the shipping
 * tables. Without this, a merchant's per-country flat setups stop being markets when the
 * derivation is removed, and the countries silently fold back into the primary feed.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 3.10.0
 */
class Migration20260813T1653383133 extends AbstractMigration {

	/**
	 * @var TargetAudience
	 */
	protected TargetAudience $target_audience;

	/**
	 * @var ShippingRateQuery
	 */
	protected ShippingRateQuery $shipping_rate_query;

	/**
	 * @var ShippingTimeQuery
	 */
	protected ShippingTimeQuery $shipping_time_query;

	/**
	 * @var MarketService
	 */
	protected MarketService $market_service;

	/**
	 * @var OptionsInterface
	 */
	protected OptionsInterface $options;

	/**
	 * Migration constructor.
	 *
	 * @param wpdb              $wpdb
	 * @param TargetAudience    $target_audience
	 * @param ShippingRateQuery $shipping_rate_query
	 * @param ShippingTimeQuery $shipping_time_query
	 * @param MarketService     $market_service
	 * @param OptionsInterface  $options
	 */
	public function __construct(
		wpdb $wpdb,
		TargetAudience $target_audience,
		ShippingRateQuery $shipping_rate_query,
		ShippingTimeQuery $shipping_time_query,
		MarketService $market_service,
		OptionsInterface $options
	) {
		parent::__construct( $wpdb );
		$this->target_audience     = $target_audience;
		$this->shipping_rate_query = $shipping_rate_query;
		$this->shipping_time_query = $shipping_time_query;
		$this->market_service      = $market_service;
		$this->options             = $options;
	}

	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number.
	 */
	public function get_applicable_version(): string {
		return '3.10.0';
	}

	/**
	 * Apply the migration.
	 *
	 * @return void
	 */
	public function apply(): void {
		if ( ! $this->has_convertible_shipping() ) {
			return;
		}

		$main_country = $this->target_audience->get_main_target_country();
		$countries    = $this->target_audience->get_target_countries();

		if ( '' === $main_country || count( $countries ) < 2 ) {
			return;
		}

		$rates = $this->shipping_rate_query->get_all_shipping_rates();
		$rates = is_array( $rates ) ? $rates : [];
		$times = $this->shipping_time_query->get_all_shipping_times();
		$times = is_array( $times ) ? $times : [];

		$baseline = $this->shipping_signature( $main_country, $rates, $times );
		$owned    = $this->countries_with_a_market();

		foreach ( $countries as $country ) {
			if ( $country === $main_country || in_array( $country, $owned, true ) ) {
				continue;
			}

			// A country with no row of its own was never its own market: it inherited the
			// primary's shipping, so it stays part of the primary market.
			if ( ! isset( $rates[ $country ] ) && ! isset( $times[ $country ] ) ) {
				continue;
			}

			if ( $this->shipping_signature( $country, $rates, $times ) === $baseline ) {
				continue;
			}

			$this->store_market( (string) $country );

			// A country listed twice would otherwise convert twice, and the second pass would
			// record that it was never in the primary feed.
			$owned[] = $country;
		}
	}

	/**
	 * Whether this store can have markets worth converting.
	 *
	 * Only a flat rate ever produced them: the other modes keep their markets in the Markets
	 * option already, and their shipping rows are retained across mode switches, so reading the
	 * rows there would invent markets the merchant never had.
	 *
	 * The audience shape does not matter. A market's country leaves the primary feed because
	 * the primary country list is computed without the countries markets own, not because it
	 * was taken out of the stored list, which an "all countries" audience never consults.
	 *
	 * @return bool
	 */
	private function has_convertible_shipping(): bool {
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return is_array( $mc_settings ) && 'flat' === ( $mc_settings['shipping_rate'] ?? null );
	}

	/**
	 * Stores one market for a country, leaving language and currency to the site defaults
	 * that add_market() applies.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 */
	private function store_market( string $country ): void {
		$feed_label = strtoupper( $country );

		try {
			$id = $this->market_service->generate_market_id( $feed_label );

			// The id is derived from the country, but a market for another country can already
			// be using it, and add_market() would overwrite that market wholesale.
			if ( null !== $this->market_service->get_market( $id ) ) {
				return;
			}

			$this->market_service->add_market(
				$id,
				[
					'country'    => $country,
					'feed_label' => $feed_label,
				]
			);
		} catch ( Throwable $exception ) {
			// One unconvertible country must not abort the upgrade: apply() runs from admin_init
			// and a throw here would leave the version unrecorded, repeating on every page load.
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}

	/**
	 * Returns the countries a stored market already owns, so the migration can run again
	 * without duplicating or overwriting them.
	 *
	 * @return string[]
	 */
	private function countries_with_a_market(): array {
		$countries = [];

		foreach ( $this->market_service->get_markets() as $id => $market ) {
			if ( 'primary' === $id || empty( $market['country'] ) ) {
				continue;
			}

			$countries[] = (string) $market['country'];
		}

		return $countries;
	}

	/**
	 * Builds a comparable signature of a country's stored flat shipping.
	 *
	 * Two countries share a market only when their rate, free-shipping threshold and delivery
	 * window all match, so all four take part in the comparison.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 * @param array  $rates   Rates keyed by country.
	 * @param array  $times   Times keyed by country.
	 *
	 * @return string
	 */
	private function shipping_signature( string $country, array $rates, array $times ): string {
		$rate = $rates[ $country ] ?? [];
		$time = $times[ $country ] ?? [];

		return (string) wp_json_encode(
			[
				'rate'          => isset( $rate['rate'] ) ? (string) $rate['rate'] : null,
				'free_shipping' => $rate['free_shipping_threshold'] ?? null,
				'time'          => isset( $time['time'] ) ? (string) $time['time'] : null,
				'max_time'      => isset( $time['max_time'] ) ? (string) $time['max_time'] : null,
			]
		);
	}
}
