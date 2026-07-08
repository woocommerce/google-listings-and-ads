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

		$this->target_audience         = $this->createMock( TargetAudience::class );
		$this->options                 = $this->createMock( OptionsInterface::class );
		$this->shipping_rate_query     = $this->createMock( ShippingRateQuery::class );
		$this->shipping_time_query     = $this->createMock( ShippingTimeQuery::class );
		$this->wc                      = $this->createMock( WC::class );
		$this->wpml                    = $this->createMock( WPML::class );
		$this->job_repository          = $this->createMock( JobRepository::class );
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
			->with( [ 'feed_labels' => [ 'GB', 'GB-GBP' ] ] );

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
			->with( [ 'feed_labels' => [ 'GB', 'GB-GBP' ] ] );

		$this->market_service->update_market( 'gb', [ 'currency' => [ 'EUR' ] ] );
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

		// Shipping-relevant fields (country, shipping_rate, shipping_time) DID change.
		$this->shipping_settings_job->expects( $this->once() )
			->method( 'schedule' );

		$this->market_service->update_market(
			'gb',
			[
				'country'       => 'IE',
				'language'      => [ 'ga' ],
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

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->shipping_settings_job->expects( $this->never() )
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

		$this->shipping_rate_query->method( 'get_results' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_results' )->willReturn( [] );

		$this->cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ 'feed_labels' => [ 'GB', 'GB-GBP' ] ] );

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

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS => [ 'fr' => $stored_config ],
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

		$this->assertCount( 1, $captured );
		$this->assertSame( [ 'fr', $stored_config ], $captured[0] );
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
		// Non-store-currency markets only contribute labels while conversion is available.
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

		$result = $this->market_service->get_all_feed_labels();

		$this->assertContains( 'US', $result );
		$this->assertContains( 'GB-GBP', $result );
		$this->assertContains( 'DE-EUR', $result );
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

		$this->set_up_options_get(
			[
				OptionsInterface::MARKETS         => $secondary,
				OptionsInterface::MERCHANT_CENTER => [ 'shipping_rate' => 'flat' ],
			]
		);
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
			'two-letter uppercase'    => [ 'US' ],
			'uppercase with dash'     => [ 'GB-EN' ],
			'alphanumeric'            => [ 'A1' ],
			'sixteen char max length' => [ 'A1-B2-C3-D4-E5-F' ],
		];
	}

	public function provide_invalid_feed_labels(): array {
		return [
			'lowercase'       => [ 'us' ],
			'seventeen chars' => [ 'A1-B2-C3-D4-E5-F6' ],
			'twenty chars'    => [ 'A1-B2-C3-D4-E5-F6-G7' ],
			'underscore'      => [ 'GB_EN' ],
			'period'          => [ 'GB.EN' ],
			'space'           => [ 'GB EN' ],
			'at sign'         => [ 'GB@EN' ],
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
		$this->wpml->method( 'can_convert_currency' )->willReturn( true );

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

	public function test_update_market_secondary_schedules_language_cleanup_when_language_removed(): void {
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

		// Feed labels no longer encode the language, so the keys are the
		// market's label variants; the cleanup job narrows the deletion to the
		// removed languages by each product's own post language.
		$this->language_cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'keys'              => [ 'GB', 'GB-GBP' ],
					'removed_languages' => [ 'cy' ],
				]
			);

		$this->market_service->update_market( 'gb', [ 'language' => [ 'en' ] ] );
	}

	public function test_update_market_secondary_does_not_schedule_language_cleanup_when_language_added(): void {
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

		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'en', 'cy' ] ] );
	}

	public function test_update_market_secondary_does_not_schedule_language_cleanup_when_language_reordered(): void {
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

		$this->language_cleanup_job->expects( $this->never() )->method( 'schedule' );

		$this->market_service->update_market( 'gb', [ 'language' => [ 'cy', 'en' ] ] );
	}

	public function test_update_market_secondary_uses_old_feed_label_when_feed_label_also_changes(): void {
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

		$this->language_cleanup_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				[
					'keys'              => [ 'GB', 'GB-GBP' ],
					'removed_languages' => [ 'cy' ],
				]
			);

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

	public function test_get_market_feed_label_appends_uppercase_currency(): void {
		$this->assertSame( 'BE-EUR', $this->market_service->get_market_feed_label( 'BE', 'EUR' ) );
		$this->assertSame( 'BE-EUR', $this->market_service->get_market_feed_label( 'BE', 'eur' ) );
		$this->assertSame( 'FR-USD', $this->market_service->get_market_feed_label( 'FR', 'USD' ) );
	}

	public function test_get_market_feed_label_falls_back_to_store_currency_when_empty(): void {
		$this->assertSame(
			'BE-' . get_woocommerce_currency(),
			$this->market_service->get_market_feed_label( 'BE', '' )
		);
	}

	public function test_get_all_feed_labels_derives_secondary_labels_from_currency(): void {
		// Language plays no part in the labels: a market with several languages
		// still contributes a single currency-derived label, because Google
		// tracks the language separately on every entry (contentLanguage).
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

		$this->assertSame( [ 'US', 'BE-EUR' ], $this->market_service->get_all_feed_labels() );
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
			[ 'US', 'BE-' . get_woocommerce_currency() ],
			$this->market_service->get_all_feed_labels()
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

		$this->assertSame( [ 'BE-EUR' ], $this->market_service->get_feed_labels_for_language( 'fr' ) );
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
