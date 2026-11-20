<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidOption;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class OptionsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class OptionsTest extends UnitTest {

	private const PREFIXED_MARKETS = 'gla_markets';

	/**
	 * @param array<string, mixed> $storage
	 */
	private function create_options_with_storage( array &$storage ): Options {
		$wp = $this->createMock( WP::class );

		$wp->method( 'get_option' )->willReturnCallback(
			function ( string $option, $default_value = null ) use ( &$storage ) {
				return array_key_exists( $option, $storage ) ? $storage[ $option ] : $default_value;
			}
		);

		$wp->method( 'add_option' )->willReturnCallback(
			function ( string $option, $value ) use ( &$storage ) {
				if ( array_key_exists( $option, $storage ) ) {
					return false;
				}
				$storage[ $option ] = $value;

				return true;
			}
		);

		$wp->method( 'update_option' )->willReturnCallback(
			function ( string $option, $value ) use ( &$storage ) {
				$storage[ $option ] = $value;

				return true;
			}
		);

		$wp->method( 'delete_option' )->willReturnCallback(
			function ( string $option ) use ( &$storage ) {
				unset( $storage[ $option ] );

				return true;
			}
		);

		$options = $this->getMockBuilder( Options::class )
			->onlyMethods( [ 'get_merchant_id', 'get_ads_id' ] )
			->getMock();

		$options->set_wp_proxy_object( $wp );

		return $options;
	}

	public function test_markets_is_registered_as_valid_option(): void {
		$this->assertArrayHasKey( OptionsInterface::MARKETS, OptionsInterface::VALID_OPTIONS );
		$this->assertTrue(
			in_array( OptionsInterface::MARKETS, Options::get_all_option_keys(), true ),
			'MARKETS should be included in get_all_option_keys()'
		);
	}

	public function test_get_throws_for_invalid_option_name(): void {
		$storage = [];
		$options = $this->create_options_with_storage( $storage );

		$this->expectException( InvalidOption::class );
		$options->get( 'not_a_registered_option' );
	}

	public function test_add_and_get_markets(): void {
		$storage = [];
		$options = $this->create_options_with_storage( $storage );

		$markets = [
			[
				'id'     => 'primary',
				'locale' => 'en_US',
			],
		];

		$this->assertTrue( $options->add( OptionsInterface::MARKETS, $markets ) );
		$this->assertSame( $markets, $options->get( OptionsInterface::MARKETS ) );
		$this->assertArrayHasKey( self::PREFIXED_MARKETS, $storage );
		$this->assertSame( $markets, $storage[ self::PREFIXED_MARKETS ] );
	}

	public function test_update_markets(): void {
		$storage = [];
		$options = $this->create_options_with_storage( $storage );

		$options->add( OptionsInterface::MARKETS, [ 'version' => 1 ] );
		$updated = [
			'version' => 2,
			'regions' => [ 'EU' ],
		];

		$this->assertTrue( $options->update( OptionsInterface::MARKETS, $updated ) );
		$this->assertSame( $updated, $options->get( OptionsInterface::MARKETS ) );
		$this->assertSame( $updated, $storage[ self::PREFIXED_MARKETS ] );
	}

	public function test_delete_markets(): void {
		$storage = [];
		$options = $this->create_options_with_storage( $storage );

		$options->add( OptionsInterface::MARKETS, [ 'keep' => false ] );
		$this->assertArrayHasKey( self::PREFIXED_MARKETS, $storage );

		$this->assertTrue( $options->delete( OptionsInterface::MARKETS ) );
		$this->assertArrayNotHasKey( self::PREFIXED_MARKETS, $storage );
		$this->assertSame( [], $options->get( OptionsInterface::MARKETS, [] ) );
	}
}
