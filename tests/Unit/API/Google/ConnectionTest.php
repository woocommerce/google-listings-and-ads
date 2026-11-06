<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantAccessCircuitBreaker;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GuzzleClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class ConnectionTest extends UnitTest {

	use GuzzleClientTrait;

	/** @var MockObject|Ads $ads */
	protected $ads;

	/** @var MockObject|Merchant $merchant */
	protected $merchant;

	/** @var MockObject|MerchantAccessCircuitBreaker $merchant_access_breaker */
	protected $merchant_access_breaker;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var Container $container */
	protected $container;

	/** @var Connection $connection */
	protected $connection;

	protected const TEST_EMAIL       = 'john@doe.email';
	protected const TEST_MERCHANT_ID = 23456781;
	protected const TEST_ADS_ID      = 12345678;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->guzzle_client_setup();

		$this->ads                     = $this->createMock( Ads::class );
		$this->merchant                = $this->createMock( Merchant::class );
		$this->merchant_access_breaker = $this->createMock( MerchantAccessCircuitBreaker::class );
		$this->options                 = $this->createMock( OptionsInterface::class );

		$this->container->addShared( Ads::class, $this->ads );
		$this->container->addShared( Merchant::class, $this->merchant );
		$this->container->addShared( MerchantAccessCircuitBreaker::class, $this->merchant_access_breaker );

		$this->connection = new Connection();
		$this->connection->set_container( $this->container );
		$this->connection->set_options_object( $this->options );
	}

	public function test_get_access_status_when_not_connected() {
		$this->generate_request_mock( [ 'status' => 'disconnected' ] );

		$this->merchant->expects( $this->never() )->method( 'check_access' );
		$this->ads->expects( $this->never() )->method( 'check_access' );

		$this->assertEquals(
			[ 'status' => 'disconnected' ],
			$this->connection->get_access_status()
		);
	}

	public function test_get_access_status_without_connected_accounts() {
		$this->generate_request_mock( $this->connected_response() );

		$this->assertEquals(
			$this->connected_response(),
			$this->connection->get_access_status()
		);
	}

	public function test_get_access_status_keeps_unknown_access() {
		$this->generate_request_mock( $this->connected_response() );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->merchant->method( 'check_access' )->with( self::TEST_EMAIL )->willReturn( null );
		$this->ads->method( 'check_access' )->with( self::TEST_EMAIL )->willReturn( false );

		$status = $this->connection->get_access_status();

		$this->assertSame( self::TEST_MERCHANT_ID, $status['merchant_account'] );
		$this->assertNull( $status['merchant_access'] );
		$this->assertSame( self::TEST_ADS_ID, $status['ads_account'] );
		$this->assertFalse( $status['ads_access'] );
	}

	public function test_get_reconnect_status_maps_access_to_yes_or_no() {
		$this->generate_request_mock( $this->connected_response() );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->merchant->method( 'check_access' )->willReturn( true );
		$this->ads->method( 'check_access' )->willReturn( null );

		$status = $this->connection->get_reconnect_status();

		$this->assertSame( 'yes', $status['merchant_access'] );
		$this->assertSame( 'no', $status['ads_access'] );
	}

	public function test_confirmed_merchant_access_ends_the_sync_pause() {
		$this->generate_request_mock( $this->connected_response() );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
		$this->merchant->method( 'check_access' )->willReturn( true );

		$this->merchant_access_breaker->expects( $this->once() )->method( 'reset' );

		$this->connection->get_access_status();
	}

	/**
	 * @testWith [ false ]
	 *           [ null ]
	 *
	 * @param bool|null $merchant_access
	 */
	public function test_unconfirmed_merchant_access_keeps_the_sync_pause( ?bool $merchant_access ) {
		$this->generate_request_mock( $this->connected_response() );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
		$this->merchant->method( 'check_access' )->willReturn( $merchant_access );

		$this->merchant_access_breaker->expects( $this->never() )->method( 'reset' );

		$this->connection->get_access_status();
	}

	public function test_get_reconnect_status_without_ads_account() {
		$this->generate_request_mock( $this->connected_response() );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
		$this->merchant->method( 'check_access' )->willReturn( false );

		$status = $this->connection->get_reconnect_status();

		$this->assertSame( 'no', $status['merchant_access'] );
		$this->assertArrayNotHasKey( 'ads_access', $status );
	}

	/**
	 * A connection status response for a connected Google account.
	 *
	 * @return array
	 */
	private function connected_response(): array {
		return [
			'status' => 'connected',
			'email'  => self::TEST_EMAIL,
		];
	}
}
