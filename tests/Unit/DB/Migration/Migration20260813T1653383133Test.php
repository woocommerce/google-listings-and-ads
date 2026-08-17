<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migration20260813T1653383133;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20260813T1653383133Test
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB\Migration
 */
class Migration20260813T1653383133Test extends UnitTest {

	/** @var MockObject|TargetAudience */
	protected $target_audience;

	/** @var MockObject|ShippingRateQuery */
	protected $shipping_rate_query;

	/** @var MockObject|ShippingTimeQuery */
	protected $shipping_time_query;

	/** @var MockObject|MarketService */
	protected $market_service;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var Migration20260813T1653383133 */
	protected $migration;

	public function setUp(): void {
		global $wpdb;

		parent::setUp();

		$this->target_audience     = $this->createMock( TargetAudience::class );
		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->shipping_time_query = $this->createMock( ShippingTimeQuery::class );
		$this->market_service      = $this->createMock( MarketService::class );
		$this->options             = $this->createMock( OptionsInterface::class );

		// A flat store with an explicit audience, which is the only shape worth converting.
		$this->set_up_store( 'flat', 'selected' );

		$this->market_service->method( 'generate_market_id' )
			->willReturnCallback(
				function ( string $feed_label ): string {
					return sanitize_title( $feed_label );
				}
			);

		$this->migration = new Migration20260813T1653383133(
			$wpdb,
			$this->target_audience,
			$this->shipping_rate_query,
			$this->shipping_time_query,
			$this->market_service,
			$this->options
		);
	}

	/**
	 * Sets the shipping mode and audience shape.
	 *
	 * @param string $shipping_rate
	 * @param string $location
	 */
	private function set_up_store( string $shipping_rate, string $location ): void {
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get' )->willReturnCallback(
			function ( string $key ) use ( $shipping_rate, $location ) {
				if ( OptionsInterface::MERCHANT_CENTER === $key ) {
					return [ 'shipping_rate' => $shipping_rate ];
				}

				if ( OptionsInterface::TARGET_AUDIENCE === $key ) {
					return [ 'location' => $location ];
				}

				return [];
			}
		);

		$this->rebuild();
	}

	/**
	 * Rebuilds the migration against the current mocks.
	 */
	private function rebuild(): void {
		global $wpdb;

		$this->migration = new Migration20260813T1653383133(
			$wpdb,
			$this->target_audience,
			$this->shipping_rate_query,
			$this->shipping_time_query,
			$this->market_service,
			$this->options
		);
	}

	/**
	 * Sets the shipping tables up.
	 *
	 * @param array $rates Rates keyed by country.
	 * @param array $times Times keyed by country.
	 */
	private function set_up_shipping( array $rates, array $times = [] ): void {
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( $rates );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( $times );
	}

	/**
	 * Builds a rate row as get_all_shipping_rates() reports it.
	 *
	 * @param string     $country
	 * @param string     $rate
	 * @param float|null $threshold
	 *
	 * @return array
	 */
	private function rate( string $country, string $rate, ?float $threshold = null ): array {
		return [
			'country_code'            => $country,
			'currency'                => 'USD',
			'free_shipping_threshold' => $threshold,
			'rate'                    => $rate,
		];
	}

	public function test_applies_to_the_release_that_removes_the_derived_model(): void {
		$this->assertSame( '3.10.0', $this->migration->get_applicable_version() );
	}

	public function test_stores_a_market_for_a_country_whose_rate_differs(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [ 'primary' => [ 'country' => null ] ] );

		// Language and currency are left out: add_market() fills the site defaults.
		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->with(
				'gb',
				[
					'country'    => 'GB',
					'feed_label' => 'GB',
				]
			);

		$this->migration->apply();
	}

	public function test_stores_a_market_when_only_the_delivery_time_differs(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '5.00' ),
			],
			[
				'US' => [
					'time'     => 2,
					'max_time' => 4,
				],
				'GB' => [
					'time'     => 7,
					'max_time' => 9,
				],
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->once() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_stores_a_market_when_only_the_free_shipping_threshold_differs(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '5.00', 75.0 ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->once() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_stores_a_market_when_only_the_maximum_delivery_time_differs(): void {
		// Both halves of the window take part, so a shared minimum is not enough to match.
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '5.00' ),
			],
			[
				'US' => [
					'time'     => 2,
					'max_time' => 5,
				],
				'GB' => [
					'time'     => 2,
					'max_time' => 9,
				],
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->once() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_stores_a_market_for_a_country_that_only_has_a_time_row(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[ 'US' => $this->rate( 'US', '5.00' ) ],
			[
				'US' => [
					'time'     => 2,
					'max_time' => 4,
				],
				'GB' => [
					'time'     => 8,
					'max_time' => 10,
				],
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->with( 'gb', $this->anything() );

		$this->migration->apply();
	}

	public function test_a_country_listed_twice_is_converted_once(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->once() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_leaves_a_market_that_already_owns_the_generated_id(): void {
		// The id comes from the country, but another market can already be using it.
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );
		$this->market_service->method( 'get_market' )->willReturn( [ 'country' => 'DE' ] );

		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_leaves_a_country_whose_shipping_matches_the_main_country(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'CA' => $this->rate( 'CA', '5.00' ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_leaves_a_country_with_no_shipping_row_of_its_own(): void {
		// It inherited the primary's shipping, so it was never its own market.
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA' ] );
		$this->set_up_shipping( [ 'US' => $this->rate( 'US', '5.00' ) ] );
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_skips_a_country_that_already_has_a_stored_market(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [ 'country' => null ],
				'gb-eur'  => [ 'country' => 'GB' ],
			]
		);

		// Matched on country, not id, so a market stored under any id still counts.
		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_running_twice_stores_the_market_once(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);

		// The second pass sees the market the first pass stored.
		$stored = [];
		$this->market_service->method( 'get_markets' )->willReturnCallback(
			function () use ( &$stored ): array {
				return $stored;
			}
		);
		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->willReturnCallback(
				function ( string $id, array $config ) use ( &$stored ): void {
					$stored[ $id ] = $config;
				}
			);

		$this->migration->apply();
		$this->migration->apply();
	}

	public function test_no_op_when_the_store_is_not_on_a_flat_rate(): void {
		// Rate rows survive a mode switch and delivery times are set independently, so reading
		// them outside flat mode would invent markets the merchant never had.
		$this->set_up_store( 'automatic', 'selected' );

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);

		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_converts_for_an_all_countries_audience(): void {
		// Such a store keeps no explicit country list. The primary country list is computed
		// without the countries markets own, so the conversion holds there too.
		$this->set_up_store( 'flat', 'all' );

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);

		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->with( 'gb', $this->callback( fn( $config ) => 'GB' === $config['country'] ) );

		$this->migration->apply();
	}

	public function test_the_stored_market_carries_the_defaults_and_leaves_the_primary_audience(): void {
		// Everything above mocks MarketService, so nothing proves what add_market() actually
		// writes. This drives the real service and reads the option back.
		global $wpdb;

		$stored_options = [
			OptionsInterface::MERCHANT_CENTER => [
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
			OptionsInterface::TARGET_AUDIENCE => [
				'location'  => 'selected',
				'countries' => [ 'US', 'GB' ],
			],
			OptionsInterface::MARKETS         => [],
		];

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->willReturnCallback(
			function ( string $key, $fallback = null ) use ( &$stored_options ) {
				return $stored_options[ $key ] ?? $fallback;
			}
		);
		$options->method( 'update' )->willReturnCallback(
			function ( string $key, $value ) use ( &$stored_options ): bool {
				$stored_options[ $key ] = $value;

				return true;
			}
		);

		$target_audience = $this->createMock( TargetAudience::class );
		$target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );

		$rate_query = $this->createMock( ShippingRateQuery::class );
		$rate_query->method( 'get_all_shipping_rates' )->willReturn(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);
		$rate_query->method( 'get_results' )->willReturn( [] );

		$time_query = $this->createMock( ShippingTimeQuery::class );
		$time_query->method( 'get_all_shipping_times' )->willReturn( [] );
		$time_query->method( 'get_results' )->willReturn( [] );

		$wc = $this->createMock( WC::class );
		$wc->method( 'get_countries' )->willReturn( [ 'GB' => 'United Kingdom (UK)' ] );

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_currencies_enabled_for_language' )->willReturnCallback(
			function ( array $currencies ): array {
				return $currencies;
			}
		);

		$job_repository = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )->willReturnCallback(
			function ( $classname ) {
				switch ( $classname ) {
					case UpdateAllProducts::class:
						return $this->createMock( UpdateAllProducts::class );
					case UpdateShippingSettings::class:
						return $this->createMock( UpdateShippingSettings::class );
					default:
						return null;
				}
			}
		);

		$market_service = new MarketService( $target_audience, $rate_query, $time_query, $wc, $wpml, $job_repository );
		$market_service->set_options_object( $options );

		$migration = new Migration20260813T1653383133( $wpdb, $target_audience, $rate_query, $time_query, $market_service, $options );
		$migration->apply();

		$market = $stored_options[ OptionsInterface::MARKETS ]['gb'] ?? null;

		$this->assertNotNull( $market, wp_json_encode( $stored_options[ OptionsInterface::MARKETS ] ) );
		$this->assertSame( 'GB', $market['country'] );
		$this->assertSame( 'GB', $market['feed_label'] );

		// AC1: the site defaults land, and the country is recorded as having been in primary.
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $market['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $market['currency'] );
		$this->assertTrue( $market['was_in_primary'] );

		// The converted country leaves the primary feed, so it is not listed twice.
		$this->assertSame( [ 'US' ], array_values( $stored_options[ OptionsInterface::TARGET_AUDIENCE ]['countries'] ) );
	}

	public function test_an_all_countries_store_records_the_country_as_having_been_primary(): void {
		// An all-countries audience keeps no explicit country list, so membership has to come
		// from the resolved audience. Drives the real service to prove the recorded flag.
		global $wpdb;

		$stored_options = [
			OptionsInterface::MERCHANT_CENTER => [
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
			OptionsInterface::TARGET_AUDIENCE => [
				'location'  => 'all',
				'countries' => [],
			],
			OptionsInterface::MARKETS         => [],
		];

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->willReturnCallback(
			function ( string $key, $fallback = null ) use ( &$stored_options ) {
				return $stored_options[ $key ] ?? $fallback;
			}
		);
		$options->method( 'update' )->willReturnCallback(
			function ( string $key, $value ) use ( &$stored_options ): bool {
				$stored_options[ $key ] = $value;

				return true;
			}
		);

		$target_audience = $this->createMock( TargetAudience::class );
		$target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );

		$rate_query = $this->createMock( ShippingRateQuery::class );
		$rate_query->method( 'get_all_shipping_rates' )->willReturn(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
			]
		);
		$rate_query->method( 'get_results' )->willReturn( [] );

		$time_query = $this->createMock( ShippingTimeQuery::class );
		$time_query->method( 'get_all_shipping_times' )->willReturn( [] );
		$time_query->method( 'get_results' )->willReturn( [] );

		$wc = $this->createMock( WC::class );
		$wc->method( 'get_countries' )->willReturn( [ 'GB' => 'United Kingdom (UK)' ] );

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_currencies_enabled_for_language' )->willReturnCallback(
			function ( array $currencies ): array {
				return $currencies;
			}
		);

		$job_repository = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )->willReturnCallback(
			function ( $classname ) {
				switch ( $classname ) {
					case UpdateAllProducts::class:
						return $this->createMock( UpdateAllProducts::class );
					case UpdateShippingSettings::class:
						return $this->createMock( UpdateShippingSettings::class );
					default:
						return null;
				}
			}
		);

		$market_service = new MarketService( $target_audience, $rate_query, $time_query, $wc, $wpml, $job_repository );
		$market_service->set_options_object( $options );

		$migration = new Migration20260813T1653383133( $wpdb, $target_audience, $rate_query, $time_query, $market_service, $options );
		$migration->apply();

		$market = $stored_options[ OptionsInterface::MARKETS ]['gb'] ?? null;

		$this->assertNotNull( $market, wp_json_encode( $stored_options[ OptionsInterface::MARKETS ] ) );
		$this->assertSame( 'GB', $market['country'] );
		$this->assertSame( 'GB', $market['feed_label'] );

		// AC1: the site defaults land, and the country is recorded as having been in primary.
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $market['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $market['currency'] );
		$this->assertTrue( $market['was_in_primary'] );

		// The stored list is empty in this mode and stays that way; the country leaves the
		// primary feed because the primary country list excludes what markets own.
		$this->assertSame( [], $stored_options[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
		$this->assertNotContains( 'GB', $market_service->get_primary_market()['countries'] );
	}

	public function test_no_op_when_only_one_country_is_targeted(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_no_op_when_there_is_no_main_target_country(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( '' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );

		$this->market_service->expects( $this->never() )->method( 'add_market' );

		$this->migration->apply();
	}

	public function test_one_rejected_country_does_not_stop_the_others(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB', 'DE' ] );
		$this->set_up_shipping(
			[
				'US' => $this->rate( 'US', '5.00' ),
				'GB' => $this->rate( 'GB', '12.00' ),
				'DE' => $this->rate( 'DE', '9.00' ),
			]
		);
		$this->market_service->method( 'get_markets' )->willReturn( [] );

		$seen = [];
		$this->market_service->expects( $this->exactly( 2 ) )
			->method( 'add_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$seen ): void {
					$seen[] = $id;

					if ( 'gb' === $id ) {
						throw new InvalidValue( 'nope' );
					}
				}
			);

		$this->migration->apply();

		$this->assertSame( [ 'gb', 'de' ], $seen );
	}
}
