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
		'country'       => 'US',
		'language'      => 'en',
		'currency'      => 'USD',
		'feed_label'    => 'US',
		'shipping_rate' => 'flat',
		'shipping_time' => 'flat',
		'free_shipping' => 50.0,
	];

	protected const SECONDARY_MARKET = [
		'country'       => 'GB',
		'label'         => 'United Kingdom (UK)',
		'countries'     => [ 'GB' ],
		'language'      => 'en',
		'currency'      => 'GBP',
		'feed_label'    => 'GB',
		'shipping_rate' => 'flat',
		'shipping_time' => 'flat',
		'free_shipping' => null,
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
		$this->assertArrayHasKey( 'shipping_rate', $primary );
		$this->assertArrayHasKey( 'shipping_time', $primary );
		$this->assertArrayHasKey( 'free_shipping', $primary );
	}

	public function test_get_markets_primary_values_from_market_service(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();
		$primary  = $data[0];

		$this->assertEquals( 'Primary Market', $primary['label'] );
		$this->assertEquals( [ 'US' ], $primary['countries'] );
		$this->assertEquals( 'flat', $primary['shipping_rate'] );
		$this->assertEquals( 'flat', $primary['shipping_time'] );
		$this->assertEquals( 50.0, $primary['free_shipping'] );
	}

	public function test_get_languages_currencies_returns_200(): void {
		$response = $this->do_request( self::ROUTE_LANGUAGES_CURRENCIES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $response->get_data()['languages'] );
		$this->assertEquals( [], $response->get_data()['currencies'] );
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
	}

	public function test_post_market_returns_201_on_success(): void {
		$created_market = [
			'country'       => 'DE',
			'language'      => 'de',
			'currency'      => 'EUR',
			'feed_label'    => 'DE',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'add_market' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;
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
				'language' => 'de',
				'currency' => 'EUR',
			]
		);

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'de', $data['id'] );
		$this->assertEquals( 'DE', $data['country'] );
		$this->assertEquals( 'EUR', $data['currency'] );
		$this->assertEquals( 'DE', $data['feed_label'] );
		$this->assertEquals( 'flat', $data['shipping_rate'] );
	}

	public function test_post_market_returns_400_missing_required_field(): void {
		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'language' => 'en',
				'currency' => 'GBP',
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_post_market_returns_400_when_add_market_throws_invalid_value(): void {
		$this->market_service->method( 'get_market' )
			->willReturn( null );

		$this->market_service->method( 'add_market' )
			->willThrowException( InvalidValue::is_empty( 'country' ) );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'DE',
				'language' => 'de',
				'currency' => 'EUR',
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_post_market_returns_409_when_id_already_exists(): void {
		$this->market_service->method( 'get_market' )
			->with( 'gb' )
			->willReturn( self::SECONDARY_MARKET );

		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'  => 'GB',
				'language' => 'en',
				'currency' => 'GBP',
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
				'currency' => '',
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
				'language' => 'en',
				'currency' => 'GBP',
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
		$this->assertArrayHasKey( 'free_shipping', $secondary );
		$this->assertEquals( 'gb', $secondary['id'] );
		$this->assertEquals( 'United Kingdom (UK)', $secondary['label'] );
		$this->assertEquals( [ 'GB' ], $secondary['countries'] );
	}

	public function test_post_market_without_shipping_mode_succeeds(): void {
		$created_market = [
			'country'       => 'JP',
			'language'      => 'ja',
			'currency'      => 'JPY',
			'feed_label'    => 'JP',
			'shipping_rate' => 'flat',
			'shipping_time' => 'flat',
			'free_shipping' => null,
		];

		$created = false;

		$this->market_service->method( 'add_market' )
			->willReturnCallback(
				function () use ( &$created ) {
					$created = true;
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
				'language' => 'ja',
				'currency' => 'JPY',
			]
		);

		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_post_market_free_shipping_cannot_be_set_in_payload(): void {
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
				'language'      => 'ja',
				'currency'      => 'JPY',
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
							&& ! isset( $params['id'] )
							&& ! isset( $params['label'] )
							&& ! isset( $params['feed_label'] )
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
			]
		);
	}
}
