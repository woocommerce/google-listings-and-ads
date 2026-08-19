<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedLanguageProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedMarketProductsJob;
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
 * Class MarketServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 */
class MarketServiceTest extends UnitTest {

	/** @var MockObject|TargetAudience */
	protected $target_audience;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MockObject|ShippingRateQuery */
	protected $shipping_rate_query;

	/** @var MockObject|ShippingTimeQuery */
	protected $shipping_time_query;

	/** @var MockObject|WC */
	protected $wc;

	/** @var MockObject|WPML */
	protected $wpml;

	/** @var array<string, string[]> Currencies disabled per language for the wpml stub. */
	protected $disabled_currencies = [];

	/** @var MockObject|JobRepository */
	protected $job_repository;

	/** @var MockObject|CleanupOrphanedMarketProductsJob */
	protected $cleanup_job;

	/** @var MockObject|CleanupOrphanedLanguageProductsJob */
	protected $language_cleanup_job;

	/** @var MockObject|UpdateShippingSettings */
	protected $shipping_settings_job;

	/** @var MockObject|UpdateAllProducts */
	protected $update_all_products_job;

	/** @var MarketService */
	protected $market_service;

	public function setUp(): void {
		parent::setUp();

		$this->target_audience     = $this->createMock( TargetAudience::class );
		$this->options             = $this->createMock( OptionsInterface::class );
		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->shipping_time_query = $this->createMock( ShippingTimeQuery::class );
		$this->wc                  = $this->createMock( WC::class );
		$this->wpml                = $this->createMock( WPML::class );
		$this->job_repository      = $this->createMock( JobRepository::class );

		// A permissive producible-currency list so market validation accepts the
		// currencies the fixtures across this file use. Producibility rejection
		// and get_currencies delegation are covered by tests that construct
		// their own service with a locally configured WPML mock.
		$this->wpml->method( 'get_currencies' )->willReturn(
			[
				[
					'code'   => 'USD',
					'symbol' => '$',
				],
				[
					'code'   => 'EUR',
					'symbol' => '€',
				],
				[
					'code'   => 'GBP',
					'symbol' => '£',
				],
				[
					'code'   => 'CHF',
					'symbol' => 'CHF',
				],
				[
					'code'   => 'JPY',
					'symbol' => '¥',
				],
				[
					'code'   => 'CAD',
					'symbol' => '$',
				],
			]
		);
		$this->cleanup_job             = $this->createMock( CleanupOrphanedMarketProductsJob::class );
		$this->language_cleanup_job    = $this->createMock( CleanupOrphanedLanguageProductsJob::class );
		$this->shipping_settings_job   = $this->createMock( UpdateShippingSettings::class );
		$this->update_all_products_job = $this->createMock( UpdateAllProducts::class );

		$this->job_repository->method( 'get' )
			->willReturnCallback(
				function ( $classname ) {
					switch ( $classname ) {
						case CleanupOrphanedMarketProductsJob::class:
							return $this->cleanup_job;
						case CleanupOrphanedLanguageProductsJob::class:
							return $this->language_cleanup_job;
						case UpdateShippingSettings::class:
							return $this->shipping_settings_job;
						case UpdateAllProducts::class:
							return $this->update_all_products_job;
						default:
							return null;
					}
				}
			);

		// Currencies pass through unless a test disables them via $this->disabled_currencies
		// (mirrors WPML per-language narrowing).
		$this->wpml->method( 'get_currencies_enabled_for_language' )
			->willReturnCallback(
				function ( array $currencies, string $language ): array {
					$disabled = $this->disabled_currencies[ $language ] ?? [];

					return array_values( array_diff( $currencies, $disabled ) );
				}
			);

		$this->market_service = new MarketService(
			$this->target_audience,
			$this->shipping_rate_query,
			$this->shipping_time_query,
			$this->wc,
			$this->wpml,
			$this->job_repository
		);
		$this->market_service->set_options_object( $this->options );
	}

	public function test_get_markets_returns_primary_and_stored_secondary(): void {
		$secondary = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertArrayHasKey( 'primary', $result );
		$this->assertArrayHasKey( 'gb', $result );
		$this->assertSame( 'primary', array_key_first( $result ) );
		$this->assertSame( 'primary', $result['primary']['id'] );
	}

	/**
	 * Regression (GOOWOO-773): a secondary market's stored shipping snapshot must be
	 * replaced by the current global method on read, so no consumer ever sees a stale
	 * value. Here the stored snapshot is automatic/flat but the global rate is manual.
	 */
	public function test_get_markets_overwrites_secondary_shipping_with_global_method(): void {
		$secondary = [
			'fr' => [
				'country'       => 'FR',
				'language'      => [ 'fr' ],
				'currency'      => [ 'EUR' ],
				'feed_label'    => 'FR',
				'shipping_rate' => 'automatic',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $secondary,
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'manual',
					'shipping_time' => 'manual',
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertSame( 'manual', $result['fr']['shipping_rate'] );
		$this->assertSame( 'manual', $result['fr']['shipping_time'] );
		// The primary is composed from the same global option.
		$this->assertSame( 'manual', $result['primary']['shipping_rate'] );
		$this->assertSame( 'manual', $result['primary']['shipping_time'] );
	}

	public function test_get_markets_falls_back_to_default_when_empty(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => null ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'primary', $result );
		$this->assertNull( $result['primary']['country'] );
		$this->assertNull( $result['primary']['feed_label'] );
	}

	public function test_get_markets_strips_stored_primary_key(): void {
		$stored = [
			'primary' => [ 'should' => 'be-ignored' ],
			'gb'      => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $stored ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertSame( 'primary', $result['primary']['id'] );
		$this->assertArrayNotHasKey( 'should', $result['primary'] );
	}

	/**
	 * Regression: legacy/corrupted state could leave a primary-shaped entry
	 * stored under the 'primary' key. The result must always contain exactly
	 * one primary, sourced from the synthesised composition — never the
	 * stored copy — even when iterated by a consumer that flattens to a list.
	 */
	public function test_get_markets_never_returns_duplicate_primary(): void {
		$stored = [
			'primary' => [
				'id'         => 'primary',
				'label'      => 'Primary Market',
				'countries'  => [ 'MU', 'ZW' ],
				'country'    => 'ZW',
				'language'   => [ 'en' ],
				'currency'   => [ 'USD' ],
				'feed_label' => 'ZW',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $stored ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US', 'CA' ] );

		$result = $this->market_service->get_markets();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'primary', $result );

		$primary_ids = array_filter(
			array_column( $result, 'id' ),
			static function ( $id ) {
				return 'primary' === $id;
			}
		);
		$this->assertCount( 1, $primary_ids );

		$this->assertNull( $result['primary']['country'] );
		$this->assertNull( $result['primary']['feed_label'] );
		$this->assertSame( [ 'US', 'CA' ], $result['primary']['countries'] );
	}

	public function test_reset_markets_deletes_target_audience_and_markets_options(): void {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive(
				[ OptionsInterface::TARGET_AUDIENCE ],
				[ OptionsInterface::MARKETS ]
			);

		$this->market_service->reset_markets();
	}

	public function test_get_primary_market_country_and_feed_label_are_null(): void {
		$this->set_up_options_get( [ OptionsInterface::MERCHANT_CENTER => [] ] );
		$this->set_up_primary_market_dependencies(
			'ZW',
			[ 'MU', 'ZW', 'AO', 'CI', 'CM' ]
		);

		$result = $this->market_service->get_primary_market();

		$this->assertNull( $result['country'] );
		$this->assertNull( $result['feed_label'] );
		$this->assertSame( [ 'MU', 'ZW', 'AO', 'CI', 'CM' ], $result['countries'] );
	}

	public function test_get_primary_market_returns_full_response_ready_shape(): void {
		$mc_settings = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
		];

		$this->set_up_options_get( [ OptionsInterface::MERCHANT_CENTER => $mc_settings ] );
		$this->set_up_primary_market_dependencies(
			'US',
			[ 'US', 'CA' ],
			[
				'US' => [
					'country_code'            => 'US',
					'currency'                => 'USD',
					'free_shipping_threshold' => 50.0,
					'rate'                    => '5.00',
				],
			]
		);

		$result = $this->market_service->get_primary_market();

		$this->assertSame( 'primary', $result['id'] );
		$this->assertSame( 'Primary Market', $result['label'] );
		$this->assertSame( [ 'US', 'CA' ], $result['countries'] );
		$this->assertNull( $result['country'] );
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $result['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $result['currency'] );
		$this->assertNull( $result['feed_label'] );
		$this->assertSame( 'flat', $result['shipping_rate'] );
		$this->assertSame( 'flat', $result['shipping_time'] );
		$this->assertSame( 50.0, $result['free_shipping'] );
	}

	public function test_get_primary_market_free_shipping_null_when_unset(): void {
		$this->set_up_options_get( [ OptionsInterface::MERCHANT_CENTER => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_primary_market();

		$this->assertNull( $result['free_shipping'] );
	}

	public function test_get_market_returns_market_by_id(): void {
		$stored = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $stored ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_market( 'gb' );

		$this->assertSame( 'GB', $result['country'] );
		$this->assertSame( 'GB', $result['feed_label'] );
	}

	public function test_get_markets_secondary_country_and_feed_label_are_non_null(): void {
		$stored = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $stored ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US', 'CA' ] );

		$result = $this->market_service->get_markets();

		$this->assertNull( $result['primary']['country'] );
		$this->assertNull( $result['primary']['feed_label'] );
		$this->assertIsString( $result['gb']['country'] );
		$this->assertIsString( $result['gb']['feed_label'] );
		$this->assertSame( 'GB', $result['gb']['country'] );
		$this->assertSame( 'GB', $result['gb']['feed_label'] );
	}

	public function test_get_market_returns_primary_for_primary_id(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_market( 'primary' );

		$this->assertSame( 'primary', $result['id'] );
		$this->assertNull( $result['country'] );
		$this->assertNull( $result['feed_label'] );
	}

	public function test_get_market_returns_null_for_unknown_id(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->assertNull( $this->market_service->get_market( 'nonexistent' ) );
	}

	public function test_add_market_throws_when_id_is_primary(): void {
		$this->expectException( InvalidValue::class );

		$this->market_service->add_market( 'primary', [ 'country' => 'GB' ] );
	}

	public function test_add_market_throws_when_config_missing_required_key(): void {
		$this->expectException( InvalidValue::class );

		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->market_service->add_market(
			'gb',
			[
				'language' => [ 'en' ],
				'currency' => [ 'GBP' ],
			]
		);
	}

	public function test_add_market_throws_when_currency_not_enabled_for_any_language(): void {
		$this->disabled_currencies['fr'] = [ 'GBP' ];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market(
			'fr',
			[
				'country'    => 'FR',
				'feed_label' => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR', 'GBP' ],
			]
		);
	}

	public function test_add_market_normalises_currency_case_before_enablement_check(): void {
		// Regression: a lowercase currency must be uppercased before the enablement check so it is
		// matched against WCML's uppercase codes, matching feed generation. Otherwise a disabled
		// currency slips past validation and is left saved but unable to ever produce a feed.
		$this->disabled_currencies['fr'] = [ 'GBP' ];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market(
			'fr',
			[
				'country'    => 'FR',
				'feed_label' => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'eur', 'gbp' ],
			]
		);
	}

	public function test_get_market_currencies_for_language_narrows_to_enabled(): void {
		// Both gates apply: a currency must be convertible and enabled for the language.
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );
		$this->disabled_currencies['fr'] = [ 'GBP' ];

		$market = [ 'currency' => [ 'EUR', 'GBP' ] ];

		$this->assertSame( [ 'EUR' ], $this->market_service->get_market_currencies_for_language( $market, 'fr' ) );
		$this->assertSame( [ 'EUR', 'GBP' ], $this->market_service->get_market_currencies_for_language( $market, 'de' ) );
	}

	public function test_add_market_persists_and_removes_country_from_target_audience(): void {
		$config = [
			'country'    => 'GB',
			'language'   => [ 'en' ],
			'currency'   => [ 'GBP' ],
			'feed_label' => 'GB',
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US', 'GB', 'CA' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'gb', $config );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$stored_gb = $update_calls[ OptionsInterface::MARKETS ]['gb'];
		$this->assertSame( 'GB', $stored_gb['country'] );
		$this->assertSame( [ 'en' ], $stored_gb['language'] );
		$this->assertSame( [ 'GBP' ], $stored_gb['currency'] );
		$this->assertSame( 'GB', $stored_gb['feed_label'] );
		$this->assertSame( 'flat', $stored_gb['shipping_rate'] );
		$this->assertSame( 'flat', $stored_gb['shipping_time'] );

		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
		$this->assertSame( [ 'US', 'CA' ], $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
		$this->assertTrue( $update_calls[ OptionsInterface::MARKETS ]['gb']['was_in_primary'] );
	}

	public function test_add_market_flat_restores_country_seeds_shipping_and_schedules_jobs(): void {
		$rate_rows = [
			[
				'id'       => 1,
				'country'  => 'MU',
				'currency' => 'USD',
				'rate'     => '77',
				'options'  => [ 'free_shipping_threshold' => 44.0 ],
			],
		];
		$time_rows = [
			[
				'id'       => 1,
				'country'  => 'MU',
				'time'     => '7',
				'max_time' => '14',
			],
		];

		$this->set_up_flat_market_dependencies( [ 'MU' ], $rate_rows, $time_rows );

		$target_countries = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$target_countries ) {
					if ( OptionsInterface::TARGET_AUDIENCE === $key ) {
						$target_countries = $value['countries'] ?? null;
					}
					return true;
				}
			);

		$this->update_all_products_job->expects( $this->once() )->method( 'schedule' );
		$this->shipping_settings_job->expects( $this->once() )->method( 'schedule' );

		$fired_count   = 0;
		$captured_id   = null;
		$captured_hook = null;
		add_action(
			'woocommerce_gla_market_added',
			function ( $id, $market ) use ( &$fired_count, &$captured_id, &$captured_hook ) {
				++$fired_count;
				$captured_id   = $id;
				$captured_hook = $market;
			},
			10,
			2
		);

		$this->market_service->add_market( 'cm', [ 'country' => 'CM' ] );

		// The country joins the target audience: a flat market is derived from a targeted country.
		$this->assertSame( [ 'MU', 'CM' ], $target_countries );

		// A flat market is never persisted, so its shipping rows are seeded from the primary
		// market instead, giving the merchant editable values to start from.
		$this->assertSame(
			[
				'country'  => 'CM',
				'currency' => 'USD',
				'rate'     => '77',
				'options'  => [ 'free_shipping_threshold' => 44.0 ],
			],
			array_intersect_key( $rate_rows[1], array_flip( [ 'country', 'currency', 'rate', 'options' ] ) )
		);
		$this->assertSame(
			[
				'country'  => 'CM',
				'time'     => '7',
				'max_time' => '14',
			],
			array_intersect_key( $time_rows[1], array_flip( [ 'country', 'time', 'max_time' ] ) )
		);

		// The action reports the derived market, not the submitted config.
		$this->assertSame( 1, $fired_count );
		$this->assertSame( 'cm', $captured_id );
		$this->assertSame( 'cm', $captured_hook['id'] );
		$this->assertSame( 'CM', $captured_hook['feed_label'] );
		$this->assertSame( 'flat', $captured_hook['shipping_rate'] );
	}

	public function test_add_market_flat_returns_full_market_seeded_from_primary(): void {
		$rate_rows = [
			[
				'id'       => 1,
				'country'  => 'MU',
				'currency' => 'USD',
				'rate'     => '77',
				'options'  => [ 'free_shipping_threshold' => 44.0 ],
			],
		];
		$time_rows = [
			[
				'id'       => 1,
				'country'  => 'MU',
				'time'     => '7',
				'max_time' => '14',
			],
		];

		$this->set_up_flat_market_dependencies( [ 'MU', 'CM' ], $rate_rows, $time_rows );

		$created = $this->market_service->add_market( 'cm', [ 'country' => 'CM' ] );
		$primary = $this->market_service->get_primary_market();

		// Complete without the merchant having saved any shipping values of their own,
		// with the seeded values copied from the primary market.
		$this->assertSame( 'cm', $created['id'] );
		$this->assertSame( 'CM', $created['country'] );
		$this->assertSame( [ 'CM' ], $created['countries'] );
		$this->assertSame( 'Cameroon', $created['label'] );
		$this->assertSame( 'CM', $created['feed_label'] );
		$this->assertSame( 'flat', $created['shipping_rate'] );
		$this->assertSame( 'flat', $created['shipping_time'] );
		$this->assertSame( 44.0, $created['free_shipping'] );

		// Flat markets carry no locale of their own, so they use the site defaults.
		$this->assertSame( $primary['language'], $created['language'] );
		$this->assertSame( $primary['currency'], $created['currency'] );
	}

	public function test_add_market_flat_keeps_the_countrys_own_shipping_rows(): void {
		$rate_rows = [
			[
				'id'       => 1,
				'country'  => 'MU',
				'currency' => 'USD',
				'rate'     => '77',
				'options'  => [ 'free_shipping_threshold' => 44.0 ],
			],
			[
				'id'       => 2,
				'country'  => 'CM',
				'currency' => 'USD',
				'rate'     => '55',
				'options'  => [ 'free_shipping_threshold' => 10.0 ],
			],
		];
		$time_rows = [
			[
				'id'       => 1,
				'country'  => 'MU',
				'time'     => '7',
				'max_time' => '14',
			],
			[
				'id'       => 2,
				'country'  => 'CM',
				'time'     => '1',
				'max_time' => '5',
			],
		];

		$this->set_up_flat_market_dependencies( [ 'MU', 'CM' ], $rate_rows, $time_rows );

		// Rows already exist for the country, so they are never written over.
		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_time_query->expects( $this->never() )->method( 'update' );

		$created = $this->market_service->add_market( 'cm', [ 'country' => 'CM' ] );

		// The response reports the country's own values, not the primary market's.
		$this->assertSame( 10.0, $created['free_shipping'] );
		$this->assertSame( 'CM', $created['feed_label'] );

		// Nothing was seeded on top of the existing rows.
		$this->assertCount( 2, $rate_rows );
		$this->assertCount( 2, $time_rows );
		$this->assertSame( '55', $rate_rows[1]['rate'] );
		$this->assertSame( '1', $time_rows[1]['time'] );
	}

	public function test_add_market_flat_throws_when_country_is_empty(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market( 'cm', [ 'country' => '' ] );
	}

	public function test_flat_derives_country_with_distinct_shipping_as_secondary_market(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'MU', 'ZW', 'FR', 'CM' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'MU', 'ZW', 'FR', 'CM' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'MU' );
		$this->wc->method( 'get_countries' )->willReturn( [ 'CM' => 'Cameroon' ] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn(
			[
				'MU' => [
					'free_shipping_threshold' => 44.0,
					'rate'                    => '77',
				],
				'ZW' => [
					'free_shipping_threshold' => 44.0,
					'rate'                    => '77',
				],
				'FR' => [
					'free_shipping_threshold' => 44.0,
					'rate'                    => '77',
				],
				'CM' => [
					'free_shipping_threshold' => 10.0,
					'rate'                    => '55',
				],
			]
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'MU' => [
					'time'     => '7',
					'max_time' => '14',
				],
				'ZW' => [
					'time'     => '7',
					'max_time' => '14',
				],
				'FR' => [
					'time'     => '7',
					'max_time' => '14',
				],
				'CM' => [
					'time'     => '1',
					'max_time' => '5',
				],
			]
		);

		// Nothing is persisted: the markets are derived live from the shipping tables.
		$this->options->expects( $this->never() )->method( 'update' );

		$markets = $this->market_service->get_markets();

		// Only Cameroon (distinct rate/time) surfaces as its own editable secondary market.
		$this->assertSame( [ 'primary', 'cm' ], array_keys( $markets ) );

		$cm = $markets['cm'];
		$this->assertSame( 'CM', $cm['country'] );
		$this->assertSame( 'CM', $cm['feed_label'] );
		$this->assertSame( 'flat', $cm['shipping_rate'] );
		$this->assertSame( 'flat', $cm['shipping_time'] );
		$this->assertSame( 10.0, $cm['free_shipping'] );

		// The countries sharing the main country's shipping stay in the primary feed.
		$this->assertSame( [ 'MU', 'ZW', 'FR' ], $markets['primary']['countries'] );
	}

	public function test_flat_no_secondary_when_all_countries_share_shipping(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'MU', 'ZW', 'FR' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'MU', 'ZW', 'FR' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'MU' );
		$this->wc->method( 'get_countries' )->willReturn( [] );
		$shared_rate = [
			'MU' => [
				'free_shipping_threshold' => 44.0,
				'rate'                    => '77',
			],
			'ZW' => [
				'free_shipping_threshold' => 44.0,
				'rate'                    => '77',
			],
			'FR' => [
				'free_shipping_threshold' => 44.0,
				'rate'                    => '77',
			],
		];
		$shared_time = [
			'MU' => [
				'time'     => '7',
				'max_time' => '14',
			],
			'ZW' => [
				'time'     => '7',
				'max_time' => '14',
			],
			'FR' => [
				'time'     => '7',
				'max_time' => '14',
			],
		];
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( $shared_rate );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( $shared_time );

		$markets = $this->market_service->get_markets();

		// Every country shares the main country's shipping, so there are no secondary markets.
		$this->assertSame( [ 'primary' ], array_keys( $markets ) );
		$this->assertSame( [ 'MU', 'ZW', 'FR' ], $markets['primary']['countries'] );
	}

	public function test_flat_derived_market_deleted_removes_country_and_shipping_rows(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'MU', 'CM' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'MU', 'CM' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'MU' );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn(
			[
				'MU' => [ 'rate' => '77' ],
				'CM' => [ 'rate' => '55' ],
			]
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'MU' => [
					'time'     => '7',
					'max_time' => '14',
				],
				'CM' => [
					'time'     => '1',
					'max_time' => '5',
				],
			]
		);

		$removed_country = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$removed_country ) {
					if ( OptionsInterface::TARGET_AUDIENCE === $key ) {
						$removed_country = $value['countries'] ?? null;
					}
					return true;
				}
			);

		// Deleting the derived Cameroon market drops its shipping rows entirely.
		$this->shipping_rate_query->expects( $this->once() )->method( 'delete' )->with( 'country', 'CM' );
		$this->shipping_time_query->expects( $this->once() )->method( 'delete' )->with( 'country', 'CM' );

		$this->market_service->delete_market( 'cm' );

		// ...and removes it from the target audience.
		$this->assertSame( [ 'MU' ], $removed_country );
	}

	public function test_non_flat_uses_stored_secondary_markets_not_derived(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// Automatic mode uses the stored markets, not a shipping-table derivation, so the
		// per-country shipping-time query (used only by derivation) is never consulted.
		$this->shipping_time_query->expects( $this->never() )->method( 'get_all_shipping_times' );

		$markets = $this->market_service->get_markets();

		$this->assertSame( [ 'primary', 'gb' ], array_keys( $markets ) );
		$this->assertSame( 'GB', $markets['gb']['country'] );
	}

	public function test_flat_mode_reconciles_orphaned_stored_markets_back_into_the_audience(): void {
		// A market persisted under automatic/manual mode (its country removed from the audience)
		// would be orphaned once the store is flat: invisible, undeletable, and unsynced. Reading
		// markets in flat mode must fold its country back into the audience and clear the stale entry.
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->wc->method( 'get_countries' )->willReturn( [] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [ 'US' => [ 'rate' => '5' ] ] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => '2',
					'max_time' => '4',
				],
			]
		);

		$updates = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$updates ) {
					$updates[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->get_markets();

		// The stale stored market is cleared...
		$this->assertArrayHasKey( OptionsInterface::MARKETS, $updates );
		$this->assertSame( [], $updates[ OptionsInterface::MARKETS ] );

		// ...and its country is folded back into the target audience so it stays targeted.
		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $updates );
		$this->assertContains( 'GB', $updates[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
	}

	public function test_flat_mode_reconciliation_schedules_cleanup_update_and_shipping_sync(): void {
		// A store that synced products under the stored markets' feed labels and then switched to
		// flat is left with stale MC offers under those labels. Reconciliation must clean them up,
		// resync the restored countries, and refresh shipping — mirroring delete_market()'s flat path.
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->wc->method( 'get_countries' )->willReturn( [] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [ 'US' => [ 'rate' => '5' ] ] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [ 'US' => [ 'time' => '2' ] ] );
		$this->options->method( 'update' )->willReturn( true );

		// Cleanup targets the removed market's feed label variants, captured before the option clear.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP' ] ] );

		// The restored country must be re-synced under its current flat feed label.
		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		// Global shipping method is flat/flat (syncable) → a shipping settings sync is scheduled.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->get_markets();
	}

	public function test_flat_mode_reconciliation_cleans_up_variants_of_every_stored_market(): void {
		// Two stored markets with distinct labels/languages/currencies: the single cleanup job must
		// receive the accumulated feed label variants from both, not just one.
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
					'de' => [
						'country'    => 'DE',
						'language'   => [ 'de' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'DE',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->wc->method( 'get_countries' )->willReturn( [] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [ 'US' => [ 'rate' => '5' ] ] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [ 'US' => [ 'time' => '2' ] ] );
		$this->options->method( 'update' )->willReturn( true );

		// Both markets' variants, in stored order: GB (base + EN/GBP) then DE (base + DE/EUR).
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP', 'DE', 'DE-DE-EUR' ] ] );

		$this->market_service->get_markets();
	}

	public function test_flat_mode_reconciliation_with_no_stored_markets_performs_no_writes_or_jobs(): void {
		// With nothing stored the read path must stay a pure read: no option writes, no jobs.
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->wc->method( 'get_countries' )->willReturn( [] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [] );

		$this->options->expects( $this->never() )->method( 'update' );
		$this->cleanup_job->expects( $this->never() )->method( 'schedule' );
		$this->update_all_products_job->expects( $this->never() )->method( 'schedule' );
		$this->shipping_settings_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->get_markets();
	}

	public function test_add_market_records_was_in_primary_false_when_country_was_not_targeted(): void {
		$config = [
			'country'    => 'DE',
			'language'   => [ 'de' ],
			'currency'   => [ 'EUR' ],
			'feed_label' => 'DE',
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US', 'GB' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'de', $config );

		$this->assertFalse( $update_calls[ OptionsInterface::MARKETS ]['de']['was_in_primary'] );
	}

	public function test_add_market_country_removal_is_idempotent(): void {
		$config = [
			'country'    => 'DE',
			'language'   => [ 'de' ],
			'currency'   => [ 'EUR' ],
			'feed_label' => 'DE',
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US', 'GB' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'de', $config );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$this->assertArrayNotHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
	}

	public function test_update_market_primary_fans_out_to_merchant_center(): void {
		$existing_mc = [
			'shipping_rate' => 'automatic',
			'shipping_time' => 'automatic',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => $existing_mc,
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			]
		);

		$this->assertArrayHasKey( OptionsInterface::MERCHANT_CENTER, $update_calls );
		$this->assertSame( 'flat', $update_calls[ OptionsInterface::MERCHANT_CENTER ]['shipping_rate'] );
		$this->assertSame( 'flat', $update_calls[ OptionsInterface::MERCHANT_CENTER ]['shipping_time'] );

		$this->assertArrayNotHasKey( OptionsInterface::MARKETS, $update_calls );
	}

	public function test_update_market_primary_fans_out_countries_to_target_audience(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[ 'countries' => [ 'US', 'CA' ] ]
		);

		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
		$this->assertSame( [ 'US', 'CA' ], $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
	}

	public function test_update_market_primary_flat_mode_reintroduces_derived_secondary_countries(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US', 'CA', 'MX' ] ],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA', 'MX' ] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn(
			[
				'US' => [
					'free_shipping_threshold' => 50.0,
					'rate'                    => '5',
				],
				'MX' => [
					'free_shipping_threshold' => 20.0,
					'rate'                    => '15',
				],
			]
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => '3',
					'max_time' => '5',
				],
				'MX' => [
					'time'     => '7',
					'max_time' => '14',
				],
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		// Mirrors what the frontend now submits: the primary market's already-filtered
		// countries (MX excluded, since it's surfaced as its own derived secondary market).
		$this->market_service->update_market(
			'primary',
			[ 'countries' => [ 'US', 'CA' ] ]
		);

		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
		$this->assertSame(
			[ 'US', 'CA', 'MX' ],
			$update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries']
		);
	}

	public function test_update_market_primary_returns_composed_market(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'flat' ],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		$this->options->method( 'update' )->willReturn( true );

		$result = $this->market_service->update_market(
			'primary',
			[ 'shipping_rate' => 'flat' ]
		);

		$this->assertSame( 'primary', $result['id'] );
		$this->assertArrayHasKey( 'countries', $result );
	}

	public function test_update_market_primary_persists_multiple_languages_and_currencies(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[
				'language' => [ 'en', 'fr' ],
				'currency' => [ 'USD', 'EUR' ],
			]
		);

		$this->assertArrayHasKey( OptionsInterface::MERCHANT_CENTER, $update_calls );
		$this->assertSame( [ 'en', 'fr' ], $update_calls[ OptionsInterface::MERCHANT_CENTER ]['language'] );
		$this->assertSame( [ 'USD', 'EUR' ], $update_calls[ OptionsInterface::MERCHANT_CENTER ]['currency'] );
	}

	public function test_update_market_primary_deduplicates_languages_and_currencies(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[
				'language' => [ 'en', 'fr', 'en' ],
				'currency' => [ 'USD', 'USD', 'EUR' ],
			]
		);

		$this->assertSame( [ 'en', 'fr' ], $update_calls[ OptionsInterface::MERCHANT_CENTER ]['language'] );
		$this->assertSame( [ 'USD', 'EUR' ], $update_calls[ OptionsInterface::MERCHANT_CENTER ]['currency'] );
	}

	public function test_update_market_primary_partial_update_preserves_other_keys(): void {
		$existing_mc = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'language'      => [ 'en', 'fr' ],
			'currency'      => [ 'USD', 'EUR' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => $existing_mc,
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[ 'shipping_rate' => 'automatic' ]
		);

		$persisted = $update_calls[ OptionsInterface::MERCHANT_CENTER ];
		$this->assertSame( 'automatic', $persisted['shipping_rate'] );
		$this->assertSame( [ 'en', 'fr' ], $persisted['language'] );
		$this->assertSame( [ 'USD', 'EUR' ], $persisted['currency'] );
	}

	public function test_update_market_primary_language_currency_update_preserves_shipping(): void {
		$existing_mc = [
			'shipping_rate' => 'automatic',
			'shipping_time' => 'flat',
			'language'      => [ 'en' ],
			'currency'      => [ 'USD' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => $existing_mc,
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[
				'language' => [ 'en', 'fr' ],
				'currency' => [ 'USD', 'EUR' ],
			]
		);

		$persisted = $update_calls[ OptionsInterface::MERCHANT_CENTER ];
		$this->assertSame( [ 'en', 'fr' ], $persisted['language'] );
		$this->assertSame( [ 'USD', 'EUR' ], $persisted['currency'] );
		$this->assertSame( 'automatic', $persisted['shipping_rate'] );
		$this->assertSame( 'flat', $persisted['shipping_time'] );
	}

	public function test_update_market_primary_persists_empty_language_and_currency_arrays(): void {
		$existing_mc = [
			'shipping_rate' => 'flat',
			'language'      => [ 'en', 'fr' ],
			'currency'      => [ 'USD', 'EUR' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => $existing_mc,
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->update_market(
			'primary',
			[
				'language' => [],
				'currency' => [],
			]
		);

		$persisted = $update_calls[ OptionsInterface::MERCHANT_CENTER ];
		$this->assertSame( [], $persisted['language'] );
		$this->assertSame( [], $persisted['currency'] );
		$this->assertSame( 'flat', $persisted['shipping_rate'] );
	}

	public function test_update_market_primary_rejects_non_array_language(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'The value of language must be of type array.' );

		$this->market_service->update_market(
			'primary',
			[ 'language' => 'en' ]
		);
	}

	public function test_update_market_primary_rejects_non_array_currency(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'The value of currency must be of type array.' );

		$this->market_service->update_market(
			'primary',
			[ 'currency' => 'USD' ]
		);
	}

	public function test_get_primary_market_returns_stored_language_and_currency_when_set(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en', 'fr' ],
					'currency' => [ 'USD', 'EUR' ],
				],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_primary_market();

		$this->assertSame( [ 'en', 'fr' ], $result['language'] );
		$this->assertSame( [ 'USD', 'EUR' ], $result['currency'] );
	}

	public function test_get_primary_market_falls_back_to_defaults_when_stored_value_invalid(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => 'en',
					'currency' => 'USD',
				],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_primary_market();

		$this->assertIsArray( $result['language'] );
		$this->assertIsArray( $result['currency'] );
		$this->assertNotSame( 'en', $result['language'] );
		$this->assertNotSame( 'USD', $result['currency'] );
	}

	public function test_update_market_secondary_persists_supplied_currency_verbatim(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$persisted = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$persisted ) {
					if ( OptionsInterface::MARKETS === $key ) {
						$persisted = $value;
					}
					return true;
				}
			);

		$this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );

		$this->assertSame( [ 'EUR' ], $persisted['gb']['currency'] );
		$this->assertSame( 'GB', $persisted['gb']['country'] );
	}

	public function test_update_market_preserves_existing_language_when_omitted(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en', 'de' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$persisted = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$persisted ) {
					if ( OptionsInterface::MARKETS === $key ) {
						$persisted = $value;
					}
					return true;
				}
			);

		$this->market_service->update_market( 'gb', [ 'shipping_rate' => 'flat' ] );

		$this->assertSame( [ 'en', 'de' ], $persisted['gb']['language'] );
	}

	public function test_update_market_saves_supplied_language_verbatim(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$persisted = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$persisted ) {
					if ( OptionsInterface::MARKETS === $key ) {
						$persisted = $value;
					}
					return true;
				}
			);

		$this->market_service->update_market( 'gb', [ 'language' => [ 'fr', 'de' ] ] );

		$this->assertSame( [ 'fr', 'de' ], $persisted['gb']['language'] );
	}

	public function test_update_market_secondary_validates_merged_config(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->update_market( 'gb', [ 'country' => '' ] );
	}

	/**
	 * The Edit Market screen submits shipping_rate/shipping_time on every save, but
	 * a secondary market does not own a shipping method — it is global. Those params
	 * must be dropped (not persisted, not error), while the market's other fields
	 * still save, and the returned market reflects the global shipping method.
	 */
	public function test_update_market_secondary_drops_shipping_params_but_saves_other_fields(): void {
		// Stored locale values are only read back verbatim while a multilingual
		// integration is active; without one the read boundary masks them to
		// the site locale, which would hide the saved currency below.
		$this->wpml->method( 'is_active' )->willReturn( true );

		// A multilingual market with a custom currency is an automatic/manual concept
		// (flat markets carry no locale of their own), so this uses the persisted path.
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'automatic',
			],
		];

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->update_market(
			'gb',
			[
				'shipping_rate' => 'manual',
				'shipping_time' => 'manual',
				'currency'      => [ 'EUR' ],
			]
		);

		// The submitted shipping params are dropped; the result reflects the global method.
		$this->assertSame( 'automatic', $result['shipping_rate'] );
		$this->assertSame( 'flat', $result['shipping_time'] );
		// The non-shipping field still saved.
		$this->assertSame( [ 'EUR' ], $result['currency'] );
		$this->assertSame( 'GB', $result['country'] );
	}

	/**
	 * A shipping-only edit (the Edit Market screen echoing the global method back on
	 * save, while the stored snapshot is stale) must be a no-op: no shipping sync is
	 * scheduled and the stored snapshot is left untouched. This is the exact scenario
	 * the ticket names, and locks in both halves of the drop behaviour.
	 */
	public function test_update_market_secondary_shipping_only_edit_does_not_schedule_shipping_sync(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				// Stale snapshot from before the merchant switched the global method.
				'shipping_rate' => 'automatic',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'manual',
					'shipping_time' => 'manual',
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		// The Edit Market screen echoes the global method back on save.
		$this->market_service->update_market(
			'gb',
			[
				'shipping_rate' => 'manual',
				'shipping_time' => 'manual',
			]
		);

		// The submitted params were dropped, not persisted: the stored snapshot is untouched.
		$this->assertSame( 'automatic', $update_calls[ OptionsInterface::MARKETS ]['gb']['shipping_rate'] );
		$this->assertSame( 'flat', $update_calls[ OptionsInterface::MARKETS ]['gb']['shipping_time'] );
	}

	public function test_update_market_secondary_returns_updated_market(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );

		$this->assertIsArray( $result );
		$this->assertSame( 'GB', $result['country'] );
	}

	public function test_update_market_schedules_cleanup_when_feed_label_changes(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP' ] ] );

		// feed_label rename alone does not touch country/currency/shipping_rate/shipping_time.
		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'feed_label' => 'GB-PROMO' ] );
	}

	public function test_update_market_schedules_cleanup_when_currency_changes(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// The derived label carries the currency, so a currency change re-labels
		// the market's entries and the old label's entries become orphans.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP' ] ] );

		$this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );
	}

	public function test_update_market_cleanup_covers_removed_currency_across_all_languages(): void {
		// A multi-language/currency market is a multilingual concept, so its feeds keep the suffix.
		$this->wpml->method( 'is_active' )->willReturn( true );

		$existing = [
			'de' => [
				'country'    => 'DE',
				'language'   => [ 'en', 'de' ],
				'currency'   => [ 'EUR', 'USD' ],
				'feed_label' => 'DE',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// Dropping USD orphans every USD variant across both languages (plus base and currency-only
		// labels); the surviving EUR labels are not cleaned up.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'feed_labels' => [
						'DE',
						'DE-EN-USD',
						'DE-DE-USD',
					],
				]
			);

		$this->market_service->update_market( 'de', [ 'currency' => [ 'EUR' ] ] );
	}

	public function test_update_market_does_not_schedule_cleanup_when_only_shipping_fields_change(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// Country and shipping fields play no part in the derived labels,
		// so no entries are orphaned by this update.
		$this->cleanup_job->expects( $this->never() )
			->method( 'schedule' );

		// Shipping-relevant fields (country, shipping_rate, shipping_time) DID change.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market(
			'gb',
			[
				'country'       => 'IE',
				'shipping_rate' => 'automatic',
				'shipping_time' => 'automatic',
			]
		);
	}

	public function test_update_market_schedules_cleanup_when_language_changes(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// The derived label carries the language, so replacing the language
		// orphans the old language's label; the new label is left alone.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP' ] ] );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'ga' ] ] );
	}

	public function test_add_market_does_not_schedule_shipping_sync_when_global_is_manual(): void {
		$config = [
			'country'    => 'DE',
			'language'   => [ 'de' ],
			'currency'   => [ 'EUR' ],
			'feed_label' => 'DE',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'manual',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		// Global rate is manual → nothing to sync, regardless of the stored snapshot.
		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->add_market( 'de', $config );
	}

	public function test_update_market_does_not_schedule_shipping_sync_when_only_language_changes(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'en', 'cy' ] ] );
	}

	public function test_delete_market_does_not_schedule_shipping_sync_when_global_is_manual(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				// Stale snapshot says non-manual, but the global rate below is manual.
				'shipping_rate' => 'automatic',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'manual',
					'shipping_time' => 'flat',
				],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		// Global rate is manual → no sync, even though the deleted snapshot was automatic.
		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'gb' );
	}

	/**
	 * Inverse direction: the deleted market's stale snapshot says `manual`, but the
	 * global rate is `flat`, so Google must still be notified after deletion.
	 */
	public function test_delete_flat_derived_market_schedules_shipping_sync(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US', 'GB' ] ],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		// GB has a distinct rate, so it surfaces as the derived 'gb' secondary market.
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn(
			[
				'US' => [ 'rate' => '5' ],
				'GB' => [ 'rate' => '10' ],
			]
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => '2',
					'max_time' => '4',
				],
				'GB' => [
					'time'     => '2',
					'max_time' => '4',
				],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		// Global shipping method is flat/flat (syncable) → deleting schedules a shipping sync.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'gb' );
	}

	public function test_add_market_secondary_schedules_update_all_products(): void {
		$config = [
			'country'    => 'GB',
			'language'   => [ 'en' ],
			'currency'   => [ 'GBP' ],
			'feed_label' => 'GB',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->add_market( 'gb', $config );
	}

	public function test_add_market_primary_does_not_schedule_update_all_products(): void {
		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market( 'primary', [] );
	}

	public function test_add_market_invalid_config_does_not_schedule_update_all_products(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
			]
		);

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'feed_label' => '',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
			]
		);
	}

	public function test_update_market_secondary_schedules_update_all_products_when_country_differs(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'country' => 'IE' ] );
	}

	public function test_update_market_primary_schedules_update_all_products_when_target_audience_countries_change(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market(
			'primary',
			[ 'countries' => [ 'US', 'CA' ] ]
		);
	}

	public function test_update_market_primary_schedules_update_all_products_when_target_audience_countries_reordered(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US', 'GB' ] ],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US', 'GB' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market(
			'primary',
			[ 'countries' => [ 'GB', 'US' ] ]
		);
	}

	public function test_update_market_secondary_schedules_update_all_products_when_feed_label_differs(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'feed_label' => 'GB-PROMO' ] );
	}

	public function test_update_market_secondary_schedules_update_all_products_when_language_differs(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'en', 'cy' ] ] );
	}

	public function test_update_market_secondary_schedules_update_all_products_when_currency_differs(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );
	}

	public function test_update_market_does_not_schedule_update_all_products_when_only_shipping_rate_differs(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'shipping_rate' => 'automatic' ] );
	}

	public function test_update_market_does_not_schedule_update_all_products_when_only_shipping_time_differs(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'shipping_time' => 'automatic' ] );
	}

	public function test_update_market_does_not_schedule_update_all_products_when_no_fields_differ(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [] );
	}

	public function test_update_market_does_not_schedule_update_all_products_when_language_reordered(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en', 'cy' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'cy', 'en' ] ] );
	}

	public function test_update_market_does_not_schedule_update_all_products_when_currency_reordered(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP', 'EUR' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR', 'GBP' ] ] );
	}

	public function test_delete_market_secondary_schedules_update_all_products(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->options->method( 'update' )->willReturn( true );
		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'gb' );
	}

	public function test_delete_market_primary_does_not_schedule_update_all_products(): void {
		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->expectException( InvalidValue::class );

		$this->market_service->delete_market( 'primary' );
	}

	public function test_delete_market_unknown_id_does_not_schedule_update_all_products(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'unknown' );
	}

	public function test_add_market_does_not_schedule_cleanup(): void {
		$config = [
			'country'    => 'DE',
			'language'   => [ 'de' ],
			'currency'   => [ 'EUR' ],
			'feed_label' => 'DE',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		// Flat mode, so the created market is completed from the shipping tables.
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [] );

		$this->cleanup_job->expects( $this->never() )
			->method( 'schedule' );

		// Global shipping method is flat/flat (syncable) → shipping sync IS scheduled.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->add_market( 'de', $config );
	}

	public function test_delete_market_throws_when_id_is_primary(): void {
		$this->expectException( InvalidValue::class );

		$this->market_service->delete_market( 'primary' );
	}

	public function test_delete_market_removes_and_restores_country_to_target_audience(): void {
		$existing = [
			'us' => [
				'country'        => 'US',
				'language'       => [ 'en' ],
				'currency'       => [ 'USD' ],
				'feed_label'     => 'US',
				'was_in_primary' => true,
			],
			'gb' => [
				'country'        => 'GB',
				'language'       => [ 'en' ],
				'currency'       => [ 'GBP' ],
				'feed_label'     => 'GB',
				'was_in_primary' => true,
			],
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'CA' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->market_service->delete_market( 'us' );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$this->assertArrayNotHasKey( 'us', $update_calls[ OptionsInterface::MARKETS ] );
		$this->assertArrayHasKey( 'gb', $update_calls[ OptionsInterface::MARKETS ] );

		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
		$this->assertContains( 'US', $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
		$this->assertContains( 'CA', $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
	}

	public function test_delete_market_never_in_primary_removes_shipping_rows_and_leaves_audience(): void {
		$existing = [
			'gb' => [
				'country'        => 'GB',
				'language'       => [ 'en' ],
				'currency'       => [ 'GBP' ],
				'feed_label'     => 'GB',
				'was_in_primary' => false,
			],
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'GB' );
		$this->shipping_time_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'GB' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'insert' );

		$this->market_service->delete_market( 'gb' );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$this->assertArrayNotHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
	}

	public function test_delete_market_missing_was_in_primary_flag_defaults_to_removal(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'GB' );
		$this->shipping_time_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'GB' );

		$this->market_service->delete_market( 'gb' );

		$this->assertArrayNotHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
	}

	public function test_delete_market_schedules_cleanup_with_feed_label(): void {
		// Custom language/currency markets are an automatic/manual concept (flat markets
		// carry no locale of their own), so this exercises the persisted delete path.
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'automatic',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP' ] ] );

		// Global shipping method is automatic/flat (syncable) → shipping sync also scheduled.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'gb' );
	}

	public function test_delete_market_schedules_cleanup_across_all_language_currency_variants(): void {
		// Multi-language/currency markets are an automatic/manual concept, so this
		// exercises the persisted delete path. They are multilingual, so feeds keep the suffix.
		$this->wpml->method( 'is_active' )->willReturn( true );

		$existing = [
			'de' => [
				'country'       => 'DE',
				'language'      => [ 'en', 'de' ],
				'currency'      => [ 'EUR', 'USD' ],
				'feed_label'    => 'DE',
				'shipping_rate' => 'automatic',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		// Every language x currency variant is cleaned up (plus the base label),
		// not just the first currency.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'feed_labels' => [
						'DE',
						'DE-EN-EUR',
						'DE-DE-EUR',
						'DE-EN-USD',
						'DE-DE-USD',
					],
				]
			);

		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'de' );
	}

	public function test_delete_market_primary_throws_and_does_not_schedule_cleanup(): void {
		$this->cleanup_job->expects( $this->never() )
			->method( 'schedule' );

		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->expectException( InvalidValue::class );

		$this->market_service->delete_market( 'primary' );
	}

	public function test_delete_market_country_restoration_is_idempotent(): void {
		$existing = [
			'gb' => [
				'country'        => 'GB',
				'language'       => [ 'en' ],
				'currency'       => [ 'GBP' ],
				'feed_label'     => 'GB',
				'was_in_primary' => true,
			],
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US', 'GB' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $existing,
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->market_service->delete_market( 'gb' );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$this->assertArrayNotHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
	}

	public function test_delete_market_updates_existing_rate_row_with_primary_values(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => '5.00',
					'options'  => [ 'free_shipping_threshold' => 50.0 ],
				],
				[
					'id'       => 2,
					'country'  => 'FR',
					'currency' => 'EUR',
					'rate'     => '20.00',
					'options'  => [],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'country'  => 'FR',
					'currency' => 'USD',
					'rate'     => '5.00',
					'options'  => [ 'free_shipping_threshold' => 50.0 ],
				],
				[ 'id' => 2 ]
			);
		$this->shipping_rate_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_updates_existing_time_row_with_primary_values(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'time'     => '1',
					'max_time' => '3',
				],
				[
					'id'       => 2,
					'country'  => 'FR',
					'time'     => '3',
					'max_time' => '7',
				],
			]
		);

		$this->shipping_time_query->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'country'  => 'FR',
					'time'     => '1',
					'max_time' => '3',
				],
				[ 'id' => 2 ]
			);
		$this->shipping_time_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_time_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_inserts_rate_row_when_target_missing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => '5.00',
					'options'  => [ 'free_shipping_threshold' => 50.0 ],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'insert' )
			->with(
				[
					'country'  => 'FR',
					'currency' => 'USD',
					'rate'     => '5.00',
					'options'  => [ 'free_shipping_threshold' => 50.0 ],
				]
			);
		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_inserts_time_row_when_target_missing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'time'     => '1',
					'max_time' => '3',
				],
			]
		);

		$this->shipping_time_query->expects( $this->once() )
			->method( 'insert' )
			->with(
				[
					'country'  => 'FR',
					'time'     => '1',
					'max_time' => '3',
				]
			);
		$this->shipping_time_query->expects( $this->never() )->method( 'update' );
		$this->shipping_time_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_deletes_orphan_rate_row_when_source_missing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 2,
					'country'  => 'FR',
					'currency' => 'EUR',
					'rate'     => '20.00',
					'options'  => [],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'FR' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'insert' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_deletes_orphan_time_row_when_source_missing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 2,
					'country'  => 'FR',
					'time'     => '3',
					'max_time' => '7',
				],
			]
		);

		$this->shipping_time_query->expects( $this->once() )
			->method( 'delete' )
			->with( 'country', 'FR' );
		$this->shipping_time_query->expects( $this->never() )->method( 'update' );
		$this->shipping_time_query->expects( $this->never() )->method( 'insert' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_does_nothing_to_rate_query_when_both_rate_rows_missing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_does_nothing_to_time_query_when_both_time_rows_missing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_time_query->expects( $this->never() )->method( 'update' );
		$this->shipping_time_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_time_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'fr' );
	}

	public function test_delete_market_primary_throw_does_not_run_sync_or_fire_hook(): void {
		$hook_fired = false;
		$listener   = function () use ( &$hook_fired ) {
			$hook_fired = true;
		};
		add_action( 'woocommerce_gla_market_deleted', $listener );

		$this->shipping_rate_query->expects( $this->never() )->method( 'get_results' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'delete' );
		$this->shipping_time_query->expects( $this->never() )->method( 'get_results' );
		$this->shipping_time_query->expects( $this->never() )->method( 'update' );
		$this->shipping_time_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_time_query->expects( $this->never() )->method( 'delete' );

		try {
			$this->market_service->delete_market( 'primary' );
			$this->fail( 'Expected InvalidValue exception.' );
		} catch ( InvalidValue $e ) {
			$this->assertInstanceOf( InvalidValue::class, $e );
		} finally {
			remove_action( 'woocommerce_gla_market_deleted', $listener );
		}

		$this->assertFalse( $hook_fired );
	}

	public function test_delete_market_fires_woocommerce_gla_market_deleted_hook_once_with_id_and_deleted_config(): void {
		$stored_config = [
			'country'    => 'FR',
			'language'   => [ 'fr' ],
			'currency'   => [ 'EUR' ],
			'feed_label' => 'FR',
		];

		// Custom language/currency markets are an automatic/manual concept (the persisted path).
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [ 'fr' => $stored_config ],
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$captured = [];
		$listener = function ( $market_id, $deleted_config ) use ( &$captured ) {
			$captured[] = [ $market_id, $deleted_config ];
		};
		add_action( 'woocommerce_gla_market_deleted', $listener, 10, 2 );

		try {
			$this->market_service->delete_market( 'fr' );
		} finally {
			remove_action( 'woocommerce_gla_market_deleted', $listener, 10 );
		}

		// The payload carries the deleted config with the current global shipping method.
		$expected_config = array_merge(
			$stored_config,
			[
				'shipping_rate' => 'automatic',
				'shipping_time' => 'flat',
			]
		);
		$this->assertCount( 1, $captured );
		$this->assertSame( [ 'fr', $expected_config ], $captured[0] );
	}

	public function test_delete_market_coerces_null_options_to_empty_array(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'        => 'FR',
						'language'       => [ 'fr' ],
						'currency'       => [ 'EUR' ],
						'feed_label'     => 'FR',
						'was_in_primary' => true,
					],
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => '5.00',
					'options'  => null,
				],
				[
					'id'       => 2,
					'country'  => 'FR',
					'currency' => 'EUR',
					'rate'     => '20.00',
					'options'  => [],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'update' )
			->with(
				[
					'country'  => 'FR',
					'currency' => 'USD',
					'rate'     => '5.00',
					'options'  => [],
				],
				[ 'id' => 2 ]
			);

		$this->market_service->delete_market( 'fr' );
	}

	public function test_update_markets_strips_primary_key(): void {
		$markets = [
			'primary' => [ 'country' => 'US' ],
			'gb'      => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$persisted = null;
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MARKETS,
				$this->callback(
					function ( $value ) use ( &$persisted ) {
						$persisted = $value;
						return true;
					}
				)
			);

		$this->market_service->update_markets( $markets );

		$this->assertArrayNotHasKey( 'primary', $persisted );
		$this->assertArrayHasKey( 'gb', $persisted );
	}

	public function test_get_all_feed_labels_primary_only(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_all_feed_labels();

		$this->assertSame( [ 'US' ], $result );
	}

	public function test_get_all_feed_labels_includes_secondary_markets(): void {
		// Stored currencies only drive the derived labels while a multilingual
		// integration is active; without one the site locale takes over.
		$this->wpml->method( 'is_active' )->willReturn( true );

		$secondary = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
			'de' => [
				'country'    => 'DE',
				'language'   => [ 'de' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'DE',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		// Non-store-currency markets only contribute labels while conversion is available.
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$result = $this->market_service->get_all_feed_labels();

		$this->assertContains( 'US', $result );
		$this->assertContains( 'GB-EN-GBP', $result );
		$this->assertContains( 'DE-DE-EUR', $result );
		$this->assertCount( 3, $result );
	}

	public function test_get_main_feed_label_returns_primary_feed_label(): void {
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'AU' );

		$result = $this->market_service->get_main_feed_label();

		$this->assertSame( 'AU', $result );
	}

	public function test_get_all_countries_primary_only(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US', 'CA' ] );

		$result = $this->market_service->get_all_countries();

		$this->assertSame( [ 'US', 'CA' ], $result );
	}

	public function test_get_all_countries_includes_secondary_market_country(): void {
		$secondary = [
			'gb' => [
				'country'    => 'GB',
				'language'   => 'en',
				'currency'   => 'GBP',
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US', 'CA' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$result = $this->market_service->get_all_countries();

		$this->assertContains( 'US', $result );
		$this->assertContains( 'CA', $result );
		$this->assertContains( 'GB', $result );
		$this->assertCount( 3, $result );
	}

	public function test_get_all_countries_deduplicates(): void {
		$secondary = [
			'us2' => [
				'country'    => 'US',
				'language'   => 'en',
				'currency'   => 'USD',
				'feed_label' => 'US-PROMO',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_all_countries();

		$this->assertCount( 1, $result );
		$this->assertContains( 'US', $result );
	}

	public function test_get_markets_secondary_enriched_with_free_shipping_countries_and_label(): void {
		$rates = [
			'US' => [ 'rate' => '10.00' ],
			'DE' => [
				'rate'                    => '5.00',
				'free_shipping_threshold' => 75.0,
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'flat' ],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US', 'DE' ] ],
			]
		);
		$this->set_up_primary_market_dependencies(
			'US',
			[ 'US', 'DE' ],
			$rates,
			[
				'DE' => 'Germany',
				'US' => 'United States (US)',
			]
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => '2',
					'max_time' => '4',
				],
				'DE' => [
					'time'     => '2',
					'max_time' => '4',
				],
			]
		);

		$result = $this->market_service->get_markets();

		// DE has a distinct flat rate, so it surfaces as the derived 'de' secondary market
		// enriched with its country, label, and free-shipping threshold.
		$this->assertSame( [ 'DE' ], $result['de']['countries'] );
		$this->assertSame( 'Germany', $result['de']['label'] );
		$this->assertSame( 75.0, $result['de']['free_shipping'] );
	}

	public function test_get_markets_free_shipping_null_for_all_markets_when_mode_automatic(): void {
		$secondary = [
			'de' => [
				'country'    => 'DE',
				'language'   => [ 'de' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'DE',
			],
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'FR',
			],
		];

		// Rows persist in the DB across mode switches, so this fixture mimics a
		// merchant who had flat rates configured and then switched to automatic.
		$rates = [
			'US' => [
				'country_code'            => 'US',
				'currency'                => 'USD',
				'rate'                    => '5.00',
				'free_shipping_threshold' => 50.0,
			],
			'DE' => [
				'country_code'            => 'DE',
				'currency'                => 'EUR',
				'rate'                    => '5.00',
				'free_shipping_threshold' => 75.0,
			],
			'FR' => [
				'country_code'            => 'FR',
				'currency'                => 'EUR',
				'rate'                    => '5.00',
				'free_shipping_threshold' => 80.0,
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $secondary,
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ], $rates );

		$result = $this->market_service->get_markets();

		$this->assertNull( $result['primary']['free_shipping'] );
		$this->assertNull( $result['de']['free_shipping'] );
		$this->assertNull( $result['fr']['free_shipping'] );
	}

	public function test_get_markets_free_shipping_null_when_mode_manual_or_unset(): void {
		$secondary = [
			'de' => [
				'country'    => 'DE',
				'language'   => [ 'de' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'DE',
			],
		];

		$rates = [
			'US' => [
				'country_code'            => 'US',
				'currency'                => 'USD',
				'rate'                    => '5.00',
				'free_shipping_threshold' => 50.0,
			],
			'DE' => [
				'country_code'            => 'DE',
				'currency'                => 'EUR',
				'rate'                    => '5.00',
				'free_shipping_threshold' => 75.0,
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $secondary,
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'manual' ],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ], $rates );

		$result = $this->market_service->get_markets();

		$this->assertNull( $result['primary']['free_shipping'] );
		$this->assertNull( $result['de']['free_shipping'] );
	}

	public function test_get_markets_secondary_free_shipping_null_when_no_rate_entry(): void {
		$secondary = [
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'FR',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertNull( $result['fr']['free_shipping'] );
		$this->assertSame( [ 'FR' ], $result['fr']['countries'] );
	}

	public function test_add_market_defaults_shipping_mode_from_mc_settings_when_omitted(): void {
		$mc_settings = [
			'shipping_rate' => 'automatic',
			'shipping_time' => 'manual',
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => $mc_settings,
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market(
			'jp',
			[
				'country'    => 'JP',
				'language'   => [ 'ja' ],
				'currency'   => [ 'JPY' ],
				'feed_label' => 'JP',
			]
		);

		$stored_jp = $update_calls[ OptionsInterface::MARKETS ]['jp'];
		$this->assertSame( 'automatic', $stored_jp['shipping_rate'] );
		$this->assertSame( 'manual', $stored_jp['shipping_time'] );
	}

	/**
	 * An explicit shipping_rate/shipping_time in the add request is still persisted
	 * as the stored snapshot. This is storage-only: the snapshot no longer drives any
	 * decision (get_markets() overwrites it with the global method on read, and the
	 * sync gate reads the global setting), but the write itself is preserved.
	 */
	public function test_add_market_explicit_shipping_mode_takes_precedence_over_mc_settings(): void {
		$mc_settings = [
			'shipping_rate' => 'automatic',
			'shipping_time' => 'automatic',
		];

		$ta = [
			'location'  => 'selected',
			'countries' => [ 'US' ],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => $mc_settings,
				OptionsInterface::TARGET_AUDIENCE => $ta,
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market(
			'jp',
			[
				'country'       => 'JP',
				'language'      => [ 'ja' ],
				'currency'      => [ 'JPY' ],
				'feed_label'    => 'JP',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			]
		);

		$stored_jp = $update_calls[ OptionsInterface::MARKETS ]['jp'];
		$this->assertSame( 'flat', $stored_jp['shipping_rate'] );
		$this->assertSame( 'flat', $stored_jp['shipping_time'] );
	}

	public function test_has_multilingual_support_returns_false(): void {
		$this->wpml->method( 'is_active' )->willReturn( false );

		$this->assertFalse( $this->market_service->has_multilingual_support() );
	}

	public function test_add_market_throws_when_language_is_not_array(): void {
		$this->expectException( InvalidValue::class );

		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => 'en',
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			]
		);
	}

	public function test_add_market_throws_when_currency_is_not_array(): void {
		$this->expectException( InvalidValue::class );

		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => 'GBP',
				'feed_label' => 'GB',
			]
		);
	}

	/**
	 * @dataProvider provide_valid_feed_labels
	 *
	 * @param string $feed_label
	 */
	public function test_add_market_accepts_valid_feed_label( string $feed_label ): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'GB' ] ],
			]
		);

		$persisted = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$persisted ) {
					if ( OptionsInterface::MARKETS === $key ) {
						$persisted = $value;
					}
					return true;
				}
			);

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => $feed_label,
			]
		);

		$this->assertSame( $feed_label, $persisted['gb']['feed_label'] );
	}

	/**
	 * @dataProvider provide_invalid_feed_labels
	 *
	 * @param string $feed_label
	 */
	public function test_add_market_rejects_invalid_feed_label( string $feed_label ): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => $feed_label,
			]
		);
	}

	/**
	 * @dataProvider provide_valid_feed_labels
	 *
	 * @param string $feed_label
	 */
	public function test_update_market_accepts_valid_feed_label( string $feed_label ): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->update_market( 'gb', [ 'feed_label' => $feed_label ] );

		$this->assertSame( $feed_label, $result['feed_label'] );
	}

	/**
	 * @dataProvider provide_invalid_feed_labels
	 *
	 * @param string $feed_label
	 */
	public function test_update_market_rejects_invalid_feed_label( string $feed_label ): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->update_market( 'gb', [ 'feed_label' => $feed_label ] );
	}

	public function test_feed_label_rejection_message_references_pattern_and_value(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessageMatches( '#feed_label.*\[A-Z0-9-\].*"us"#' );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'us',
			]
		);
	}

	public function test_empty_feed_label_still_throws_is_empty_not_pattern(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'The value of feed_label can not be empty.' );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => '',
			]
		);
	}

	public function provide_valid_feed_labels(): array {
		return [
			'two-letter uppercase'     => [ 'US' ],
			'uppercase with dash'      => [ 'GB-EN' ],
			'alphanumeric'             => [ 'A1' ],
			'thirteen char max length' => [ 'A1-B2-C3-D4-E' ],
		];
	}

	public function provide_invalid_feed_labels(): array {
		return [
			'lowercase'      => [ 'us' ],
			'fourteen chars' => [ 'A1-B2-C3-D4-E5' ],
			'twenty chars'   => [ 'A1-B2-C3-D4-E5-F6-G7' ],
			'underscore'     => [ 'GB_EN' ],
			'period'         => [ 'GB.EN' ],
			'space'          => [ 'GB EN' ],
			'at sign'        => [ 'GB@EN' ],
		];
	}

	public function test_add_market_persists_array_language_and_currency(): void {
		$config = [
			'country'    => 'CH',
			'language'   => [ 'de', 'fr', 'it' ],
			'currency'   => [ 'CHF', 'EUR' ],
			'feed_label' => 'CH',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'CH' ] ],
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'ch', $config );

		$stored_ch = $update_calls[ OptionsInterface::MARKETS ]['ch'];
		$this->assertSame( [ 'de', 'fr', 'it' ], $stored_ch['language'] );
		$this->assertSame( [ 'CHF', 'EUR' ], $stored_ch['currency'] );
	}

	public function test_add_market_defaults_language_and_currency_when_omitted(): void {
		// The market form omits the language and currency fields for some
		// shipping methods and for stores without a multilingual integration,
		// so the service must fall back to the site defaults instead of failing.
		$config = [
			'country'    => 'GB',
			'feed_label' => 'GB',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'GB' ] ],
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'gb', $config );

		$stored_gb = $update_calls[ OptionsInterface::MARKETS ]['gb'];
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $stored_gb['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $stored_gb['currency'] );
	}

	public function test_update_market_defaults_language_and_currency_for_market_stored_without_them(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->update_market( 'gb', [ 'country' => 'GB' ] );

		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $result['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $result['currency'] );
	}

	public function test_add_market_persists_empty_language_currency_arrays_verbatim(): void {
		$config = [
			'country'    => 'GB',
			'language'   => [],
			'currency'   => [],
			'feed_label' => 'GB',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'GB' ] ],
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'gb', $config );

		$stored_gb = $update_calls[ OptionsInterface::MARKETS ]['gb'];
		$this->assertSame( [], $stored_gb['language'] );
		$this->assertSame( [], $stored_gb['currency'] );
	}

	public function test_add_market_saves_supplied_languages_verbatim(): void {
		$config = [
			'country'    => 'FR',
			'language'   => [ 'fr', 'de' ],
			'currency'   => [ 'EUR' ],
			'feed_label' => 'FR',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'FR' ] ],
			]
		);

		$update_calls = [];
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_calls ) {
					$update_calls[ $key ] = $value;
					return true;
				}
			);

		$this->market_service->add_market( 'fr', $config );

		$stored_fr = $update_calls[ OptionsInterface::MARKETS ]['fr'];
		$this->assertSame( [ 'fr', 'de' ], $stored_fr['language'] );
	}

	public function test_has_multilingual_support_returns_true(): void {
		$this->wpml->method( 'is_active' )->willReturn( true );

		$this->assertTrue( $this->market_service->has_multilingual_support() );
	}

	public function test_get_primary_market_uses_wpml_default_language_when_active(): void {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'is_active' )->willReturn( true );
		$wpml->method( 'get_default_language_code' )->willReturn( 'fr' );
		$wpml->method( 'get_languages' )->willReturn(
			[
				[
					'code'  => 'en',
					'label' => 'English',
				],
				[
					'code'  => 'fr',
					'label' => 'French',
				],
			]
		);
		$wpml->method( 'get_currencies' )->willReturn(
			[
				[
					'code'   => 'EUR',
					'symbol' => '€',
				],
			]
		);

		$this->set_up_options_get( [ OptionsInterface::MERCHANT_CENTER => [] ] );
		$this->set_up_primary_market_dependencies( 'FR', [ 'FR' ] );

		$result = $this->create_service_with_wpml( $wpml )->get_primary_market();

		$this->assertSame( [ 'fr' ], $result['language'] );
		$this->assertSame( [ 'EUR' ], $result['currency'] );
	}

	public function test_get_languages_delegates_to_wpml_integration(): void {
		$languages = [
			[
				'code'  => 'en',
				'label' => 'English',
			],
			[
				'code'  => 'de',
				'label' => 'German',
			],
		];

		$this->wpml->method( 'get_languages' )->willReturn( $languages );

		$this->assertSame( $languages, $this->market_service->get_languages() );
	}

	public function test_get_languages_falls_back_to_site_default_when_wpml_returns_none(): void {
		$this->wpml->method( 'get_languages' )->willReturn( [] );

		$result = $this->market_service->get_languages();

		$this->assertCount( 1, $result );
		$this->assertSame( substr( get_locale(), 0, 2 ), $result[0]['code'] );
		$this->assertNotSame( '', $result[0]['label'] );
	}

	public function test_get_currencies_delegates_to_wpml(): void {
		$currencies = [
			[
				'code'      => 'USD',
				'symbol'    => '$',
				'languages' => [ 'en' ],
			],
			[
				'code'      => 'EUR',
				'symbol'    => '€',
				'languages' => [ 'en' ],
			],
		];

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_languages' )->willReturn(
			[
				[
					'code'  => 'en',
					'label' => 'English',
				],
			]
		);
		$wpml->method( 'get_currencies' )->willReturn( $currencies );

		$this->assertSame( $currencies, $this->create_service_with_wpml( $wpml )->get_currencies() );
	}

	public function test_get_currencies_falls_back_to_site_default_when_wpml_returns_none(): void {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_languages' )->willReturn( [] );
		$wpml->method( 'get_currencies' )->willReturn( [] );

		$result = $this->create_service_with_wpml( $wpml )->get_currencies();

		$this->assertCount( 1, $result );
		$this->assertSame( get_woocommerce_currency(), $result[0]['code'] );
		$this->assertNotSame( '', $result[0]['symbol'] );
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $result[0]['languages'] );
	}

	public function test_get_currencies_backfills_languages_when_wpml_has_currencies_but_no_languages(): void {
		// WPML ties each currency's `languages` to its own get_languages(), so a
		// currency can come back with no languages even though it is itself
		// configured, if no WPML languages are configured yet. Left unpatched,
		// this currency would be filtered out of the edit form's currency
		// dropdown as soon as the fallback language from get_languages() is
		// selected, reproducing the empty-dropdown bug for a non-empty currency
		// list.
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_languages' )->willReturn( [] );
		$wpml->method( 'get_currencies' )->willReturn(
			[
				[
					'code'      => 'USD',
					'symbol'    => '$',
					'languages' => [],
				],
			]
		);

		$result = $this->create_service_with_wpml( $wpml )->get_currencies();

		$this->assertCount( 1, $result );
		$this->assertSame( 'USD', $result[0]['code'] );
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $result[0]['languages'] );
	}

	public function test_generate_market_id_sanitises_uppercase_feed_label(): void {
		$this->assertSame( 'gb', $this->market_service->generate_market_id( 'GB' ) );
	}

	public function test_generate_market_id_converts_multi_word_label_to_slug(): void {
		$this->assertSame( 'united-kingdom', $this->market_service->generate_market_id( 'United Kingdom' ) );
	}

	public function test_generate_market_id_throws_when_label_sanitises_to_reserved_primary(): void {
		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessageMatches( '/reserved/' );

		$this->market_service->generate_market_id( 'Primary' );
	}

	public function test_generate_market_id_throws_when_label_is_already_lowercase_primary(): void {
		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessageMatches( '/reserved/' );

		$this->market_service->generate_market_id( 'primary' );
	}

	/**
	 * Verifies that shipping rates are fetched once per MarketService instance.
	 *
	 * The assertion is on the mock, not on a return value: `expects( $this->once() )`
	 * tells PHPUnit to fail at teardown if `get_all_shipping_rates()` is invoked
	 * 0 times or 2+ times across the three service calls below.
	 *
	 * The three calls mirror the controller's create-market flow — `get_market()`
	 * for the existence check, `get_market()` for the read-back, plus a `get_markets()`
	 * for good measure — so a working cache yields one query, a broken cache yields three.
	 *
	 * Does not use `set_up_primary_market_dependencies()` because that helper attaches
	 * a `->method('get_all_shipping_rates')` matcher to the same mock method; stacking
	 * that with `expects( $this->once() )->method(...)` creates two rules per call and
	 * makes the count expectation unreliable.
	 */
	public function test_shipping_rates_fetched_once_across_multiple_get_markets_calls(): void {
		// Flat rate: the only mode that reads the shipping rates for a market's free
		// shipping threshold, so the only mode where there is a fetch to cache.
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'get_all_shipping_rates' )
			->willReturn( [] );

		$this->market_service->get_market( 'some-id' );
		$this->market_service->get_market( 'some-id' );
		$this->market_service->get_markets();
	}

	/**
	 * Regression (GOOWOO-773): a secondary market keeps a stale snapshot of the
	 * shipping method (here `flat`) from when it was created. After the merchant
	 * switches the global rate to `manual`, the sync check must follow the global
	 * setting, not the stale snapshot — otherwise a sync is attempted with no DB
	 * rates and the DB adapter throws (500 error on saving the setting).
	 */
	public function test_has_syncable_markets_false_when_global_is_manual_despite_stale_secondary(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'manual',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'fr' => [
						'country'       => 'FR',
						'feed_label'    => 'FR',
						'language'      => [ 'fr' ],
						'currency'      => [ 'EUR' ],
						'shipping_rate' => 'flat',
						'shipping_time' => 'flat',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$this->assertFalse( $this->market_service->has_syncable_markets() );
	}

	/**
	 * The inverse of the regression case: a secondary market's stale snapshot says
	 * `manual`, but the global rate is `flat`, so the store is syncable.
	 */
	public function test_has_syncable_markets_true_when_global_is_flat_despite_stale_manual_secondary(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'fr' => [
						'country'       => 'FR',
						'feed_label'    => 'FR',
						'language'      => [ 'fr' ],
						'currency'      => [ 'EUR' ],
						'shipping_rate' => 'manual',
						'shipping_time' => 'flat',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->assertTrue( $this->market_service->has_syncable_markets() );
	}

	public function test_has_syncable_markets_true_when_global_is_automatic(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->assertTrue( $this->market_service->has_syncable_markets() );
	}

	/**
	 * A missing/unset shipping_rate (with a flat time) is not syncable — the old
	 * `'manual' !== $rate` check would have wrongly treated null as syncable.
	 */
	public function test_has_syncable_markets_false_when_rate_is_null(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->assertFalse( $this->market_service->has_syncable_markets() );
	}

	public function test_has_syncable_markets_false_when_every_market_is_manual(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'manual',
					'shipping_time' => 'flat',
				],
				OptionsInterface::MARKETS         => [
					'fr' => [
						'country'       => 'FR',
						'feed_label'    => 'FR',
						'language'      => [ 'fr' ],
						'currency'      => [ 'EUR' ],
						'shipping_rate' => 'manual',
						'shipping_time' => 'flat',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->assertFalse( $this->market_service->has_syncable_markets() );
	}

	public function test_has_syncable_markets_false_when_shipping_time_not_flat(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'manual',
				],
				OptionsInterface::MARKETS         => [],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->assertFalse( $this->market_service->has_syncable_markets() );
	}

	public function test_add_market_fires_market_added_hook_on_success(): void {
		$config = [
			'country'    => 'GB',
			'language'   => [ 'en' ],
			'currency'   => [ 'GBP' ],
			'feed_label' => 'GB',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'automatic',
				],
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);

		$persisted_markets = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$persisted_markets ) {
					if ( OptionsInterface::MARKETS === $key ) {
						$persisted_markets = $value;
					}
					return true;
				}
			);

		$fired_count     = 0;
		$captured_id     = null;
		$captured_config = null;
		add_action(
			'woocommerce_gla_market_added',
			function ( $id, $hook_config ) use ( &$fired_count, &$captured_id, &$captured_config ) {
				++$fired_count;
				$captured_id     = $id;
				$captured_config = $hook_config;
			},
			10,
			2
		);

		$this->market_service->add_market( 'gb', $config );

		$this->assertSame( 1, $fired_count );
		$this->assertSame( 'gb', $captured_id );

		// The market was persisted as submitted.
		$this->assertSame( [ 'GBP' ], $persisted_markets['gb']['currency'] );

		// The action reports the market as it will be read back, so a listener sees the
		// values the store will use: the live shipping method, the country's list and
		// label, and the store currency, since without a multilingual integration the
		// submitted GBP cannot be produced.
		$this->assertSame( 'gb', $captured_config['id'] );
		$this->assertSame( 'GB', $captured_config['country'] );
		$this->assertSame( [ 'GB' ], $captured_config['countries'] );
		$this->assertSame( 'GB', $captured_config['feed_label'] );
		$this->assertSame( 'automatic', $captured_config['shipping_rate'] );
		$this->assertSame( 'automatic', $captured_config['shipping_time'] );
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $captured_config['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $captured_config['currency'] );
		$this->assertNull( $captured_config['free_shipping'] );
	}

	public function test_add_market_does_not_fire_market_added_hook_when_id_is_primary(): void {
		$fired = false;
		add_action(
			'woocommerce_gla_market_added',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->expectException( InvalidValue::class );

		try {
			$this->market_service->add_market( 'primary', [] );
		} finally {
			$this->assertFalse( $fired );
		}
	}

	public function test_add_market_does_not_fire_market_added_hook_when_validation_fails(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
			]
		);

		$fired = false;
		add_action(
			'woocommerce_gla_market_added',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->expectException( InvalidValue::class );

		try {
			$this->market_service->add_market(
				'gb',
				[
					'country'    => 'GB',
					'feed_label' => '',
					'language'   => [ 'en' ],
					'currency'   => [ 'GBP' ],
				]
			);
		} finally {
			$this->assertFalse( $fired );
		}
	}

	public function test_update_market_fires_market_updated_hook_on_primary_success(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [],
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$fired_count     = 0;
		$captured_id     = null;
		$captured_market = null;
		add_action(
			'woocommerce_gla_market_updated',
			function ( $id, $updated_market ) use ( &$fired_count, &$captured_id, &$captured_market ) {
				++$fired_count;
				$captured_id     = $id;
				$captured_market = $updated_market;
			},
			10,
			2
		);

		$result = $this->market_service->update_market( 'primary', [ 'shipping_rate' => 'flat' ] );

		$this->assertSame( 1, $fired_count );
		$this->assertSame( 'primary', $captured_id );
		$this->assertSame( $result, $captured_market );
		foreach ( [ 'id', 'label', 'countries', 'country', 'language', 'currency', 'feed_label', 'shipping_rate', 'shipping_time', 'free_shipping' ] as $key ) {
			$this->assertArrayHasKey( $key, $captured_market );
		}
	}

	public function test_update_market_fires_market_updated_hook_on_secondary_success(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$fired_count     = 0;
		$captured_id     = null;
		$captured_market = null;
		add_action(
			'woocommerce_gla_market_updated',
			function ( $id, $updated_market ) use ( &$fired_count, &$captured_id, &$captured_market ) {
				++$fired_count;
				$captured_id     = $id;
				$captured_market = $updated_market;
			},
			10,
			2
		);

		$result = $this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );

		$this->assertSame( 1, $fired_count );
		$this->assertSame( 'gb', $captured_id );
		$this->assertSame( $result, $captured_market );
		foreach ( [ 'countries', 'label', 'free_shipping' ] as $key ) {
			$this->assertArrayHasKey( $key, $captured_market );
		}
	}

	public function test_update_market_does_not_fire_market_updated_hook_when_validation_fails(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );

		$fired = false;
		add_action(
			'woocommerce_gla_market_updated',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->expectException( InvalidValue::class );

		try {
			$this->market_service->update_market( 'gb', [ 'feed_label' => '' ] );
		} finally {
			$this->assertFalse( $fired );
		}
	}

	public function test_delete_market_fires_market_deleted_hook_on_success(): void {
		// Custom-currency markets are an automatic/manual concept (the persisted path).
		$existing_entry = [
			'country'       => 'GB',
			'language'      => [ 'en' ],
			'currency'      => [ 'GBP' ],
			'feed_label'    => 'GB',
			'shipping_rate' => 'automatic',
			'shipping_time' => 'flat',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [ 'gb' => $existing_entry ],
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );
		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$fired_count     = 0;
		$captured_id     = null;
		$captured_config = null;
		add_action(
			'woocommerce_gla_market_deleted',
			function ( $id, $deleted_config ) use ( &$fired_count, &$captured_id, &$captured_config ) {
				++$fired_count;
				$captured_id     = $id;
				$captured_config = $deleted_config;
			},
			10,
			2
		);

		$this->market_service->delete_market( 'gb' );

		// The stored snapshot already matches the global method (automatic/flat), so the
		// payload equals the deleted config.
		$this->assertSame( 1, $fired_count );
		$this->assertSame( 'gb', $captured_id );
		$this->assertSame( $existing_entry, $captured_config );
	}

	public function test_delete_market_does_not_fire_market_deleted_hook_when_id_is_primary(): void {
		$fired = false;
		add_action(
			'woocommerce_gla_market_deleted',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->expectException( InvalidValue::class );

		try {
			$this->market_service->delete_market( 'primary' );
		} finally {
			$this->assertFalse( $fired );
		}
	}

	public function test_delete_market_does_not_fire_market_deleted_hook_when_id_not_in_stored_markets(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->options->expects( $this->never() )->method( 'update' );

		$fired = false;
		add_action(
			'woocommerce_gla_market_deleted',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->market_service->delete_market( 'unknown' );

		$this->assertFalse( $fired );
	}

	public function test_update_market_secondary_schedules_market_cleanup_when_language_removed(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en', 'cy' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// A removed language's entries live under its own derived label, so
		// label-based cleanup covers them; the removed label plus the
		// verbatim-label key are cleaned, the surviving label is not.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-CY-GBP' ] ] );
		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'en' ] ] );
	}

	public function test_update_market_secondary_does_not_schedule_cleanup_when_language_added(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->cleanup_job->expects( $this->never() )->method( 'schedule' );
		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'en', 'cy' ] ] );
	}

	public function test_update_market_secondary_does_not_schedule_cleanup_when_language_reordered(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en', 'cy' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->cleanup_job->expects( $this->never() )->method( 'schedule' );
		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'cy', 'en' ] ] );
	}

	public function test_update_market_secondary_cleanup_uses_old_labels_when_feed_label_also_changes(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en', 'cy' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-GBP', 'GB-CY-GBP' ] ] );
		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market(
			'gb',
			[
				'language'   => [ 'en' ],
				'feed_label' => 'GB-PROMO',
			]
		);
	}

	public function test_update_market_primary_schedules_language_cleanup_when_language_removed(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en', 'fr' ],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US', 'CA' ] );

		// Primary entries are tracked under the bare target country codes; the
		// cleanup job narrows the deletion to the removed languages by each
		// product's own post language.
		$this->language_cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'keys'              => [ 'US', 'CA' ],
					'removed_languages' => [ 'fr' ],
				]
			);

		$this->market_service->update_market( 'primary', [ 'language' => [ 'en' ] ] );
	}

	public function test_update_market_primary_does_not_schedule_when_language_unchanged(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en', 'fr' ],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'primary', [ 'currency' => [ 'USD' ] ] );
	}

	public function test_update_market_primary_normalises_locale_form_against_short_codes(): void {
		// Existing stored language uses the locale form 'en_US'; the merchant supplies
		// short codes for the new value. Only the genuinely-removed code should be reported.
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en_US', 'fr' ],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->language_cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'keys'              => [ 'US' ],
					'removed_languages' => [ 'fr' ],
				]
			);

		$this->market_service->update_market( 'primary', [ 'language' => [ 'en' ] ] );
	}

	public function test_update_market_primary_does_not_schedule_when_target_audience_empty(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en', 'fr' ],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [] );

		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'primary', [ 'language' => [ 'en' ] ] );
	}

	public function test_get_market_feed_label_appends_uppercase_language_and_currency(): void {
		// The language/currency suffix applies for a multilingual store (or any non-store currency).
		$this->wpml->method( 'is_active' )->willReturn( true );

		$this->assertSame( 'BE-FR-EUR', $this->market_service->get_market_feed_label( 'BE', 'fr', 'EUR' ) );
		$this->assertSame( 'BE-FR-EUR', $this->market_service->get_market_feed_label( 'BE', 'FR', 'eur' ) );
		$this->assertSame( 'BE-FR-EUR', $this->market_service->get_market_feed_label( 'BE', 'fr_FR', 'EUR' ) );
		$this->assertSame( 'FR-NL-USD', $this->market_service->get_market_feed_label( 'FR', 'nl', 'USD' ) );
	}

	public function test_get_market_feed_label_is_bare_for_non_multilingual_store_currency(): void {
		// A non-multilingual store's store-currency feed collapses to one feed per market, so it
		// uses the bare base label (like the primary market). Non-store currencies keep the suffix.
		$this->wpml->method( 'is_active' )->willReturn( false );

		$this->assertSame( 'FR', $this->market_service->get_market_feed_label( 'FR', 'en', get_woocommerce_currency() ) );
		$this->assertSame( 'FR', $this->market_service->get_market_feed_label( 'FR', '', '' ) );
		$this->assertSame( 'FR-EN-EUR', $this->market_service->get_market_feed_label( 'FR', 'en', 'EUR' ) );
	}

	public function test_get_market_feed_label_returns_empty_string_for_empty_base_label(): void {
		$this->assertSame( '', $this->market_service->get_market_feed_label( '', 'fr', 'EUR' ) );
	}

	public function test_get_market_feed_label_falls_back_to_site_language_and_store_currency_when_empty(): void {
		// On a multilingual store the empty language/currency fall back to the site language and
		// store currency in the suffix.
		$this->wpml->method( 'is_active' )->willReturn( true );

		$this->assertSame(
			'BE-' . strtoupper( substr( get_locale(), 0, 2 ) ) . '-' . get_woocommerce_currency(),
			$this->market_service->get_market_feed_label( 'BE', '', '' )
		);
	}

	public function test_get_all_feed_labels_derives_one_label_per_language(): void {
		// A Merchant Center feed is one language-currency pair, so a market
		// with several languages contributes one derived label per language.
		$this->set_up_wpml_languages( 'en', [ 'en', 'fr', 'nl' ] );
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [ 'language' => [ 'en' ] ],
				OptionsInterface::MARKETS         => [
					'be' => [
						'country'    => 'BE',
						'language'   => [ 'nl', 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'BE',
					],
				],
			]
		);
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$this->assertSame( [ 'US', 'BE-NL-EUR', 'BE-FR-EUR' ], $this->market_service->get_all_feed_labels() );
	}

	public function test_get_all_feed_labels_falls_back_to_store_currency_when_market_has_none(): void {
		$this->set_up_wpml_languages( 'en', [ 'en', 'fr' ] );
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [ 'language' => [ 'en' ] ],
				OptionsInterface::MARKETS         => [
					'be' => [
						'country'    => 'BE',
						'language'   => [],
						'currency'   => [],
						'feed_label' => 'BE',
					],
				],
			]
		);
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->assertSame(
			[ 'US', 'BE-EN-' . get_woocommerce_currency() ],
			$this->market_service->get_all_feed_labels()
		);
	}

	public function test_get_all_feed_labels_ignores_stored_language_when_not_multilingual(): void {
		// A language saved while a multilingual integration was active must
		// not drive the derived label once the integration is gone: without
		// it every product syncs in the site language. The market keeps the
		// store currency so it still takes part in syncing; a market stored
		// with a non-store currency is excluded entirely, covered by
		// test_get_all_feed_labels_omits_excluded_market_so_stale_cleanup_removes_its_entries.
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'ae' => [
						'country'    => 'AE',
						'language'   => [ 'fr' ],
						'currency'   => [ get_woocommerce_currency() ],
						'feed_label' => 'AE',
					],
				],
			]
		);
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->assertSame(
			[ 'US', 'AE' ],
			$this->market_service->get_all_feed_labels()
		);
	}

	public function test_get_markets_uses_site_locale_when_not_multilingual(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'ae' => [
						'country'    => 'AE',
						'language'   => [ 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'AE',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $result['ae']['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $result['ae']['currency'] );
	}

	public function test_get_markets_keeps_stored_locale_when_multilingual(): void {
		$this->set_up_wpml_languages( 'en', [ 'en', 'fr' ] );
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'ae' => [
						'country'    => 'AE',
						'language'   => [ 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'AE',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertSame( [ 'fr' ], $result['ae']['language'] );
		$this->assertSame( [ 'EUR' ], $result['ae']['currency'] );
	}

	public function test_get_markets_masking_does_not_touch_the_stored_option(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'ae' => [
						'country'    => 'AE',
						'language'   => [ 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'AE',
					],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->options->expects( $this->never() )->method( 'update' );

		$this->market_service->get_markets();
	}

	public function test_get_feed_labels_for_language_ignores_stored_language_when_not_multilingual(): void {
		// Without a multilingual integration every product syncs to every
		// market in the site language, so the applicable labels for the site
		// language must include every market regardless of the languages
		// stored on it. Otherwise the error-clearing comparison in
		// ProductHelper::mark_as_synced() runs against fewer labels than the
		// sync actually creates.
		$site_language = substr( get_locale(), 0, 2 );

		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [ 'language' => [ $site_language ] ],
				OptionsInterface::MARKETS         => [
					'ae' => [
						'country'    => 'AE',
						'language'   => [ 'fr' ],
						'currency'   => [ get_woocommerce_currency() ],
						'feed_label' => 'AE',
					],
				],
			]
		);
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->assertSame(
			[ 'US', 'AE' ],
			$this->market_service->get_feed_labels_for_language( $site_language )
		);
	}

	public function test_get_feed_labels_for_language_returns_labels_of_markets_accepting_it(): void {
		$this->set_up_wpml_languages( 'en', [ 'en', 'fr' ] );
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [ 'language' => [ 'en' ] ],
				OptionsInterface::MARKETS         => [
					'be' => [
						'country'    => 'BE',
						'language'   => [ 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'BE',
					],
				],
			]
		);
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$this->assertSame( [ 'BE-FR-EUR' ], $this->market_service->get_feed_labels_for_language( 'fr' ) );
		$this->assertSame( [ 'US' ], $this->market_service->get_feed_labels_for_language( 'en' ) );
	}

	public function test_get_shipping_sync_countries_includes_non_manual_secondary_markets(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'       => 'FR',
						'language'      => [ 'fr' ],
						'currency'      => [ 'EUR' ],
						'feed_label'    => 'FR',
						'shipping_rate' => 'automatic',
					],
					'de' => [
						'country'       => 'DE',
						'language'      => [ 'de' ],
						'currency'      => [ 'EUR' ],
						'feed_label'    => 'DE',
						'shipping_rate' => 'manual',
					],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		// Manual markets are excluded — their shipping is managed outside the plugin.
		$this->assertSame(
			[ 'US', 'CA', 'FR' ],
			$this->market_service->get_shipping_sync_countries()
		);
	}

	public function test_get_shipping_sync_countries_deduplicates_countries(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'       => 'FR',
						'language'      => [ 'fr' ],
						'currency'      => [ 'EUR' ],
						'feed_label'    => 'FR',
						'shipping_rate' => 'flat',
					],
				],
			]
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'FR' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$this->assertSame(
			[ 'US', 'FR' ],
			$this->market_service->get_shipping_sync_countries()
		);
	}

	public function test_get_participating_markets_excludes_foreign_currency_market_without_conversion(): void {
		$secondary = [
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'FR',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$participating = $this->market_service->get_participating_markets();

		$this->assertArrayHasKey( 'primary', $participating );
		$this->assertArrayNotHasKey( 'fr', $participating );

		// The stored market itself is untouched and still visible to the UI.
		$this->assertArrayHasKey( 'fr', $this->market_service->get_markets() );
	}

	public function test_get_participating_markets_includes_foreign_currency_market_with_conversion(): void {
		$secondary = [
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'FR',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$this->assertArrayHasKey( 'fr', $this->market_service->get_participating_markets() );
	}

	public function test_get_participating_markets_always_includes_store_currency_market(): void {
		$secondary = [
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ get_woocommerce_currency() ],
				'feed_label' => 'FR',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->assertArrayHasKey( 'fr', $this->market_service->get_participating_markets() );
	}

	public function test_get_excluded_market_countries_lists_foreign_currency_markets_without_conversion(): void {
		$secondary = [
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'FR',
			],
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ get_woocommerce_currency() ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->assertSame( [ 'FR' ], $this->market_service->get_excluded_market_countries() );
	}

	public function test_get_all_feed_labels_omits_excluded_market_so_stale_cleanup_removes_its_entries(): void {
		$secondary = [
			'fr' => [
				'country'    => 'FR',
				'language'   => [ 'fr' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'FR',
			],
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $secondary,
				OptionsInterface::MERCHANT_CENTER => [ 'language' => [ 'en' ] ],
			]
		);
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->assertSame( [ 'US' ], $this->market_service->get_all_feed_labels() );
	}

	public function test_conversion_availability_change_schedules_resync_and_shipping_sync(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::CURRENCY_CONVERSION_AVAILABLE => 'yes',
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'    => 'FR',
						'language'   => [ 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'FR',
					],
				],
			]
		);
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->shipping_settings_job->expects( $this->once() )->method( 'schedule' );
		$this->update_all_products_job->expects( $this->once() )->method( 'schedule' );

		$this->invoke_conversion_availability_handler();

		$this->assertSame(
			'no',
			$this->options->get( OptionsInterface::CURRENCY_CONVERSION_AVAILABLE )
		);
	}

	public function test_conversion_availability_first_run_records_state_without_scheduling(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'    => 'FR',
						'language'   => [ 'fr' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'FR',
					],
				],
			]
		);
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->shipping_settings_job->expects( $this->never() )->method( 'schedule' );
		$this->update_all_products_job->expects( $this->never() )->method( 'schedule' );

		$this->invoke_conversion_availability_handler();

		$this->assertSame(
			'no',
			$this->options->get( OptionsInterface::CURRENCY_CONVERSION_AVAILABLE )
		);
	}

	public function test_conversion_availability_change_without_foreign_currency_markets_does_not_schedule(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::CURRENCY_CONVERSION_AVAILABLE => 'yes',
				OptionsInterface::MARKETS => [
					'fr' => [
						'country'    => 'FR',
						'language'   => [ 'fr' ],
						'currency'   => [ get_woocommerce_currency() ],
						'feed_label' => 'FR',
					],
				],
			]
		);
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->shipping_settings_job->expects( $this->never() )->method( 'schedule' );
		$this->update_all_products_job->expects( $this->never() )->method( 'schedule' );

		$this->invoke_conversion_availability_handler();
	}

	public function test_conversion_availability_unchanged_does_nothing(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::CURRENCY_CONVERSION_AVAILABLE => 'no',
				OptionsInterface::MARKETS => [],
			]
		);
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->options->expects( $this->never() )->method( 'update' );
		$this->shipping_settings_job->expects( $this->never() )->method( 'schedule' );
		$this->update_all_products_job->expects( $this->never() )->method( 'schedule' );

		$this->invoke_conversion_availability_handler();
	}

	private function invoke_conversion_availability_handler(): void {
		$method = ( new \ReflectionClass( MarketService::class ) )->getMethod( 'handle_conversion_availability_change' );
		$method->setAccessible( true );
		$method->invoke( $this->market_service );
	}

	public function test_add_market_copies_primary_shipping_rows_for_uncovered_country(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->options->method( 'update' )->willReturn( true );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => 5.0,
					'options'  => [],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'time'     => 3,
					'max_time' => 7,
				],
			]
		);

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'insert' )
			->with(
				[
					'country'  => 'GB',
					'currency' => 'USD',
					'rate'     => 5.0,
					'options'  => [],
				]
			);
		$this->shipping_time_query->expects( $this->once() )
			->method( 'insert' )
			->with(
				[
					'country'  => 'GB',
					'time'     => 3,
					'max_time' => 7,
				]
			);

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			]
		);
	}

	public function test_add_market_leaves_existing_shipping_rows_untouched(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->options->method( 'update' )->willReturn( true );
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );

		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => 5.0,
					'options'  => [],
				],
				[
					'id'       => 2,
					'country'  => 'GB',
					'currency' => 'GBP',
					'rate'     => 8.0,
					'options'  => [],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'time'     => 3,
					'max_time' => 7,
				],
				[
					'id'       => 2,
					'country'  => 'GB',
					'time'     => 5,
					'max_time' => 10,
				],
			]
		);

		$this->shipping_rate_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_rate_query->expects( $this->never() )->method( 'update' );
		$this->shipping_time_query->expects( $this->never() )->method( 'insert' );
		$this->shipping_time_query->expects( $this->never() )->method( 'update' );

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			]
		);
	}

	public function test_add_market_rejects_currency_the_site_cannot_produce(): void {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_currencies' )->willReturn( [] );

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'cannot be produced' );

		$this->create_service_with_wpml( $wpml )->add_market(
			'de',
			[
				'country'    => 'DE',
				'language'   => [ 'de' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'DE',
			]
		);
	}

	public function test_add_market_accepts_unproducible_currency_with_exchange_rate(): void {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_currencies' )->willReturn( [] );
		// Currencies pass the per-language check untouched (an unstubbed mock returns [] and fails them all).
		$wpml->method( 'get_currencies_enabled_for_language' )->willReturnArgument( 0 );

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);

		$persisted = null;
		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$persisted ) {
					if ( OptionsInterface::MARKETS === $key ) {
						$persisted = $value;
					}
					return true;
				}
			);

		$this->create_service_with_wpml( $wpml )->add_market(
			'de',
			[
				'country'       => 'DE',
				'language'      => [ 'de' ],
				'currency'      => [ 'EUR' ],
				'feed_label'    => 'DE',
				'exchange_rate' => 0.92,
			]
		);

		$this->assertSame( 0.92, $persisted['de']['exchange_rate'] );
	}

	public function test_add_market_rejects_non_numeric_exchange_rate(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'exchange_rate' );

		$this->market_service->add_market(
			'de',
			[
				'country'       => 'DE',
				'language'      => [ 'de' ],
				'currency'      => [ 'EUR' ],
				'feed_label'    => 'DE',
				'exchange_rate' => 'not-a-number',
			]
		);
	}

	public function test_add_market_rejects_non_positive_exchange_rate(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'exchange_rate' );

		$this->market_service->add_market(
			'de',
			[
				'country'       => 'DE',
				'language'      => [ 'de' ],
				'currency'      => [ 'EUR' ],
				'feed_label'    => 'DE',
				'exchange_rate' => 0,
			]
		);
	}

	public function test_update_market_rejects_touched_currency_the_site_cannot_produce(): void {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_currencies' )->willReturn( [] );

		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'cannot be produced' );

		$this->create_service_with_wpml( $wpml )->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );
	}

	public function test_update_market_untouched_currency_is_not_revalidated(): void {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_currencies' )->willReturn( [] );
		// Currencies pass the per-language check untouched (an unstubbed mock returns [] and fails them all).
		$wpml->method( 'get_currencies_enabled_for_language' )->willReturnArgument( 0 );

		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// The stored GBP is not producible on this site, but an update that
		// leaves the currency and exchange rate untouched must not fail on it.
		$result = $this->create_service_with_wpml( $wpml )->update_market( 'gb', [ 'shipping_rate' => 'flat' ] );

		// Secondary markets do not own a shipping method (the submitted value
		// is dropped), so completion is asserted through the returned market.
		$this->assertSame( 'GB', $result['country'] );
	}

	public function test_update_market_secondary_schedules_update_all_products_when_only_exchange_rate_differs(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'exchange_rate' => 1.15 ] );
	}

	public function test_update_market_secondary_does_not_schedule_update_all_products_when_exchange_rate_unchanged(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'exchange_rate' => 1.15,
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$this->update_all_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'exchange_rate' => 1.15 ] );
	}

	public function test_get_markets_keeps_stored_currency_for_market_with_exchange_rate_when_not_multilingual(): void {
		$secondary = [
			'de' => [
				'country'       => 'DE',
				'language'      => [ 'de' ],
				'currency'      => [ 'EUR' ],
				'feed_label'    => 'DE',
				'exchange_rate' => 0.92,
			],
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$markets = $this->market_service->get_markets();

		// The exchange rate is the market's own conversion source, so its
		// stored currency survives the no-multilingual masking; the market
		// without a rate is masked to the site currency as before.
		$this->assertSame( [ 'EUR' ], $markets['de']['currency'] );
		$this->assertSame( 0.92, $markets['de']['exchange_rate'] );
		$this->assertSame( [ get_woocommerce_currency() ], $markets['gb']['currency'] );
	}

	/**
	 * Builds a MarketService around a locally configured WPML mock, for tests
	 * whose WPML behaviour must differ from the permissive setUp defaults.
	 *
	 * @param MockObject|WPML $wpml The locally configured WPML mock.
	 *
	 * @return MarketService
	 */
	private function create_service_with_wpml( $wpml ): MarketService {
		$service = new MarketService(
			$this->target_audience,
			$this->shipping_rate_query,
			$this->shipping_time_query,
			$this->wc,
			$wpml,
			$this->job_repository
		);
		$service->set_options_object( $this->options );

		return $service;
	}

	public function test_get_participating_currencies_drops_foreign_currency_without_conversion(): void {
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$market = [ 'currency' => [ get_woocommerce_currency(), 'AED' ] ];

		$this->assertSame( [ get_woocommerce_currency() ], $this->market_service->get_participating_currencies( $market ) );
	}

	public function test_get_participating_currencies_includes_foreign_currency_with_conversion(): void {
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$market = [ 'currency' => [ get_woocommerce_currency(), 'AED' ] ];

		$this->assertSame( [ get_woocommerce_currency(), 'AED' ], $this->market_service->get_participating_currencies( $market ) );
	}

	public function test_get_all_feed_labels_includes_every_currency_of_a_market(): void {
		$this->wpml->method( 'is_active' )->willReturn( true );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$secondary = [
			'ae' => [
				'country'    => 'AE',
				'language'   => [ 'en', 'fr' ],
				'currency'   => [ get_woocommerce_currency(), 'AED' ],
				'feed_label' => 'AE',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_all_feed_labels();

		$store_currency = get_woocommerce_currency();
		$this->assertContains( 'US', $result );
		$this->assertContains( 'AE-EN-' . $store_currency, $result );
		$this->assertContains( 'AE-FR-' . $store_currency, $result );
		$this->assertContains( 'AE-EN-AED', $result );
		$this->assertContains( 'AE-FR-AED', $result );
		$this->assertCount( 5, $result );
	}

	public function test_get_all_feed_labels_excludes_non_participating_currency(): void {
		$this->wpml->method( 'is_active' )->willReturn( true );
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$secondary = [
			'ae' => [
				'country'    => 'AE',
				'language'   => [ 'en' ],
				'currency'   => [ get_woocommerce_currency(), 'AED' ],
				'feed_label' => 'AE',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_all_feed_labels();

		$this->assertContains( 'AE-EN-' . get_woocommerce_currency(), $result );
		$this->assertNotContains( 'AE-EN-AED', $result );
	}

	public function test_get_all_feed_labels_includes_primary_extra_currency_labels(): void {
		$this->wpml->method( 'is_active' )->willReturn( true );
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en', 'fr' ],
					'currency' => [ get_woocommerce_currency(), 'EUR' ],
				],
			]
		);
		$this->set_up_primary_market_dependencies( 'GB', [ 'GB' ] );

		$result = $this->market_service->get_all_feed_labels();

		// The store currency keeps the bare label; only the extra currency
		// gets per-language derived labels.
		$this->assertContains( 'GB', $result );
		$this->assertContains( 'GB-EN-EUR', $result );
		$this->assertContains( 'GB-FR-EUR', $result );
		$this->assertNotContains( 'GB-EN-' . get_woocommerce_currency(), $result );
		$this->assertCount( 3, $result );
	}

	public function test_update_market_schedules_cleanup_for_removed_currency_labels(): void {
		$existing = [
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP', 'EUR' ],
				'feed_label' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		// Removing EUR orphans the EUR label; the still-configured GBP
		// language label stays out of the cleanup set.
		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-EN-EUR' ] ] );

		$this->market_service->update_market( 'gb', [ 'currency' => [ 'GBP' ] ] );
	}

	public function test_update_market_primary_schedules_cleanup_for_removed_currency(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en', 'fr' ],
					'currency' => [ get_woocommerce_currency(), 'EUR' ],
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'GB' ] ],
			]
		);
		$this->set_up_primary_market_dependencies( 'GB', [ 'GB' ] );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB-EN-EUR', 'GB-FR-EUR' ] ] );

		$this->market_service->update_market( 'primary', [ 'currency' => [ get_woocommerce_currency() ] ] );
	}

	public function test_update_market_primary_removing_store_currency_schedules_no_cleanup(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'language' => [ 'en' ],
					'currency' => [ get_woocommerce_currency(), 'EUR' ],
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'GB' ] ],
			]
		);
		$this->set_up_primary_market_dependencies( 'GB', [ 'GB' ] );

		// The store currency's entries live under the bare main feed label,
		// which stays current, so removing the store currency from the
		// configured list must not clean anything up.
		$this->cleanup_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'primary', [ 'currency' => [ 'EUR' ] ] );
	}

	public function test_delete_market_schedules_cleanup_with_every_currency_label(): void {
		// A multi-language/currency market is a multilingual concept, so its feeds keep the suffix.
		$this->wpml->method( 'is_active' )->willReturn( true );

		$store_currency = get_woocommerce_currency();

		$existing = [
			'ae' => [
				'country'    => 'AE',
				'language'   => [ 'en', 'fr' ],
				'currency'   => [ $store_currency, 'AED' ],
				'feed_label' => 'AE',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->options->method( 'update' )->willReturn( true );
		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'feed_labels' => [
						'AE',
						'AE-EN-' . $store_currency,
						'AE-FR-' . $store_currency,
						'AE-EN-AED',
						'AE-FR-AED',
					],
				]
			);

		$this->market_service->delete_market( 'ae' );
	}

	public function test_conversion_availability_change_with_primary_extra_currency_schedules(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::CURRENCY_CONVERSION_AVAILABLE => 'yes',
				OptionsInterface::MARKETS         => [],
				OptionsInterface::MERCHANT_CENTER => [
					'currency' => [ get_woocommerce_currency(), 'EUR' ],
				],
			]
		);
		$this->wpml->method( 'can_convert_currency' )->willReturn( false );

		$this->shipping_settings_job->expects( $this->once() )->method( 'schedule' );
		$this->update_all_products_job->expects( $this->once() )->method( 'schedule' );

		$this->invoke_conversion_availability_handler();
	}

	/**
	 * Configures the WPML mock as an active multilingual integration.
	 *
	 * @param string   $default_code The site default language code.
	 * @param string[] $codes        All active language codes.
	 */
	private function set_up_wpml_languages( string $default_code, array $codes ): void {
		$this->wpml->method( 'is_active' )->willReturn( true );
		$this->wpml->method( 'get_default_language_code' )->willReturn( $default_code );
		$this->wpml->method( 'get_languages' )->willReturn(
			array_map(
				static function ( $code ) {
					return [
						'code'  => $code,
						'label' => strtoupper( $code ),
					];
				},
				$codes
			)
		);
	}

	public function test_add_market_rejects_country_owned_by_another_market(): void {
		// Market IDs derive from the feed label, so a second market for the same country under a
		// different label gets a different ID and passes the controller's ID conflict check.
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
			]
		);

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market(
			'gb-eur',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'GB-EUR',
			]
		);
	}

	public function test_update_primary_market_rejects_country_owned_by_secondary(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->expectException( InvalidValue::class );

		$this->market_service->update_market( 'primary', [ 'countries' => [ 'US', 'CA', 'GB' ] ] );
	}

	public function test_primary_market_countries_exclude_a_country_a_secondary_already_owns(): void {
		// TARGET_AUDIENCE is writable outside MarketService, so it can hold a country a market
		// owns. Reading the primary market has to drop it, or saving the form unchanged submits
		// it back, is rejected, and locks the merchant out of the primary market.
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA', 'GB' ] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [] );

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA', 'GB' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$countries = $this->market_service->get_primary_market()['countries'];

		$this->assertNotContains( 'GB', $countries );
		$this->assertSame( [ 'US', 'CA' ], array_values( $countries ) );

		// The filtered list is what the form submits back, and it must save.
		$this->market_service->update_market( 'primary', [ 'countries' => $countries ] );
	}

	public function test_flat_mode_stored_leftover_does_not_block_saving_the_primary_market(): void {
		// A stored market left behind by an earlier mode is an orphan in flat mode, reconciled
		// away when the markets list is read. It must not count as owning its country, or the
		// primary market cannot be saved while it sits there.
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'GB' ] );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [] );

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'GB' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'flat' ],
			]
		);

		$this->market_service->update_market( 'primary', [ 'countries' => [ 'US', 'GB' ] ] );

		$this->assertContains( 'GB', $this->options->get( OptionsInterface::TARGET_AUDIENCE )['countries'] );
	}

	public function test_update_market_country_change_transfers_ownership(): void {
		// The transfer extends shipping to the new country, which reads the stored rows.
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [ 'US' => [ 'rate' => '5' ] ] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [ 'US' => [ 'time' => 3 ] ] );

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'        => 'GB',
						'language'       => [ 'en' ],
						'currency'       => [ 'GBP' ],
						'feed_label'     => 'GB',
						'was_in_primary' => true,
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		// MX is not targeted, so the flag has to come out false on the other side of the move.
		$this->market_service->update_market( 'gb', [ 'country' => 'MX' ] );

		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		// The market releases GB back to the feed it came from and takes MX out, so each
		// country sits in exactly one feed.
		$this->assertContains( 'GB', $audience['countries'] );
		$this->assertNotContains( 'MX', $audience['countries'] );

		$stored = $this->options->get( OptionsInterface::MARKETS )['gb'];
		$this->assertSame( 'MX', $stored['country'] );

		// Seeded true against GB, so a false here can only come from recomputing against MX.
		$this->assertFalse( $stored['was_in_primary'] );
	}

	public function test_update_market_country_change_stops_targeting_a_country_the_market_introduced(): void {
		// GB is targeted only because this market targets it, so releasing it has to stop the
		// targeting rather than hand it to the primary feed the merchant never put it in.
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [ 'US' => [ 'rate' => '5' ] ] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [ 'US' => [ 'time' => 3 ] ] );

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'        => 'GB',
						'language'       => [ 'en' ],
						'currency'       => [ 'GBP' ],
						'feed_label'     => 'GB',
						'was_in_primary' => false,
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->market_service->update_market( 'gb', [ 'country' => 'IE' ] );

		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		$this->assertNotContains( 'GB', $audience['countries'] );
	}

	public function test_update_market_country_change_returns_a_borrowed_country_on_primary_shipping(): void {
		// GB came out of the primary feed, so going back it has to carry the primary's shipping
		// rather than the rate and delivery time this market gave it.
		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturn( [ 'US' => [ 'rate' => '5' ] ] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( [ 'US' => [ 'time' => 3 ] ] );
		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => '5',
					'options'  => [],
				],
				[
					'id'       => 2,
					'country'  => 'GB',
					'currency' => 'USD',
					'rate'     => '20',
					'options'  => [],
				],
			]
		);
		$this->shipping_time_query->method( 'get_results' )->willReturn(
			[
				[
					'id'       => 1,
					'country'  => 'US',
					'time'     => 3,
					'max_time' => 5,
				],
				[
					'id'       => 2,
					'country'  => 'GB',
					'time'     => 9,
					'max_time' => 12,
				],
			]
		);

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'        => 'GB',
						'language'       => [ 'en' ],
						'currency'       => [ 'GBP' ],
						'feed_label'     => 'GB',
						'was_in_primary' => true,
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		// GB's own rate is overwritten with the primary's, not left at 20.
		$this->shipping_rate_query->expects( $this->atLeastOnce() )
			->method( 'update' )
			->with(
				$this->callback(
					function ( array $data ): bool {
						return 'GB' === $data['country'] && '5' === $data['rate'];
					}
				),
				[ 'id' => 2 ]
			);

		$this->market_service->update_market( 'gb', [ 'country' => 'IE' ] );

		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		$this->assertContains( 'GB', $audience['countries'] );
	}

	public function test_update_market_rejects_country_owned_by_another_market(): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
					'ie' => [
						'country'    => 'IE',
						'language'   => [ 'en' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'IE',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->expectException( InvalidValue::class );

		$this->market_service->update_market( 'gb', [ 'country' => 'IE' ] );
	}

	public function test_delete_market_keeps_shipping_when_country_is_still_targeted(): void {
		// An "all countries" audience stores no explicit list, so markets created before the
		// membership fix recorded was_in_primary as false for a country that is still targeted.
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA', 'GB' ] );

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'        => 'GB',
						'language'       => [ 'en' ],
						'currency'       => [ 'GBP' ],
						'feed_label'     => 'GB',
						'was_in_primary' => false,
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [ 'location' => 'all' ],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		// Stripping the rows would leave GB selling with no shipping service on the next sync.
		$this->shipping_rate_query->expects( $this->never() )->method( 'delete' );
		$this->shipping_time_query->expects( $this->never() )->method( 'delete' );

		$this->market_service->delete_market( 'gb' );

		// An "all countries" audience already targets GB, so it stays "all" rather than gaining
		// a one-country list that contradicts the location.
		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		$this->assertSame( 'all', $audience['location'] );
		$this->assertArrayNotHasKey( 'countries', $audience );
	}

	public function test_delete_market_removes_shipping_when_country_was_never_targeted(): void {
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'        => 'GB',
						'language'       => [ 'en' ],
						'currency'       => [ 'GBP' ],
						'feed_label'     => 'GB',
						'was_in_primary' => false,
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->shipping_rate_query->expects( $this->once() )->method( 'delete' )->with( 'country', 'GB' );
		$this->shipping_time_query->expects( $this->once() )->method( 'delete' )->with( 'country', 'GB' );

		$this->market_service->delete_market( 'gb' );
	}

	public function test_add_market_takes_country_out_of_an_all_countries_audience(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA', 'GB' ] );

		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'location' => 'all' ],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->market_service->add_market(
			'gb',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
			]
		);

		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		// "All" carries no list to remove from, so it is materialised before GB is taken out.
		// Left as "all", GB would stay in the primary feed while also owning its own market.
		$this->assertSame( 'selected', $audience['location'] );
		$this->assertNotContains( 'GB', $audience['countries'] );
		$this->assertContains( 'US', $audience['countries'] );

		$this->assertTrue( $this->options->get( OptionsInterface::MARKETS )['gb']['was_in_primary'] );
	}

	public function test_rejected_primary_market_update_writes_nothing(): void {
		// Ownership is checked before any option is written, so a rejected country selection
		// cannot leave the Merchant Center settings saved with the audience untouched.
		$this->set_up_options_get_with_tracking(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'GBP' ],
						'feed_label' => 'GB',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US', 'CA' ],
				],
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'automatic',
					'language'      => [ 'en' ],
				],
			]
		);

		$rejected = false;

		// The call is wrapped rather than declared with expectException() because the assertions
		// below have to run after it, which they never do once the exception escapes the test.
		try {
			$this->market_service->update_market(
				'primary',
				[
					'language'  => [ 'en', 'fr' ],
					'countries' => [ 'US', 'CA', 'GB' ],
				]
			);
		} catch ( InvalidValue $exception ) {
			$rejected = true;
		}

		$this->assertTrue( $rejected, 'GB belongs to the gb market, so the update must be rejected.' );

		$this->assertSame( [ 'en' ], $this->options->get( OptionsInterface::MERCHANT_CENTER )['language'] );
		$this->assertSame( [ 'US', 'CA' ], $this->options->get( OptionsInterface::TARGET_AUDIENCE )['countries'] );
	}

	public function test_country_ownership_rejection_names_the_owning_market_feed_label(): void {
		// A market ID is the sanitised feed label, so naming the ID would report "gb-eur" to a
		// merchant who entered "GB-EUR".
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [
					'gb-eur' => [
						'country'    => 'GB',
						'language'   => [ 'en' ],
						'currency'   => [ 'EUR' ],
						'feed_label' => 'GB-EUR',
					],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'The country "GB" already belongs to the "GB-EUR" market.' );

		$this->market_service->add_market(
			'gb-usd',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'USD' ],
				'feed_label' => 'GB-USD',
			]
		);
	}

	public function test_country_ownership_rejection_falls_back_to_the_market_id(): void {
		// A market stored before the feed label was required has nothing else to be named by.
		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [
					'gb' => [ 'country' => 'GB' ],
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => [ 'US' ],
				],
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'automatic' ],
			]
		);

		$this->expectException( InvalidValue::class );
		$this->expectExceptionMessage( 'The country "GB" already belongs to the "gb" market.' );

		$this->market_service->add_market(
			'gb-eur',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'GB-EUR',
			]
		);
	}

	/**
	 * Sets up the options mock to return specific values for different option keys.
	 *
	 * @param array $option_map Keyed by OptionsInterface constant, values are the return values.
	 */
	private function set_up_options_get( array $option_map ): void {
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $key, $fallback = null ) use ( $option_map ) {
					return array_key_exists( $key, $option_map )
						? $option_map[ $key ]
						: $fallback;
				}
			);
	}

	/**
	 * Like set_up_options_get but tracks update() calls so subsequent get() calls
	 * return the updated values. Use for tests that call a method which writes
	 * then re-reads the same option (e.g. update_market → get_market).
	 *
	 * @param array $option_map Keyed by OptionsInterface constant, values are the initial return values.
	 */
	private function set_up_options_get_with_tracking( array $option_map ): void {
		$store = $option_map;

		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $key, $fallback = null ) use ( &$store ) {
					return array_key_exists( $key, $store )
						? $store[ $key ]
						: $fallback;
				}
			);

		$this->options->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$store ) {
					$store[ $key ] = $value;
					return true;
				}
			);
	}

	/**
	 * Sets up the TargetAudience, ShippingRateQuery, and WC mocks for primary market composition.
	 *
	 * @param string   $main_country     The main target country code.
	 * @param string[] $target_countries  All target countries.
	 * @param array    $shipping_rates    Optional shipping rates keyed by country.
	 * @param array    $country_names     Optional map of country code => full name for WC::get_countries().
	 */
	private function set_up_primary_market_dependencies(
		string $main_country,
		array $target_countries,
		array $shipping_rates = [],
		array $country_names = []
	): void {
		$this->target_audience->method( 'get_main_target_country' )
			->willReturn( $main_country );
		$this->target_audience->method( 'get_target_countries' )
			->willReturn( $target_countries );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )
			->willReturn( $shipping_rates );
		$this->wc->method( 'get_countries' )
			->willReturn( $country_names );
	}

	/**
	 * Sets up a flat-rate store whose shipping tables behave like the real ones: rows
	 * inserted during the call are visible to the reads that follow it.
	 *
	 * The first target country is the main one. Both row arrays are taken by reference so
	 * a test can assert on what the call wrote.
	 *
	 * @param string[] $target_countries The target audience countries.
	 * @param array    $rate_rows        Shipping rate rows, as ShippingRateQuery::get_results() returns them.
	 * @param array    $time_rows        Shipping time rows, as ShippingTimeQuery::get_results() returns them.
	 */
	private function set_up_flat_market_dependencies(
		array $target_countries,
		array &$rate_rows,
		array &$time_rows
	): void {
		$this->set_up_options_get(
			[
				OptionsInterface::MERCHANT_CENTER => [
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				OptionsInterface::TARGET_AUDIENCE => [
					'location'  => 'selected',
					'countries' => $target_countries,
				],
			]
		);

		$this->target_audience->method( 'get_main_target_country' )->willReturn( $target_countries[0] );
		$this->target_audience->method( 'get_target_countries' )->willReturn( $target_countries );
		$this->wc->method( 'get_countries' )->willReturn(
			[
				'MU' => 'Mauritius',
				'CM' => 'Cameroon',
			]
		);

		$this->shipping_rate_query->method( 'get_results' )->willReturnCallback(
			function () use ( &$rate_rows ) {
				return $rate_rows;
			}
		);
		$this->shipping_time_query->method( 'get_results' )->willReturnCallback(
			function () use ( &$time_rows ) {
				return $time_rows;
			}
		);

		$this->shipping_rate_query->method( 'insert' )->willReturnCallback(
			function ( array $data ) use ( &$rate_rows ) {
				$id          = count( $rate_rows ) + 1;
				$rate_rows[] = $data + [ 'id' => $id ];
				return $id;
			}
		);
		$this->shipping_time_query->method( 'insert' )->willReturnCallback(
			function ( array $data ) use ( &$time_rows ) {
				$id          = count( $time_rows ) + 1;
				$time_rows[] = $data + [ 'id' => $id ];
				return $id;
			}
		);

		$this->shipping_rate_query->method( 'get_all_shipping_rates' )->willReturnCallback(
			function () use ( &$rate_rows ) {
				$rates = [];
				foreach ( $rate_rows as $row ) {
					$rates[ $row['country'] ] = [ 'rate' => $row['rate'] ];
					if ( isset( $row['options']['free_shipping_threshold'] ) ) {
						$rates[ $row['country'] ]['free_shipping_threshold'] = $row['options']['free_shipping_threshold'];
					}
				}
				return $rates;
			}
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturnCallback(
			function () use ( &$time_rows ) {
				$times = [];
				foreach ( $time_rows as $row ) {
					$times[ $row['country'] ] = [
						'time'     => $row['time'],
						'max_time' => $row['max_time'],
					];
				}
				return $times;
			}
		);
	}
}
