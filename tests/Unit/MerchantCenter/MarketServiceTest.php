<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedMarketProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
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

	/** @var MockObject|JobRepository */
	protected $job_repository;

	/** @var MockObject|CleanupOrphanedMarketProductsJob */
	protected $cleanup_job;

	/** @var MockObject|UpdateShippingSettings */
	protected $shipping_settings_job;

	/** @var MarketService */
	protected $market_service;

	public function setUp(): void {
		parent::setUp();

		$this->target_audience       = $this->createMock( TargetAudience::class );
		$this->options               = $this->createMock( OptionsInterface::class );
		$this->shipping_rate_query   = $this->createMock( ShippingRateQuery::class );
		$this->shipping_time_query   = $this->createMock( ShippingTimeQuery::class );
		$this->wc                    = $this->createMock( WC::class );
		$this->wpml                  = $this->createMock( WPML::class );
		$this->job_repository        = $this->createMock( JobRepository::class );
		$this->cleanup_job           = $this->createMock( CleanupOrphanedMarketProductsJob::class );
		$this->shipping_settings_job = $this->createMock( UpdateShippingSettings::class );

		$this->job_repository->method( 'get' )
			->willReturnCallback(
				function ( $classname ) {
					switch ( $classname ) {
						case CleanupOrphanedMarketProductsJob::class:
							return $this->cleanup_job;
						case UpdateShippingSettings::class:
							return $this->shipping_settings_job;
						default:
							return null;
					}
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

	public function test_get_markets_falls_back_to_default_when_empty(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => null ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_markets();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'primary', $result );
		$this->assertSame( 'US', $result['primary']['country'] );
		$this->assertSame( 'US', $result['primary']['feed_label'] );
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

		$this->assertSame( 'US', $result['primary']['country'] );
		$this->assertSame( 'US', $result['primary']['feed_label'] );
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
		$this->assertSame( [ substr( get_locale(), 0, 2 ) ], $result['language'] );
		$this->assertSame( [ get_woocommerce_currency() ], $result['currency'] );
		$this->assertSame( 'US', $result['feed_label'] );
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
				'language' => [ 'en' ],
				'currency' => [ 'GBP' ],
			]
		);
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

	public function test_update_market_secondary_partial_update_succeeds(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
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
			->with( [ 'feed_label' => 'GB' ] );

		// feed_label rename alone does not touch country/currency/shipping_rate/shipping_time.
		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'feed_label' => 'GB-PROMO' ] );
	}

	public function test_update_market_does_not_schedule_cleanup_when_non_feed_label_keys_change(): void {
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

		$this->cleanup_job->expects( $this->never() )
			->method( 'schedule' );

		// Shipping-relevant fields (country, currency, shipping_rate, shipping_time) DID change.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market(
			'gb',
			[
				'country'       => 'IE',
				'language'      => [ 'ga' ],
				'currency'      => [ 'EUR' ],
				'shipping_rate' => 'automatic',
				'shipping_time' => 'automatic',
			]
		);
	}

	public function test_add_market_with_manual_shipping_rate_does_not_schedule_shipping_sync(): void {
		$config = [
			'country'       => 'DE',
			'language'      => [ 'de' ],
			'currency'      => [ 'EUR' ],
			'feed_label'    => 'DE',
			'shipping_rate' => 'manual',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

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

	public function test_delete_market_with_manual_shipping_rate_does_not_schedule_shipping_sync(): void {
		$existing = [
			'gb' => [
				'country'       => 'GB',
				'language'      => [ 'en' ],
				'currency'      => [ 'GBP' ],
				'feed_label'    => 'GB',
				'shipping_rate' => 'manual',
				'shipping_time' => 'flat',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->options->method( 'update' )->willReturn( true );

		$this->shipping_settings_job->expects( $this->never() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'gb' );
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
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		$this->cleanup_job->expects( $this->never() )
			->method( 'schedule' );

		// shipping_rate defaults to 'flat' (non-manual) → shipping sync IS scheduled.
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
				'country'    => 'US',
				'language'   => [ 'en' ],
				'currency'   => [ 'USD' ],
				'feed_label' => 'US',
			],
			'gb' => [
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
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

	public function test_delete_market_schedules_cleanup_with_feed_label(): void {
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

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $existing ] );
		$this->options->method( 'update' )->willReturn( true );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_label' => 'GB' ] );

		// Non-manual shipping_rate → shipping sync also scheduled.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->delete_market( 'gb' );
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
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB',
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

	public function test_build_default_markets_returns_keyed_by_primary(): void {
		$this->target_audience->method( 'get_main_target_country' )
			->willReturn( 'AU' );

		$result = $this->market_service->build_default_markets();

		$this->assertArrayHasKey( 'primary', $result );
		$this->assertSame( 'AU', $result['primary']['country'] );
		$this->assertSame( 'AU', $result['primary']['feed_label'] );
		$this->assertArrayHasKey( 'language', $result['primary'] );
		$this->assertArrayHasKey( 'currency', $result['primary'] );
	}

	public function test_get_all_feed_labels_primary_only(): void {
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_all_feed_labels();

		$this->assertSame( [ 'US' ], $result );
	}

	public function test_get_all_feed_labels_includes_secondary_markets(): void {
		$secondary = [
			'gb' => [
				'country'    => 'GB',
				'language'   => 'en',
				'currency'   => 'GBP',
				'feed_label' => 'GB',
			],
			'de' => [
				'country'    => 'DE',
				'language'   => 'de',
				'currency'   => 'EUR',
				'feed_label' => 'DE',
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies( 'US', [ 'US' ] );

		$result = $this->market_service->get_all_feed_labels();

		$this->assertContains( 'US', $result );
		$this->assertContains( 'GB', $result );
		$this->assertContains( 'DE', $result );
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
		$secondary = [
			'de' => [
				'country'    => 'DE',
				'language'   => [ 'de' ],
				'currency'   => [ 'EUR' ],
				'feed_label' => 'DE',
			],
		];

		$rates = [
			'DE' => [
				'country_code'            => 'DE',
				'currency'                => 'EUR',
				'rate'                    => '5.00',
				'free_shipping_threshold' => 75.0,
			],
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => $secondary ] );
		$this->set_up_primary_market_dependencies(
			'US',
			[ 'US' ],
			$rates,
			[
				'DE' => 'Germany',
				'US' => 'United States (US)',
			]
		);

		$result = $this->market_service->get_markets();

		$this->assertSame( [ 'DE' ], $result['de']['countries'] );
		$this->assertSame( 'Germany', $result['de']['label'] );
		$this->assertSame( 75.0, $result['de']['free_shipping'] );
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
			'two-letter uppercase'   => [ 'US' ],
			'uppercase with dash'    => [ 'GB-EN' ],
			'alphanumeric'           => [ 'A1' ],
			'twenty char max length' => [ 'A1-B2-C3-D4-E5-F6-G7' ],
		];
	}

	public function provide_invalid_feed_labels(): array {
		return [
			'lowercase'        => [ 'us' ],
			'twenty-one chars' => [ 'A1-B2-C3-D4-E5-F6-G7-' ],
			'underscore'       => [ 'GB_EN' ],
			'period'           => [ 'GB.EN' ],
			'space'            => [ 'GB EN' ],
			'at sign'          => [ 'GB@EN' ],
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

	public function test_add_market_throws_when_language_currency_omitted(): void {
		$config = [
			'country'    => 'GB',
			'feed_label' => 'GB',
		];

		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->expectException( InvalidValue::class );

		$this->market_service->add_market( 'gb', $config );
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
		$this->wpml->method( 'is_active' )->willReturn( true );
		$this->wpml->method( 'get_default_language_code' )->willReturn( 'fr' );
		$this->wpml->method( 'get_languages' )->willReturn(
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
		$this->wpml->method( 'get_currencies' )->willReturn(
			[
				[
					'code'   => 'EUR',
					'symbol' => '€',
				],
			]
		);

		$this->set_up_options_get( [ OptionsInterface::MERCHANT_CENTER => [] ] );
		$this->set_up_primary_market_dependencies( 'FR', [ 'FR' ] );

		$result = $this->market_service->get_primary_market();

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

	public function test_get_languages_returns_empty_when_wpml_not_active(): void {
		$this->wpml->method( 'get_languages' )->willReturn( [] );

		$this->assertSame( [], $this->market_service->get_languages() );
	}

	public function test_get_currencies_delegates_to_wpml(): void {
		$currencies = [
			[
				'code'   => 'USD',
				'symbol' => '$',
			],
			[
				'code'   => 'EUR',
				'symbol' => '€',
			],
		];

		$this->wpml->method( 'get_currencies' )->willReturn( $currencies );

		$this->assertSame( $currencies, $this->market_service->get_currencies() );
	}

	public function test_get_currencies_returns_empty_when_wpml_not_active(): void {
		$this->wpml->method( 'get_currencies' )->willReturn( [] );

		$this->assertSame( [], $this->market_service->get_currencies() );
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
		$this->set_up_options_get( [ OptionsInterface::MARKETS => [] ] );

		$this->target_audience->method( 'get_main_target_country' )->willReturn( 'US' );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		$this->shipping_rate_query->expects( $this->once() )
			->method( 'get_all_shipping_rates' )
			->willReturn( [] );

		$this->market_service->get_market( 'some-id' );
		$this->market_service->get_market( 'some-id' );
		$this->market_service->get_markets();
	}

	public function test_has_syncable_markets_true_when_only_secondary_is_non_manual(): void {
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

		$this->assertTrue( $this->market_service->has_syncable_markets() );
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

		$fired_count    = 0;
		$captured_id    = null;
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
		$this->assertSame( $persisted_markets['gb'], $captured_config );
		$this->assertSame( 'automatic', $captured_config['shipping_rate'] );
		$this->assertSame( 'automatic', $captured_config['shipping_time'] );
		$this->assertSame( [ 'en' ], $captured_config['language'] );
		$this->assertSame( [ 'GBP' ], $captured_config['currency'] );
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
		$existing_entry = [
			'country'       => 'GB',
			'language'      => [ 'en' ],
			'currency'      => [ 'GBP' ],
			'feed_label'    => 'GB',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
		];

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => [ 'gb' => $existing_entry ],
				OptionsInterface::TARGET_AUDIENCE => [ 'countries' => [ 'US' ] ],
			]
		);
		$this->options->method( 'update' )->willReturn( true );

		$fired_count    = 0;
		$captured_id    = null;
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
}
