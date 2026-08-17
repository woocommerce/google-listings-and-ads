<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\MarketsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
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

		$this->controller = new MarketsController( $this->server, $this->market_service );
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
		$this->assertArrayNotHasKey( 'feed_label', $primary );
		$this->assertNull( $primary['country'] );
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
		$this->assertArrayNotHasKey( 'feed_label', $primary );
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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created, $created_market ) {
					if ( 'de' === $id && $created ) {
						return $created_market;
					}
					return null;
				}
			);

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
		$this->assertEquals( 'flat', $data['shipping_rate'] );
	}

	public function test_post_market_returns_200_and_the_primary_market_when_the_country_is_folded_in(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );
		$this->market_service->method( 'get_market' )->willReturn( null );
		$this->market_service->method( 'get_primary_market' )->willReturn( self::PRIMARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'add_market_or_merge_into_primary' )
			->with(
				'gb',
				$this->callback(
					function ( $config ) {
						return 'GB' === $config['country'];
					}
				),
				$this->callback(
					function ( $shipping ) {
						return 5.0 === $shipping['flat_rate'] && 3 === $shipping['flat_max_time'];
					}
				)
			)
			->willReturn( true );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'GB',
				'shipping' => [
					'flat_rate'               => 5.0,
					'free_shipping_threshold' => 50.0,
					'flat_time'               => 1,
					'flat_max_time'           => 3,
				],
			]
		);

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertTrue( $data['merged_into_primary'] );
		$this->assertSame( 'primary', $data['id'] );
		$this->assertNull( $data['country'] );
	}

	public function test_post_market_still_returns_201_when_the_shipping_does_not_match(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$created = false;

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created ) {
					if ( 'gb' === $id && $created ) {
						return self::SECONDARY_MARKET;
					}
					return null;
				}
			);

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'GB',
				'shipping' => [
					'flat_rate'               => 9.99,
					'free_shipping_threshold' => null,
					'flat_time'               => 2,
					'flat_max_time'           => 6,
				],
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertArrayNotHasKey( 'merged_into_primary', $response->get_data() );
	}

	public function test_post_market_forwards_a_null_shipping_when_none_was_submitted(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->expects( $this->once() )
			->method( 'add_market_or_merge_into_primary' )
			->with( 'gb', $this->anything(), null )
			->willReturn( false );

		$created = false;
		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function () use ( &$created ) {
					$was    = $created;
					$created = true;
					return $was ? self::SECONDARY_MARKET : null;
				}
			);

		$this->do_request( self::ROUTE_MARKETS, 'POST', [ 'country' => 'GB' ] );
	}

	public function test_get_markets_carries_no_merged_flag(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );

		foreach ( $response->get_data() as $market ) {
			$this->assertArrayNotHasKey( 'merged_into_primary', $market );
		}
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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
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
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created, $created_market ) {
					if ( 'gb' === $id && $created ) {
						return $created_market;
					}
					return null;
				}
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

	public function test_post_market_with_empty_language_currency_arrays_passes_empty_arrays(): void {
		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
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
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created ) {
					if ( 'gb' === $id && $created ) {
						return [ 'country' => 'GB' ];
					}
					return null;
				}
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

	public function test_post_market_returns_400_when_add_market_throws_invalid_value(): void {
		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );

		$this->market_service->method( 'get_market' )
			->willReturn( null );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
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
		$this->assertArrayNotHasKey( 'feed_label', $secondary );
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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'jp' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created, $created_market ) {
					if ( 'jp' === $id && $created ) {
						return $created_market;
					}
					return null;
				}
			);

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
			->method( 'add_market_or_merge_into_primary' )
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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'ch' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created, $created_market ) {
					if ( 'ch' === $id && $created ) {
						return $created_market;
					}
					return null;
				}
			);

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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'ch' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created, $created_market ) {
					if ( 'ch' === $id && $created ) {
						return $created_market;
					}
					return null;
				}
			);

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

	public function test_post_market_with_only_country_derives_the_id_from_it(): void {
		$created = false;

		$this->market_service->expects( $this->once() )
			->method( 'generate_market_id' )
			->with( 'GB' )
			->willReturn( 'gb' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->with(
				'gb',
				$this->callback(
					function ( $config ) {
						return 'GB' === $config['country']
							&& ! array_key_exists( 'feed_label', $config );
					}
				)
			)
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created ) {
					if ( 'gb' === $id && $created ) {
						return [ 'country' => 'GB' ];
					}
					return null;
				}
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

	/**
	 * @dataProvider provide_invalid_countries
	 *
	 * @param string $country
	 */
	public function test_post_market_returns_400_for_a_country_that_is_not_an_alpha_2_code( string $country ): void {
		$this->market_service->expects( $this->never() )->method( 'add_market_or_merge_into_primary' );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[ 'country' => $country ]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function provide_invalid_countries(): array {
		return [
			'multi word'      => [ 'United Kingdom' ],
			'punctuation'     => [ '###' ],
			'whitespace'      => [ '   ' ],
			'leading dash'    => [ '-GB' ],
			'lowercase'       => [ 'gb' ],
			'three letters'   => [ 'GBR' ],
			'one letter'      => [ 'G' ],
			'empty'           => [ '' ],
		];
	}

	public function test_post_market_response_drops_a_leftover_feed_label(): void {
		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );
		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		// A market stored with the leftover key still present.
		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created ) {
					if ( 'gb' === $id && $created ) {
						return array_merge( self::SECONDARY_MARKET, [ 'feed_label' => 'GB-STALE' ] );
					}
					return null;
				}
			);

		$response = $this->do_request( self::ROUTE_MARKETS, 'POST', [ 'country' => 'GB' ] );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertArrayNotHasKey( 'feed_label', $response->get_data() );
		$this->assertSame( 'gb', $response->get_data()['id'] );
	}

	public function test_put_response_drops_a_leftover_feed_label(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$this->market_service->method( 'update_market' )
			->willReturn( array_merge( self::SECONDARY_MARKET, [ 'feed_label' => 'GB-STALE' ] ) );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[ 'currency' => [ 'GBP' ] ]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'feed_label', $response->get_data() );
		$this->assertSame( 'gb', $response->get_data()['id'] );
	}

	public function test_post_market_ignores_a_submitted_feed_label(): void {
		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'gb' );

		$this->market_service->method( 'add_market_or_merge_into_primary' )
			->with(
				'gb',
				$this->callback(
					function ( $config ) {
						return ! array_key_exists( 'feed_label', $config );
					}
				)
			)
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created ) {
					if ( 'gb' === $id && $created ) {
						return [ 'country' => 'GB' ];
					}
					return null;
				}
			);

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'    => 'GB',
				'feed_label' => 'GB-EN',
			]
		);

		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_put_does_not_pass_a_submitted_feed_label_through(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$this->market_service->expects( $this->once() )
			->method( 'update_market' )
			->with(
				'gb',
				$this->callback(
					function ( $params ) {
						return ! array_key_exists( 'feed_label', $params );
					}
				)
			)
			->willReturn( self::SECONDARY_MARKET );

		$response = $this->do_request(
			self::ROUTE_MARKET . 'gb',
			'PUT',
			[
				'feed_label' => 'GB-EN',
				'currency'   => [ 'GBP' ],
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
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'generate_market_id' )->willReturn( 'de' );

		$this->market_service->expects( $this->once() )
			->method( 'add_market_or_merge_into_primary' )
			->with(
				'de',
				$this->callback(
					function ( $config ) {
						return isset( $config['exchange_rate'] )
							&& 0.92 === $config['exchange_rate'];
					}
				)
			)
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;

					return false;
				}
			);

		$this->market_service->method( 'get_market' )
			->willReturnCallback(
				function ( string $id ) use ( &$created, $created_market ) {
					if ( 'de' === $id && $created ) {
						return $created_market;
					}
					return null;
				}
			);

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
