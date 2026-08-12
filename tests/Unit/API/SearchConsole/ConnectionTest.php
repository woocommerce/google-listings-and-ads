<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;

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

	protected const CONNECT_SERVER_ROOT = 'https://wcs.example.com/';

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', self::CONNECT_SERVER_ROOT );

		$this->connection = new Connection();
		$this->connection->set_container( $this->container );
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

		$this->connection->get_status();
	}
}
