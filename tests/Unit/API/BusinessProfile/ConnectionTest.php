<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class ConnectionTest extends UnitTest {

	protected const CONNECT_SERVER_ROOT = 'https://wcs.example.com/';

	protected const SELECTED_LOCATION = [
		'id'        => '5678',
		'accountId' => '1234',
		'title'     => 'Example Store',
		'address'   => '123 Market St, San Francisco, CA, 94103',
		'placeId'   => 'ChIJexample',
		'mapsUri'   => 'https://maps.google.com/?cid=1',
	];

	/** @var Container */
	protected $container;

	/** @var MockObject|LocationService */
	protected $location_service;

	/** @var Connection */
	protected $connection;

	/** @var array Requests sent through the mocked Guzzle client. */
	protected $history = [];

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', self::CONNECT_SERVER_ROOT );

		$this->location_service = $this->createMock( LocationService::class );

		$this->connection = new Connection( $this->location_service );
		$this->connection->set_container( $this->container );
	}

	/**
	 * Queue Guzzle responses for the Connect Server calls, recording each request.
	 *
	 * @param Response|ConnectException ...$responses
	 */
	protected function queue_guzzle_responses( ...$responses ): void {
		$this->history = [];
		$stack         = HandlerStack::create( new MockHandler( $responses ) );
		$stack->push( Middleware::history( $this->history ) );
		$this->container->add( Client::class, new Client( [ 'handler' => $stack ] ) );
	}

	public function test_connect_returns_oauth_url_on_success() {
		$this->queue_guzzle_responses(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) )
		);

		$url = $this->connection->connect( 'https://example.com/return' );

		$this->assertSame( 'https://accounts.google.com/o/oauth2/auth', $url );
	}

	public function test_connect_requests_business_profile_scope_on_shared_connection() {
		$this->queue_guzzle_responses(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) )
		);

		$this->connection->connect( 'https://example.com/return' );

		$request = $this->history[0]['request'];
		$body    = json_decode( (string) $request->getBody(), true );
		$this->assertSame( 'POST', $request->getMethod() );
		$this->assertSame( self::CONNECT_SERVER_ROOT . 'google/connection/google-mc', (string) $request->getUri() );
		$this->assertSame( [ Connection::SCOPE_BUSINESS_PROFILE ], $body['additionalScopes'] );
		$this->assertSame( 'https://example.com/return', $body['returnUrl'] );
	}

	public function test_connect_throws_when_response_has_no_oauth_url() {
		$this->queue_guzzle_responses( new Response( 200, [], wp_json_encode( [ 'status' => 'ok' ] ) ) );

		$this->expectException( Exception::class );
		$this->connection->connect( 'https://example.com/return' );
	}

	public function test_connect_throws_when_the_request_itself_fails() {
		$this->queue_guzzle_responses(
			new ConnectException( 'Connection timed out', new Request( 'POST', self::CONNECT_SERVER_ROOT ) )
		);

		$this->expectException( Exception::class );
		$this->connection->connect( 'https://example.com/return' );
	}

	public function test_connect_includes_login_hint_when_provided() {
		$this->queue_guzzle_responses(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) )
		);

		$this->connection->connect( 'https://example.com/return', 'merchant@example.com' );

		$body = json_decode( (string) $this->history[0]['request']->getBody(), true );
		$this->assertSame( 'merchant@example.com', $body['loginHint'] );
	}

	public function test_connect_omits_login_hint_when_not_provided() {
		$this->queue_guzzle_responses(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) )
		);

		$this->connection->connect( 'https://example.com/return' );

		$body = json_decode( (string) $this->history[0]['request']->getBody(), true );
		$this->assertArrayNotHasKey( 'loginHint', $body );
	}

	public function test_disconnect_is_purely_local() {
		// No Client::class in the container, so any remote call would throw and fail this test.
		$this->location_service->expects( $this->once() )->method( 'clear_selected_location' );

		$message = $this->connection->disconnect();

		$this->assertSame( 'Successfully disconnected.', $message );
	}

	public function test_get_status_returns_disconnected_when_no_location_is_stored() {
		// No Client::class in the container, so any remote call would throw and fail this test.
		$this->location_service->method( 'get_selected_location' )->willReturn( null );

		$this->assertSame( [ 'status' => Connection::STATUS_DISCONNECTED ], $this->connection->get_status() );
	}

	public function test_get_status_returns_stored_location_when_connected() {
		// No Client::class in the container, so any remote call would throw and fail this test.
		$this->location_service->method( 'get_selected_location' )->willReturn( self::SELECTED_LOCATION );

		$this->assertSame(
			array_merge( [ 'status' => Connection::STATUS_CONNECTED ], self::SELECTED_LOCATION ),
			$this->connection->get_status()
		);
	}
}
