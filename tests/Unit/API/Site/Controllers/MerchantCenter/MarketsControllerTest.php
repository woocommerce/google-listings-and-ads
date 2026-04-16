<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\MarketsController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
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

	/** @var MockObject|TargetAudience */
	protected $target_audience;

	/** @var MockObject|OptionsInterface */
	protected $options;

	protected const ROUTE_MARKETS             = '/wc/gla/mc/markets';
	protected const ROUTE_LANGUAGES_CURRENCIES = '/wc/gla/mc/markets/languages-currencies';
	protected const ROUTE_MARKET               = '/wc/gla/mc/markets/';

	/** @var string[] Countries returned by the TargetAudience mock. */
	protected $target_countries = [ 'US' ];

	public function setUp(): void {
		parent::setUp();

		$this->market_service  = $this->createMock( MarketService::class );
		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->options         = $this->createMock( OptionsInterface::class );

		$this->market_service->method( 'get_primary_market' )->willReturn(
			[
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			]
		);

		$this->target_audience->method( 'get_target_countries' )
			->willReturnCallback(
				function () {
					return $this->target_countries;
				}
			);

		$this->market_service->method( 'get_markets' )->willReturn(
			[
				[
					'country'   => 'US',
					'language'  => 'en',
					'currency'  => 'USD',
					'feedLabel' => 'US',
				],
			]
		);

		$this->controller = new MarketsController( $this->server, $this->market_service, $this->target_audience );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_get_markets_returns_200(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
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

	public function test_get_markets_primary_id_is_primary(): void {
		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();

		$this->assertEquals( 'primary', $data[0]['id'] );
		$this->assertEquals( 'Primary Market', $data[0]['label'] );
	}

	public function test_get_markets_primary_countries_from_target_audience(): void {
		$this->target_countries = [ 'AU', 'NZ' ];

		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();

		$this->assertEquals( [ 'AU', 'NZ' ], $data[0]['countries'] );
	}

	public function test_get_markets_primary_shipping_from_options(): void {
		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, [], [ 'shipping_rate' => 'flat', 'shipping_time' => 'manual' ] ],
				]
			);

		$response = $this->do_request( self::ROUTE_MARKETS );
		$data     = $response->get_data();

		$this->assertEquals( 'flat', $data[0]['shipping_rate'] );
		$this->assertEquals( 'manual', $data[0]['shipping_time'] );
	}

	public function test_get_languages_currencies_returns_200(): void {
		$response = $this->do_request( self::ROUTE_LANGUAGES_CURRENCIES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $response->get_data()['languages'] );
		$this->assertEquals( [], $response->get_data()['currencies'] );
	}

	public function test_post_market_returns_501(): void {
		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'country'       => 'GB',
				'language'      => 'en',
				'currency'      => 'GBP',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			]
		);

		$this->assertEquals( 501, $response->get_status() );
	}

	public function test_post_market_returns_400_missing_required_field(): void {
		$response = $this->do_request(
			self::ROUTE_MARKETS,
			'POST',
			[
				'language'      => 'en',
				'currency'      => 'GBP',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_put_primary_returns_200(): void {
		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, [], [ 'shipping_rate' => 'flat', 'shipping_time' => 'flat' ] ],
				]
			);

		$response = $this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'manual',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_put_primary_updates_merchant_center_options(): void {
		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, [], [ 'shipping_rate' => 'flat', 'shipping_time' => 'flat' ] ],
				]
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MERCHANT_CENTER,
				[ 'shipping_rate' => 'manual', 'shipping_time' => 'flat' ]
			);

		$this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'manual',
			]
		);
	}

	public function test_put_primary_partial_update_preserves_existing(): void {
		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, [], [ 'shipping_rate' => 'flat', 'shipping_time' => 'manual' ] ],
				]
			);

		$response = $this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'shipping_rate' => 'automatic',
			]
		);

		$data = $response->get_data();
		$this->assertEquals( 'automatic', $data['shipping_rate'] );
		$this->assertEquals( 'manual', $data['shipping_time'] );
	}

	public function test_put_primary_updates_target_audience_countries(): void {
		$existing_audience = [
			'location'  => 'selected',
			'countries' => [ 'US' ],
		];

		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, [], [] ],
					[ OptionsInterface::TARGET_AUDIENCE, [], $existing_audience ],
				]
			);

		$updated_audience = false;
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$updated_audience ) {
					if ( OptionsInterface::TARGET_AUDIENCE === $key ) {
						$this->assertEquals( 'selected', $value['location'] );
						$this->assertEquals( [ 'US', 'CA' ], $value['countries'] );
						$updated_audience = true;
					}
					return true;
				}
			);

		$this->do_request(
			self::ROUTE_MARKET . 'primary',
			'PUT',
			[
				'countries' => [ 'US', 'CA' ],
			]
		);

		$this->assertTrue( $updated_audience, 'TARGET_AUDIENCE option was not updated.' );
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
				'country'       => 'GB',
				'language'      => 'en',
				'currency'      => 'GBP',
				'shipping_rate' => 'flat',
				'shipping_time' => 'flat',
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
}
