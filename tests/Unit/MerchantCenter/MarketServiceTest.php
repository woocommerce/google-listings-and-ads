<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

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

	/** @var MarketService */
	protected $market_service;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->options         = $this->createMock( OptionsInterface::class );

		$this->market_service = new MarketService( $this->target_audience );
		$this->market_service->set_options_object( $this->options );
	}

	public function test_get_markets_returns_stored_markets(): void {
		$stored = [
			'us' => [
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			],
		];

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( $stored );

		$this->assertSame( $stored, $this->market_service->get_markets() );
	}

	public function test_get_markets_falls_back_to_default_when_empty(): void {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( null );

		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

		$result = $this->market_service->get_markets();

		$this->assertCount( 1, $result );
		$this->assertSame( 'US', $result[0]['country'] );
	}

	public function test_update_markets_delegates_to_options(): void {
		$markets = [
			'us' => [
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			],
		];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MARKETS, $markets );

		$this->market_service->update_markets( $markets );
	}

	public function test_build_default_markets_returns_correct_structure(): void {
		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'AU' );

		$result = $this->market_service->build_default_markets();

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'country', $result[0] );
		$this->assertArrayHasKey( 'language', $result[0] );
		$this->assertArrayHasKey( 'currency', $result[0] );
		$this->assertArrayHasKey( 'feedLabel', $result[0] );
		$this->assertSame( 'AU', $result[0]['country'] );
		$this->assertSame( 'AU', $result[0]['feedLabel'] );
		$this->assertNotEmpty( $result[0]['language'] );
		$this->assertNotEmpty( $result[0]['currency'] );
	}

	public function test_get_primary_market_returns_default_market(): void {
		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'GB' );

		$result = $this->market_service->get_primary_market();

		$this->assertIsArray( $result );
		$this->assertSame( 'GB', $result['country'] );
		$this->assertSame( 'GB', $result['feedLabel'] );
	}

	public function test_get_market_returns_market_by_id(): void {
		$stored = [
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

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( $stored );

		$result = $this->market_service->get_market( 'gb' );

		$this->assertSame( $stored['gb'], $result );
	}

	public function test_get_market_returns_null_for_unknown_id(): void {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( null );

		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

		$result = $this->market_service->get_market( 'nonexistent' );

		$this->assertNull( $result );
	}

	public function test_add_market_appends_and_persists(): void {
		$existing   = [
			'us' => [
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			],
		];
		$new_config = [
			'country'   => 'GB',
			'language'  => 'en',
			'currency'  => 'GBP',
			'feedLabel' => 'GB',
		];
		$expected   = array_merge( $existing, [ 'gb' => $new_config ] );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( $existing );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MARKETS, $expected );

		$this->market_service->add_market( 'gb', $new_config );
	}

	public function test_update_market_merges_config_and_persists(): void {
		$existing = [
			'us' => [
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			],
		];
		$update   = [ 'currency' => 'CAD' ];
		$expected = [
			'us' => [
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'CAD',
				'feedLabel' => 'US',
			],
		];

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( $existing );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MARKETS, $expected );

		$this->market_service->update_market( 'us', $update );
	}

	public function test_update_market_creates_market_if_not_exists(): void {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( null );

		$this->target_audience->expects( $this->once() )
			->method( 'get_main_target_country' )
			->willReturn( 'US' );

		$config = [
			'country'   => 'GB',
			'language'  => 'en',
			'currency'  => 'GBP',
			'feedLabel' => 'GB',
		];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MARKETS,
				$this->callback(
					function ( $markets ) use ( $config ) {
						return isset( $markets['gb'] ) && $markets['gb'] === $config;
					}
				)
			);

		$this->market_service->update_market( 'gb', $config );
	}

	public function test_delete_market_removes_and_persists(): void {
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
		$expected = [
			'gb' => [
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
			],
		];

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS )
			->willReturn( $existing );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MARKETS, $expected );

		$this->market_service->delete_market( 'us' );
	}

	public function test_has_multilingual_support_returns_false(): void {
		$this->assertFalse( $this->market_service->has_multilingual_support() );
	}
}
