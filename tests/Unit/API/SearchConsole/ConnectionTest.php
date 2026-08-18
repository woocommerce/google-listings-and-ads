<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\BadResponseException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class ConnectionTest extends UnitTest {

	/** @var Container $container */
	protected $container;

	/** @var Connection $connection */
	protected $connection;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	protected const CONNECT_SERVER_ROOT = 'https://wcs.example.com/';

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', self::CONNECT_SERVER_ROOT );

		$this->options         = $this->createMock( OptionsInterface::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );

		$this->connection = new Connection();
		$this->connection->set_container( $this->container );
		$this->connection->set_options_object( $this->options );
		$this->connection->set_merchant_center_object( $this->merchant_center );
	}

	public function test_connect_returns_oauth_url_on_success() {
		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/oauth' ] ) ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$url = $this->connection->connect( 'https://example.com/return' );

		$this->assertEquals( 'https://accounts.google.com/oauth', $url );
	}

	public function test_connect_throws_exception_when_oauth_url_missing() {
		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [] ) ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to connect Search Console account' );

		$this->connection->connect( 'https://example.com/return' );
	}

	public function test_connect_throws_exception_on_client_exception() {
		$mock_handler = new MockHandler(
			[
				new RequestException(
					'Connection timeout',
					new Request( 'POST', 'https://example.com' )
				),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to connect Search Console account' );

		$this->connection->connect( 'https://example.com/return' );
	}

	public function test_disconnect_returns_response_body_on_success() {
		$mock_handler = new MockHandler(
			[
				new Response( 200, [], 'disconnected' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::SEARCH_CONSOLE );

		$this->assertEquals( 'disconnected', $this->connection->disconnect() );
	}

	public function test_disconnect_returns_error_message_on_client_exception() {
		$mock_handler = new MockHandler(
			[
				new RequestException(
					'Connection timeout',
					new Request( 'DELETE', 'https://example.com' )
				),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::SEARCH_CONSOLE );

		$this->assertStringContainsString( 'Connection timeout', $this->connection->disconnect() );
	}

	public function test_get_status_returns_decoded_response_on_success() {
		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [ 'status' => 'connected' ] ) ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->assertEquals( [ 'status' => 'connected' ], $this->connection->get_status() );
	}

	public function test_get_status_throws_exception_on_client_exception() {
		$mock_handler = new MockHandler(
			[
				new RequestException(
					'Connection timeout',
					new Request( 'GET', 'https://example.com' )
				),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error retrieving status' );
		$this->expectExceptionCode( 0 );

		$this->connection->get_status();
	}

	public function test_get_status_preserves_http_status_code_on_bad_response_exception() {
		$mock_handler = new MockHandler(
			[
				new BadResponseException(
					'Unauthorized',
					new Request( 'GET', 'https://example.com' ),
					new Response( 401 )
				),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 401 );

		$this->connection->get_status();
	}

	public function test_should_skip_auth_returns_true_when_merchant_center_connected() {
		$this->merchant_center->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->assertTrue( $this->connection->should_skip_auth() );
	}

	public function test_should_skip_auth_returns_false_when_merchant_center_not_connected() {
		$this->merchant_center->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$this->assertFalse( $this->connection->should_skip_auth() );
	}

	public function test_get_connection_data_returns_default_when_nothing_stored() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SEARCH_CONSOLE, $this->anything() )
			->willReturnArgument( 1 );

		$this->assertEquals(
			[
				'property'      => null,
				'property_type' => null,
				'verified'      => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
				'state'         => null,
			],
			$this->connection->get_connection_data()
		);
	}

	public function test_get_connection_data_returns_stored_value() {
		$stored = [
			'property'      => 'https://example.com/',
			'property_type' => 'url_prefix',
			'verified'      => SiteVerification::VERIFICATION_STATUS_VERIFIED,
			'state'         => null,
		];

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SEARCH_CONSOLE, $this->anything() )
			->willReturn( $stored );

		$this->assertEquals( $stored, $this->connection->get_connection_data() );
	}

	public function test_update_connection_data_merges_onto_existing_data() {
		$this->options->method( 'get' )
			->with( OptionsInterface::SEARCH_CONSOLE, $this->anything() )
			->willReturn(
				[
					'property'      => 'https://example.com/',
					'property_type' => 'url_prefix',
					'verified'      => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
					'state'         => null,
				]
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::SEARCH_CONSOLE,
				[
					'property'      => 'https://example.com/',
					'property_type' => 'url_prefix',
					'verified'      => SiteVerification::VERIFICATION_STATUS_VERIFIED,
					'state'         => null,
				]
			)
			->willReturn( true );

		$this->assertTrue(
			$this->connection->update_connection_data( [ 'verified' => SiteVerification::VERIFICATION_STATUS_VERIFIED ] )
		);
	}

	public function test_clear_connection_data_deletes_the_option() {
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::SEARCH_CONSOLE )
			->willReturn( true );

		$this->assertTrue( $this->connection->clear_connection_data() );
	}

	public function test_get_connection_status_returns_disconnected_when_remote_status_is_not_connected() {
		$this->options->method( 'get' )->willReturn( self::default_connection_data() );

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [ 'status' => 'disconnected' ] ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SEARCH_CONSOLE, $this->callback( fn( $data ) => Connection::STATE_DISCONNECTED === $data['state'] ) );

		$this->assertEquals( Connection::STATE_DISCONNECTED, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_connected_when_property_is_verified() {
		$this->options->method( 'get' )->willReturn(
			self::default_connection_data(
				[
					'property' => 'https://example.com/',
					'verified' => SiteVerification::VERIFICATION_STATUS_VERIFIED,
				]
			)
		);

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [ 'status' => 'connected' ] ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SEARCH_CONSOLE, $this->callback( fn( $data ) => Connection::STATE_CONNECTED === $data['state'] ) );

		$this->assertEquals( Connection::STATE_CONNECTED, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_incomplete_when_no_property_selected_yet() {
		$this->options->method( 'get' )->willReturn( self::default_connection_data() );

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [ 'status' => 'connected' ] ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->assertEquals( Connection::STATE_INCOMPLETE, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_action_needed_when_property_selected_but_not_verified() {
		$this->options->method( 'get' )->willReturn(
			self::default_connection_data( [ 'property' => 'https://example.com/' ] )
		);

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], wp_json_encode( [ 'status' => 'connected' ] ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->assertEquals( Connection::STATE_ACTION_NEEDED, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_reconnect_when_previously_connected_and_now_unauthorized() {
		$this->options->method( 'get' )->willReturn(
			self::default_connection_data( [ 'state' => Connection::STATE_CONNECTED ] )
		);

		$mock_handler = new MockHandler(
			[
				new BadResponseException( 'Unauthorized', new Request( 'GET', 'https://example.com' ), new Response( 401 ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->assertEquals( Connection::STATE_RECONNECT, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_connection_failed_when_never_connected_and_status_errors() {
		$this->options->method( 'get' )->willReturn( self::default_connection_data() );

		$mock_handler = new MockHandler(
			[
				new BadResponseException( 'Forbidden', new Request( 'GET', 'https://example.com' ), new Response( 403 ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->assertEquals( Connection::STATE_CONNECTION_FAILED, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_transient_error_on_server_error_without_persisting_state() {
		$this->options->method( 'get' )->willReturn(
			self::default_connection_data( [ 'state' => Connection::STATE_CONNECTED ] )
		);

		$mock_handler = new MockHandler(
			[
				new BadResponseException( 'Service unavailable', new Request( 'GET', 'https://example.com' ), new Response( 503 ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->options->expects( $this->never() )->method( 'update' );

		$this->assertEquals( Connection::STATE_TRANSIENT_ERROR, $this->connection->get_connection_status()['status'] );
	}

	public function test_get_connection_status_returns_transient_error_on_network_failure_without_persisting_state() {
		$this->options->method( 'get' )->willReturn(
			self::default_connection_data( [ 'state' => Connection::STATE_CONNECTED ] )
		);

		$mock_handler = new MockHandler(
			[
				new RequestException( 'Connection timeout', new Request( 'GET', 'https://example.com' ) ),
			]
		);
		$this->container->add( Client::class, new Client( [ 'handler' => HandlerStack::create( $mock_handler ) ] ) );

		$this->options->expects( $this->never() )->method( 'update' );

		$this->assertEquals( Connection::STATE_TRANSIENT_ERROR, $this->connection->get_connection_status()['status'] );
	}

	/**
	 * @param array $overrides Fields to override on top of the default connection data shape.
	 *
	 * @return array
	 */
	protected static function default_connection_data( array $overrides = [] ): array {
		return array_merge(
			[
				'property'      => null,
				'property_type' => null,
				'verified'      => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
				'state'         => null,
			],
			$overrides
		);
	}
}
