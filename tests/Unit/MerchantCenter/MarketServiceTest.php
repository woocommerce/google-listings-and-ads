<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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

	/** @var MarketService */
	protected $market_service;

	public function setUp(): void {
		parent::setUp();

		$this->target_audience     = $this->createMock( TargetAudience::class );
		$this->options             = $this->createMock( OptionsInterface::class );
		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->shipping_time_query = $this->createMock( ShippingTimeQuery::class );

		$this->market_service = new MarketService(
			$this->target_audience,
			$this->shipping_rate_query,
			$this->shipping_time_query
		);
		$this->market_service->set_options_object( $this->options );
	}

	public function test_get_markets_returns_primary_and_stored_secondary(): void {
		$secondary = [
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
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

	public function test_get_markets_falls_back_to_default_when_empty(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => null ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'primary', $result );
		$this->assertSame( 'US', $result['primary']['country'] );
		$this->assertSame( 'US', $result['primary']['feedLabel'] );
	}

	public function test_get_markets_strips_stored_primary_key(): void {
		$stored = [
			'primary' => [ 'should' => 'be-ignored' ],
			'gb'      => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
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
				'id'        => 'primary',
				'label'     => 'Primary Market',
				'countries' => [ 'MU', 'ZW' ],
				'country'   => 'ZW',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'ZW',
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

		$this->assertSame( 'US', $result['primary']['country'] );
		$this->assertSame( 'US', $result['primary']['feedLabel'] );
		$this->assertSame( [ 'US', 'CA' ], $result['primary']['countries'] );
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
		$this->assertSame( 'US', $result['country'] );
		$this->assertNotEmpty( $result['language'] );
		$this->assertNotEmpty( $result['currency'] );
		$this->assertSame( 'US', $result['feedLabel'] );
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
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $stored ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_market( 'gb' );

		$this->assertSame( 'GB', $result['country'] );
	}

	public function test_get_market_returns_primary_for_primary_id(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_market( 'primary' );

		$this->assertSame( 'primary', $result['id'] );
		$this->assertSame( 'US', $result['country'] );
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
				'country'  => 'GB',
				'language' => 'en',
			]
		);
	}

	public function test_add_market_persists_and_removes_country_from_target_audience(): void {
		$config = [
			'country'   => 'GB',
			'language'  => 'en',
			'currency'  => 'GBP',
			'feedLabel' => 'GB',
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
		$this->assertSame( $config, $update_calls[ OptionsInterface::MARKETS ]['gb'] );

		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
		$this->assertSame( [ 'US', 'CA' ], $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
	}

	public function test_add_market_country_removal_is_idempotent(): void {
		$config = [
			'country'   => 'DE',
			'language'  => 'de',
			'currency'  => 'EUR',
			'feedLabel' => 'DE',
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

	public function test_update_market_secondary_merges_and_persists(): void {
		$existing = [
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
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

		$this->market_service->update_market( 'gb', [ 'currency' => 'EUR' ] );

		$this->assertSame( 'EUR', $persisted['gb']['currency'] );
		$this->assertSame( 'GB', $persisted['gb']['country'] );
	}

	public function test_update_market_secondary_validates_merged_config(): void {
		$existing = [
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->update_market( 'gb', [ 'country' => '' ] );
	}

	public function test_update_market_secondary_partial_update_succeeds(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => 'en',
				'currency'      => 'GBP',
				'feedLabel'     => 'GB',
				'shipping_rate' => 'automatic',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->update_market( 'gb', [ 'shipping_rate' => 'flat' ] );

		$this->assertSame( 'flat', $result['shipping_rate'] );
		$this->assertSame( 'GB', $result['country'] );
	}

	public function test_update_market_secondary_returns_updated_market(): void {
		$existing = [
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
			],
		];

		$this->set_up_options_get_with_tracking( [ OptionsInterface::MARKETS => $existing ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->update_market( 'gb', [ 'currency' => 'EUR' ] );

		$this->assertIsArray( $result );
		$this->assertSame( 'GB', $result['country'] );
	}

	public function test_delete_market_throws_when_id_is_primary(): void {
		$this->expectException( InvalidValue::class );

		$this->market_service->delete_market( 'primary' );
	}

	public function test_delete_market_removes_and_restores_country_to_target_audience(): void {
		$existing = [
			'us' => [
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			],
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
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

		$this->market_service->delete_market( 'us' );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$this->assertArrayNotHasKey( 'us', $update_calls[ OptionsInterface::MARKETS ] );
		$this->assertArrayHasKey( 'gb', $update_calls[ OptionsInterface::MARKETS ] );

		$this->assertArrayHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
		$this->assertContains( 'US', $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
		$this->assertContains( 'CA', $update_calls[ OptionsInterface::TARGET_AUDIENCE ]['countries'] );
	}

	public function test_delete_market_country_restoration_is_idempotent(): void {
		$existing = [
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
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

		$this->market_service->delete_market( 'gb' );

		$this->assertArrayHasKey( OptionsInterface::MARKETS, $update_calls );
		$this->assertArrayNotHasKey( OptionsInterface::TARGET_AUDIENCE, $update_calls );
	}

	public function test_update_markets_strips_primary_key(): void {
		$markets = [
			'primary' => [ 'country' => 'US' ],
			'gb'      => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
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

	public function test_build_default_markets_returns_keyed_by_primary(): void {
		$this->target_audience->method( 'get_main_target_country' )
			->willReturn( 'AU' );

		$result = $this->market_service->build_default_markets();

		$this->assertArrayHasKey( 'primary', $result );
		$this->assertSame( 'AU', $result['primary']['country'] );
		$this->assertSame( 'AU', $result['primary']['feedLabel'] );
		$this->assertArrayHasKey( 'language', $result['primary'] );
		$this->assertArrayHasKey( 'currency', $result['primary'] );
	}

	public function test_has_multilingual_support_returns_false(): void {
		$this->assertFalse( $this->market_service->has_multilingual_support() );
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
	 * Sets up the TargetAudience and ShippingRateQuery mocks for primary market composition.
	 *
	 * @param string   $main_country     The main target country code.
	 * @param string[] $target_countries  All target countries.
	 * @param array    $shipping_rates    Optional shipping rates keyed by country.
	 */
	private function set_up_primary_market_dependencies(
		string $main_country,
		array $target_countries,
		array $shipping_rates = []
	): void {
		$this->target_audience->method( 'get_main_target_country' )
			->willReturn( $main_country );
		$this->target_audience->method( 'get_target_countries' )
			->willReturn( $target_countries );
		$this->shipping_rate_query->method( 'get_all_shipping_rates' )
			->willReturn( $shipping_rates );
	}
}
