<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class OptionsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class OptionsTest extends UnitTest {

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var Options $options */
	protected $options;

	protected const TEST_OPTION          = OptionsInterface::ADS_ACCOUNT_STATE;
	protected const TEST_PREFIXED_OPTION = 'gla_ads_account_state';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp      = $this->createMock( WP::class );
		$this->options = new Options();
		$this->options->set_wp_proxy_object( $this->wp );
	}

	public function test_get_caches_the_value_for_the_request() {
		$this->wp->expects( $this->once() )
			->method( 'get_option' )
			->with( self::TEST_PREFIXED_OPTION, [] )
			->willReturn( [ 'set_id' => [ 'status' => 0 ] ] );

		$this->assertEquals(
			[ 'set_id' => [ 'status' => 0 ] ],
			$this->options->get( self::TEST_OPTION, [] )
		);

		// The second read returns the cached value without another get_option call.
		$this->assertEquals(
			[ 'set_id' => [ 'status' => 0 ] ],
			$this->options->get( self::TEST_OPTION, [] )
		);
	}

	public function test_get_fresh_bypasses_the_caches_and_returns_the_updated_value() {
		$deleted = [];
		$this->wp->method( 'wp_cache_delete' )
			->willReturnCallback(
				function ( $key, $group ) use ( &$deleted ) {
					$deleted[] = [ $key, $group ];
					return true;
				}
			);

		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'get_option' )
			->with( self::TEST_PREFIXED_OPTION, [] )
			->willReturnOnConsecutiveCalls(
				[ 'set_id' => [ 'status' => 0 ] ],
				[ 'set_id' => [ 'status' => 1 ] ]
			);

		// Prime the request cache with the first value.
		$this->assertEquals(
			[ 'set_id' => [ 'status' => 0 ] ],
			$this->options->get( self::TEST_OPTION, [] )
		);

		// The fresh read drops the cached entries and re-reads the option.
		$this->assertEquals(
			[ 'set_id' => [ 'status' => 1 ] ],
			$this->options->get_fresh( self::TEST_OPTION, [] )
		);

		$this->assertEquals(
			[
				[ self::TEST_PREFIXED_OPTION, 'options' ],
				[ 'alloptions', 'options' ],
			],
			$deleted
		);

		// The fresh value replaces the cached one for subsequent reads.
		$this->assertEquals(
			[ 'set_id' => [ 'status' => 1 ] ],
			$this->options->get( self::TEST_OPTION, [] )
		);
	}
}
