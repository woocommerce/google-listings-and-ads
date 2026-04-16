<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\Markets;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class MarketsTest extends UnitTest {

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var Markets */
	protected $markets;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->markets = new Markets();
		$this->markets->set_options_object( $this->options );
	}

	public function test_get_returns_stored_markets(): void {
		$stored = [
			[
				'country'   => 'US',
				'language'  => 'en',
				'currency'  => 'USD',
				'feedLabel' => 'US',
			],
		];

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS, [] )
			->willReturn( $stored );

		$this->assertSame( $stored, $this->markets->get() );
	}

	public function test_get_returns_empty_array_when_not_set(): void {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MARKETS, [] )
			->willReturn( [] );

		$this->assertSame( [], $this->markets->get() );
	}

	public function test_update_persists_markets(): void {
		$markets = [
			[
				'country'   => 'GB',
				'language'  => 'en',
				'currency'  => 'GBP',
				'feedLabel' => 'GB',
			],
		];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MARKETS, $markets )
			->willReturn( true );

		$this->assertTrue( $this->markets->update( $markets ) );
	}

	public function test_update_returns_false_on_failure(): void {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MARKETS, [] )
			->willReturn( false );

		$this->assertFalse( $this->markets->update( [] ) );
	}
}
