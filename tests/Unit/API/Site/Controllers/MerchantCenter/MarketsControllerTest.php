<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\MarketsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 */
class MarketsControllerTest extends RESTControllerUnitTest {

	/** @var MarketsController */
	protected $controller;

	/** @var MockObject|MarketService */
	protected $market_service;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var array The Merchant Center option the controller reads the shipping method from. */
	protected $merchant_center_settings;

	protected const ROUTE_MARKETS              = '/wc/gla/mc/markets';
	protected const ROUTE_LANGUAGES_CURRENCIES = '/wc/gla/mc/markets/languages-currencies';
	protected const ROUTE_MARKET               = '/wc/gla/mc/markets/';

	protected const PRIMARY_MARKET = [
		'id'            => 'primary',
		'label'         => 'Primary Market',
		'countries'     => [ 'US' ],
		'country'       => null,
		'language'      => [ 'en' ],
		'currency'      => [ 'USD' ],
		'feed_label'    => null,
		'shipping_rate' => 'flat',
		'shipping_time' => 'flat',
		'shipping'      => [
			'rate_type'               => 'flat',
			'time_type'               => 'flat',
			'flat_rate'               => 5.0,
			'free_shipping_threshold' => 50.0,
			'flat_time'               => 1,
			'flat_max_time'           => 3,
		],
	];

	protected const SECONDARY_MARKET = [
		'country'       => 'GB',
		'label'         => 'United Kingdom (UK)',
		'countries'     => [ 'GB' ],
		'language'      => [ 'en' ],
		'currency'      => [ 'GBP' ],
		'feed_label'    => 'GB',
		'shipping_rate' => 'flat',
		'shipping_time' => 'flat',
		'shipping'      => [
			'rate_type'               => 'flat',
			'time_type'               => 'flat',
			'flat_rate'               => 9.99,
			'free_shipping_threshold' => null,
			'flat_time'               => 2,
			'flat_max_time'           => 6,
		],
	];

	public function setUp(): void {
		parent::setUp();

		$this->market_service = $this->createMock( MarketService::class );

		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => self::PRIMARY_MARKET,
				'gb'      => self::SECONDARY_MARKET,
			]
		);

		// The create endpoint reads the store-wide shipping method from this option to decide
		// how a market ID is derived. Flat rate is the mode that derives the ID from the
		// country, so tests for that set this to flat before making a request.
		$this->merchant_center_settings = [
			'shipping_rate' => 'automatic',
			'shipping_time' => 'flat',
		];

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $key, $fallback = null ) {
					return OptionsInterface::MERCHANT_CENTER === $key
						? $this->merchant_center_settings
						: $fallback;
				}
			);

		$this->controller = new MarketsController( $this->server, $this->market_service );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_get_markets_returns_200(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
	}

	public function test_get_markets_returns_array_with_ids(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();

		$this->assertCount( 2, $data );
		$this->assertEquals( 'primary', $data[0]['id'] );
		$this->assertEquals( 'gb', $data[1]['id'] );
	}

	public function test_get_markets_primary_has_correct_keys(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();
		$primary  = $data[0];

		$this->assertArrayHasKey( 'id', $primary );
		$this->assertArrayHasKey( 'label', $primary );
		$this->assertArrayHasKey( 'countries', $primary );
		$this->assertArrayHasKey( 'country', $primary );
		$this->assertArrayHasKey( 'feed_label', $primary );
		$this->assertNull( $primary['country'] );
		$this->assertNull( $primary['feed_label'] );
		$this->assertArrayHasKey( 'shipping_rate', $primary );
		$this->assertArrayHasKey( 'shipping_time', $primary );
		$this->assertArrayHasKey( 'shipping', $primary );
	}

	public function test_get_markets_primary_values_from_market_service(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();
		$primary  = $data[0];

		$this->assertEquals( 'Primary Market', $primary['label'] );
		$this->assertEquals( [ 'US' ], $primary['countries'] );
		$this->assertNull( $primary['country'] );
		$this->assertNull( $primary['feed_label'] );
		$this->assertEquals( 'flat', $primary['shipping_rate'] );
		$this->assertEquals( 'flat', $primary['shipping_time'] );
	}

	public function test_get_languages_currencies_returns_200(): void {
		$this->market_service->method( 'get_languages' )->willReturn( [] );
		$this->market_service->method( 'get_currencies' )->willReturn( [] );

		$response = $this->do_request( self::ROUTE_LANGUAGES_CURRENCIES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $response->get_data()['languages'] );
		$this->assertEquals( [], $response->get_data()['currencies'] );
	}

	public function test_get_languages_currencies_returns_languages_from_market_service(): void {
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

		$this->market_service->method( 'get_languages' )->willReturn( $languages );
		$this->market_service->method( 'get_currencies' )->willReturn( [] );

		$response = $this->do_request( self::ROUTE_LANGUAGES_CURRENCIES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $languages, $response->get_data()['languages'] );
		$this->assertEquals( [], $response->get_data()['currencies'] );
	}

	public function test_get_languages_currencies_returns_currencies_from_market_service(): void {
		$currencies = [
			[
				'code'      => 'USD',
				'symbol'    => '$',
				'languages' => [ 'en', 'fr' ],
			],
			[
				'code'      => 'EUR',
				'symbol'    => '€',
				'languages' => [ 'fr' ],
			],
		];

		$this->market_service->method( 'get_languages' )->willReturn( [] );
		$this->market_service->method( 'get_currencies' )->willReturn( $currencies );

		$response = $this->do_request( self::ROUTE_LANGUAGES_CURRENCIES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $response->get_data()['languages'] );
		$this->assertEquals( $currencies, $response->get_data()['currencies'] );
	}

	public function test_languages_currencies_schema_shape(): void {
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'get_languages_currencies_schema_callback' );
		$method->setAccessible( true );
		$callback = $method->invoke( $this->controller );
		$schema   = $callback();

		$this->assertEquals( 'languages-currencies', $schema['title'] );
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'languages', $schema['properties'] );
		$this->assertArrayHasKey( 'currencies', $schema['properties'] );
		$this->assertEquals( 'array', $schema['properties']['languages']['type'] );
		$this->assertEquals( 'array', $schema['properties']['currencies']['type'] );
		$this->assertEquals( 'object', $schema['properties']['languages']['items']['type'] );
		$this->assertArrayHasKey( 'code', $schema['properties']['languages']['items']['properties'] );
		$this->assertArrayHasKey( 'label', $schema['properties']['languages']['items']['properties'] );
		$this->assertArrayHasKey( 'code', $schema['properties']['currencies']['items']['properties'] );
		$this->assertArrayHasKey( 'symbol', $schema['properties']['currencies']['items']['properties'] );
		$this->assertArrayHasKey( 'languages', $schema['properties']['currencies']['items']['properties'] );
		$this->assertEquals( 'array', $schema['properties']['currencies']['items']['properties']['languages']['type'] );
		$this->assertEquals( 'string', $schema['properties']['currencies']['items']['properties']['languages']['items']['type'] );
	}

	public function test_post_market_returns_201_on_success(): void {
		$created_market = [
			'country'       => 'DE',
			'language'      => [ 'de' ],
			'currency'      => [ 'EUR' ],
			'feed_label'    => 'DE',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );

		$this->market_service->method( 'add_market' )
			->willReturn( $created_market + [ 'id' => 'de' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'DE',
				'language' => [ 'de' ],
				'currency' => [ 'EUR' ],
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'de', $data['id'] );
		$this->assertEquals( 'DE', $data['country'] );
		$this->assertEquals( [ 'EUR' ], $data['currency'] );
		$this->assertEquals( 'DE', $data['feed_label'] );
		$this->assertEquals( 'flat', $data['shipping_rate'] );
	}

	public function test_post_market_forwards_the_shipping_object_to_add_market(): void {
		// Regression (GOOWOO-937): the create callback must pass the submitted shipping into the
		// config so add_market() can write it through, mirroring the PUT path. Before, shipping
		// was read only on update, so a newly created market's shipping was silently dropped.
		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );
		$this->market_service->method( 'get_market' )->willReturn( null );

		$captured = null;
		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->with(
				'de',
				$this->callback(
					function ( $config ) use ( &$captured ) {
						$captured = $config;
						return true;
					}
				)
			);

		$this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'DE',
				'shipping' => [
					'flat_rate'               => 99,
					'free_shipping_threshold' => 500,
					'flat_time'               => 3,
					'flat_max_time'           => 9,
				],
			]
		);

		$this->assertSame(
			[
				'flat_rate'               => 99.0,
				'free_shipping_threshold' => 500.0,
				'flat_time'               => 3,
				'flat_max_time'           => 9,
			],
			$captured['shipping'] ?? null
		);
	}

	public function test_post_market_returns_400_missing_required_field(): void {
		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'language' => [ 'en' ],
				'currency' => [ 'GBP' ],
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_post_market_without_language_currency_returns_201(): void {
		$created_market = [
			'country'       => 'GB',
			'language'      => [ 'en' ],
			'currency'      => [ 'USD' ],
			'feed_label'    => 'GB',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'add_market' )
			->with(
				'gb',
				$this->callback(
					function ( $config ) {
						return 'GB' === $config['country']
							&& ! array_key_exists( 'language', $config )
							&& ! array_key_exists( 'currency', $config );
					}
				)
			)
			->willReturn( $created_market + [ 'id' => 'gb' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country' => 'GB',
			]
		);

		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_post_market_with_empty_language_currency_arrays_passes_empty_arrays(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'add_market' )
			->with(
				'gb',
				$this->callback(
					function ( $config ) {
						return 'GB' === $config['country']
							&& [] === $config['language']
							&& [] === $config['currency'];
					}
				)
			)
			->willReturn(
				[
					'id'      => 'gb',
					'country' => 'GB',
				]
			);

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'GB',
				'language' => [],
				'currency' => [],
			]
		);

		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_post_market_flat_mode_derives_id_from_country_not_feed_label(): void {
		$this->merchant_center_settings = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
		];

		$id_source = null;
		$this->market_service->method( 'generate_market_id' )
			->willReturnCallback(
				function ( string $source ) use ( &$id_source ) {
					$id_source = $source;
					return 'cm';
				}
			);

		$this->market_service->method( 'add_market' )->willReturn(
			[
				'id'         => 'cm',
				'country'    => 'CM',
				'feed_label' => 'CM',
			]
		);

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'    => 'CM',
				'feed_label' => 'MYLABEL',
			]
		);

		// A flat market always takes its ID from the country, so the submitted label cannot
		// produce an ID that differs from the market the Markets list shows.
		$this->assertSame( 'CM', $id_source );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertSame( 'cm', $response->get_data()['id'] );
		$this->assertSame( 'CM', $response->get_data()['feed_label'] );
	}

	public function test_post_market_non_flat_mode_derives_id_from_feed_label(): void {
		$id_source = null;
		$this->market_service->method( 'generate_market_id' )
			->willReturnCallback(
				function ( string $source ) use ( &$id_source ) {
					$id_source = $source;
					return 'mylabel';
				}
			);

		$this->market_service->method( 'add_market' )->willReturn( [ 'id' => 'mylabel' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'    => 'CM',
				'feed_label' => 'MYLABEL',
			]
		);

		$this->assertSame( 'MYLABEL', $id_source );
		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_post_market_body_is_the_market_returned_by_the_service(): void {
		$this->merchant_center_settings = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
		];

		$created_market = [
			'id'            => 'cm',
			'country'       => 'CM',
			'countries'     => [ 'CM' ],
			'label'         => 'Cameroon',
			'language'      => [ 'en' ],
			'currency'      => [ 'USD' ],
			'feed_label'    => 'CM',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => 44.0,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'cm' );
		$this->market_service->method( 'add_market' )->willReturn( $created_market );

		$response = $this->do_request( self::ROUTE_MARKETS, 'POST', [ 'country' => 'CM' ] );

		// Complete regardless of whether the merchant has saved the country's shipping values,
		// because the service builds it rather than reading back a market that may not exist yet.
		$this->assertEquals( 201, $response->get_status() );
		$this->assertSame( $created_market, $response->get_data() );
	}

	public function test_post_market_flat_mode_country_in_primary_market_returns_201(): void {
		$this->merchant_center_settings = [
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
		];

		// A country that is currently part of the primary market has no market of its own, so
		// there is nothing to conflict with and it is created and returned as derived.
		$this->market_service->method( 'generate_market_id' )->willReturn( 'cm' );
		$this->market_service->method( 'get_market' )->willReturn( null );
		$this->market_service->method( 'add_market' )->willReturn(
			[
				'id'      => 'cm',
				'country' => 'CM',
			]
		);

		$response = $this->do_request( self::ROUTE_MARKETS, 'POST', [ 'country' => 'CM' ] );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertSame( 'CM', $response->get_data()['country'] );
	}

	public function test_post_market_returns_400_when_add_market_throws_invalid_value(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );

		$this->market_service->method( 'get_market' )
			->willReturn( null );

		$this->market_service->method( 'add_market' )
			->willThrowException( InvalidValue::is_empty( 'country' ) );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'DE',
				'language' => [ 'de' ],
				'currency' => [ 'EUR' ],
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_post_market_returns_400_when_generate_market_id_rejects_reserved_id(): void {
		$this->market_service->method( 'generate_market_id' )
			->willThrowException( new InvalidValue( 'reserved-id rejection' ) );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'XX',
				'language' => [ 'en' ],
				'currency' => [ 'USD' ],
			]
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertSame(
			'Cannot create a market with a reserved ID.',
			$response->get_data()['message']
		);
	}

	public function test_post_market_returns_409_when_id_already_exists(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'GB',
				'language' => [ 'en' ],
				'currency' => [ 'GBP' ],
			]
		);

		$this->assertEquals( 409, $response->get_status() );
	}

	public function test_put_primary_delegates_to_market_service(): void {
		$this->market_service->method( 'get_market' )
			->with( 'primary' )
			->willReturn( self::PRIMARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'primary',
				$this->callback(
					function ( $params ) {
						return 'manual' === $params['shipping_rate'];
					}
				)
			)
			->willReturn(
				array_merge( self::PRIMARY_MARKET, [ 'shipping_rate' => 'manual' ] )
			);

		$response = $this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'manual',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'manual', $response->get_data()['shipping_rate'] );
	}

	public function test_put_secondary_delegates_to_market_service(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'gb',
				$this->callback(
					function ( $params ) {
						return 'automatic' === $params['shipping_rate'];
					}
				)
			)
			->willReturn(
				array_merge( self::SECONDARY_MARKET, [ 'shipping_rate' => 'automatic' ] )
			);

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'shipping_rate' => 'automatic',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_put_returns_400_when_update_throws_invalid_value(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$this->market_service->method( 'update_market' )
			->willThrowException( InvalidValue::is_empty( 'currency' ) );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'currency' => [ 'EUR' ],
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_put_primary_returns_400_invalid_shipping_rate(): void {
		$response = $this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'invalid',
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_put_unknown_market_returns_404(): void {
		$this->market_service->method( 'get_market' )
			->with( 'nonexistent' )
			->willReturn( null );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'nonexistent',
			'PUT',
			[
				'shipping_rate' => 'flat',
			]
		);

		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_delete_market_returns_200_on_success(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$response = $this->do_request( self::ROUTE_MARKET . 'gb', 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['deleted'] );
		$this->assertEquals( 'gb', $response->get_data()['id'] );
	}

	public function test_delete_primary_returns_400(): void {
		$response = $this->do_request( self::ROUTE_MARKET . 'primary', 'DELETE' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_delete_unknown_market_returns_404(): void {
		$this->market_service->method( 'get_market' )
			->with( 'nonexistent' )
			->willReturn( null );

		$response = $this->do_request( self::ROUTE_MARKET . 'nonexistent', 'DELETE' );

		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_get_markets_requires_manage_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->do_request( self::ROUTE_MARKETS );

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_post_market_requires_manage_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'GB',
				'language' => [ 'en' ],
				'currency' => [ 'GBP' ],
			]
		);

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_put_market_requires_manage_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'flat',
			]
		);

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_delete_market_requires_manage_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->do_request( self::ROUTE_MARKET . 'primary', 'DELETE' );

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_get_markets_secondary_has_correct_keys(): void {
		$response  = $this->do_request( self::ROUTE_MARKETS );
		$data      = $response->get_data();
		$secondary = $data[1];

		$this->assertArrayHasKey( 'id', $secondary );
		$this->assertArrayHasKey( 'label', $secondary );
		$this->assertArrayHasKey( 'countries', $secondary );
		$this->assertEquals( 'gb', $secondary['id'] );
		$this->assertEquals( 'United Kingdom (UK)', $secondary['label'] );
		$this->assertEquals( [ 'GB' ], $secondary['countries'] );
		$this->assertSame( 'GB', $secondary['country'] );
		$this->assertSame( 'GB', $secondary['feed_label'] );
		$this->assertArrayHasKey( 'shipping', $secondary );
	}

	public function test_get_single_market_returns_the_secondary_with_its_shipping(): void {
		$this->market_service->method( 'get_market' )->willReturn( self::SECONDARY_MARKET );

		$response = $this->do_request( self::ROUTE_MARKET . 'gb' );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 'gb', $data['id'] );
		$this->assertSame( 'GB', $data['country'] );
		$this->assertSame( 9.99, $data['shipping']['flat_rate'] );
	}

	public function test_get_single_market_resolves_primary(): void {
		$this->market_service->method( 'get_market' )->willReturn( self::PRIMARY_MARKET );

		$response = $this->do_request( self::ROUTE_MARKET . 'primary' );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 'primary', $data['id'] );
		$this->assertSame( 5.0, $data['shipping']['flat_rate'] );
	}

	public function test_get_single_market_returns_404_for_an_unknown_id(): void {
		$this->market_service->method( 'get_market' )->willReturn( null );

		$response = $this->do_request( self::ROUTE_MARKET . 'nope' );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertSame( 'nope', $response->get_data()['id'] );
	}

	public function test_get_markets_exposes_the_shipping_object_for_both_market_types(): void {
		$data = $this->do_request( self::ROUTE_MARKETS )->get_data();

		// Declared sub-properties are context-filtered, so a wrong context would strip these.
		$this->assertSame(
			[
				'rate_type'               => 'flat',
				'time_type'               => 'flat',
				'flat_rate'               => 5.0,
				'free_shipping_threshold' => 50.0,
				'flat_time'               => 1,
				'flat_max_time'           => 3,
			],
			$data[0]['shipping']
		);

		$this->assertSame(
			[
				'rate_type'               => 'flat',
				'time_type'               => 'flat',
				'flat_rate'               => 9.99,
				'free_shipping_threshold' => null,
				'flat_time'               => 2,
				'flat_max_time'           => 6,
			],
			$data[1]['shipping']
		);
	}

	public function test_post_market_without_shipping_mode_succeeds(): void {
		$created_market = [
			'country'       => 'JP',
			'language'      => [ 'ja' ],
			'currency'      => [ 'JPY' ],
			'feed_label'    => 'JP',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'jp' );

		$this->market_service->method( 'add_market' )
			->willReturn( $created_market + [ 'id' => 'jp' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'JP',
				'language' => [ 'ja' ],
				'currency' => [ 'JPY' ],
			]
		);

		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_post_market_free_shipping_cannot_be_set_in_payload(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'jp' );

		$this->market_service->method( 'get_market' )
			->willReturnOnConsecutiveCalls( null, [] );

		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->with(
				$this->anything(),
				$this->callback(
					function ( $config ) {
						return ! array_key_exists( 'free_shipping', $config );
					}
				)
			);

		$this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'       => 'JP',
				'language'      => [ 'ja' ],
				'currency'      => [ 'JPY' ],
				'free_shipping' => 99.0,
			]
		);
	}

	public function test_put_only_sends_writable_params(): void {
		$this->market_service->method( 'get_market' )
			->with( 'primary' )
			->willReturn( self::PRIMARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'primary',
				$this->callback(
					function ( $params ) {
						return isset( $params['shipping_rate'] )
							&& isset( $params['shipping'] )
							&& ! isset( $params['id'] )
							&& ! isset( $params['label'] )
							&& ! isset( $params['free_shipping'] );
					}
				)
			)
			->willReturn( self::PRIMARY_MARKET );

		$this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'flat',
				'shipping'      => [ 'flat_rate' => 4.0 ],
			]
		);
	}

	public function test_put_returns_400_when_the_service_rejects_the_shipping_window(): void {
		$this->market_service->method( 'get_market' )->willReturn( self::SECONDARY_MARKET );
		$this->market_service->method( 'update_market' )
			->willThrowException( new InvalidValue( 'The minimum delivery time (9) cannot be greater than the maximum (4).' ) );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'shipping' => [
					'flat_time'     => 9,
					'flat_max_time' => 4,
				],
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_put_rejects_a_negative_shipping_value(): void {
		$this->market_service->method( 'get_market' )->willReturn( self::SECONDARY_MARKET );
		$this->market_service->expects( $this->never() )->method( 'update_market' );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'shipping' => [ 'flat_rate' => -5 ],
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_put_forwards_the_shipping_object_to_the_service(): void {
		$this->market_service->method( 'get_market' )->willReturn( self::SECONDARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'gb',
				$this->callback(
					function ( $params ) {
						return [
							'flat_rate'               => 7.5,
							'free_shipping_threshold' => 40.0,
							'flat_time'               => 2,
							'flat_max_time'           => 5,
						] === $params['shipping'];
					}
				)
			)
			->willReturn( self::SECONDARY_MARKET );

		$this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'shipping' => [
					'flat_rate'               => 7.5,
					'free_shipping_threshold' => 40.0,
					'flat_time'               => 2,
					'flat_max_time'           => 5,
				],
			]
		);
	}

	public function test_post_market_accepts_multiple_languages(): void {
		$created_market = [
			'country'       => 'CH',
			'language'      => [ 'de', 'fr', 'it' ],
			'currency'      => [ 'CHF' ],
			'feed_label'    => 'CH',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'ch' );

		$this->market_service->method( 'add_market' )
			->willReturn( $created_market + [ 'id' => 'ch' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'CH',
				'language' => [ 'de', 'fr', 'it' ],
				'currency' => [ 'CHF' ],
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( [ 'de', 'fr', 'it' ], $response->get_data()['language'] );
	}

	public function test_post_market_accepts_multiple_currencies(): void {
		$created_market = [
			'country'       => 'CH',
			'language'      => [ 'de' ],
			'currency'      => [ 'CHF', 'EUR' ],
			'feed_label'    => 'CH',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'ch' );

		$this->market_service->method( 'add_market' )
			->willReturn( $created_market + [ 'id' => 'ch' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'CH',
				'language' => [ 'de' ],
				'currency' => [ 'CHF', 'EUR' ],
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( [ 'CHF', 'EUR' ], $response->get_data()['currency'] );
	}

	public function test_post_market_uses_client_supplied_feed_label(): void {
		$created_market = [
			'country'       => 'GB',
			'language'      => [ 'en' ],
			'currency'      => [ 'GBP' ],
			'feed_label'    => 'GB-EN',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb-en' );

		$this->market_service->method( 'add_market' )
			->with(
				'gb-en',
				$this->callback(
					function ( $config ) {
						return 'GB' === $config['country']
							&& 'GB-EN' === $config['feed_label'];
					}
				)
			)
			->willReturn( $created_market + [ 'id' => 'gb-en' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'GB-EN',
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'gb-en', $data['id'] );
		$this->assertEquals( 'GB-EN', $data['feed_label'] );
	}

	public function test_post_market_falls_back_to_country_when_feed_label_absent(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'add_market' )
			->with(
				'gb',
				$this->callback(
					function ( $config ) {
						return 'GB' === $config['country']
							&& 'GB' === $config['feed_label'];
					}
				)
			)
			->willReturn(
				[
					'id'         => 'gb',
					'country'    => 'GB',
					'feed_label' => 'GB',
				]
			);

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country' => 'GB',
			]
		);

		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_post_market_returns_400_when_feed_label_pattern_invalid(): void {
		$this->market_service->method( 'get_market' )
			->willReturn( null );

		$this->market_service->method( 'add_market' )
			->willThrowException(
				InvalidValue::does_not_match_pattern( 'feed_label', '/^[A-Z0-9-]{1,20}$/', 'gb-en' )
			);

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'    => 'GB',
				'language'   => [ 'en' ],
				'currency'   => [ 'GBP' ],
				'feed_label' => 'gb-en',
			]
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertStringContainsString( 'feed_label', $response->get_data()['message'] );
		$this->assertStringContainsString( 'gb-en', $response->get_data()['message'] );
	}

	public function test_put_passes_feed_label_through(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'gb',
				$this->callback(
					function ( $params ) {
						return isset( $params['feed_label'] )
							&& 'GB-EN' === $params['feed_label'];
					}
				)
			)
			->willReturn(
				array_merge( self::SECONDARY_MARKET, [ 'feed_label' => 'GB-EN' ] )
			);

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'feed_label' => 'GB-EN',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_post_market_passes_exchange_rate_to_add_market(): void {
		$created_market = [
			'country'       => 'DE',
			'language'      => [ 'de' ],
			'currency'      => [ 'EUR' ],
			'exchange_rate' => 0.92,
			'feed_label'    => 'DE',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );

		$this->market_service->expects( $this->once() )
			->method( 'add_market' )
			->with(
				'de',
				$this->callback(
					function ( $config ) {
						return isset( $config['exchange_rate'] )
							&& 0.92 === $config['exchange_rate'];
					}
				)
			)
			->willReturn( $created_market + [ 'id' => 'de' ] );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'       => 'DE',
				'language'      => [ 'de' ],
				'currency'      => [ 'EUR' ],
				'exchange_rate' => 0.92,
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 0.92, $response->get_data()['exchange_rate'] );
	}

	public function test_put_passes_exchange_rate_through(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'gb',
				$this->callback(
					function ( $params ) {
						return isset( $params['exchange_rate'] )
							&& 1.15 === $params['exchange_rate'];
					}
				)
			)
			->willReturn(
				array_merge( self::SECONDARY_MARKET, [ 'exchange_rate' => 1.15 ] )
			);

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'exchange_rate' => 1.15,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1.15, $response->get_data()['exchange_rate'] );
	}
}
