<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class RegisteredRoutesTest
 *
 * Sends requests through the routes the plugin registers itself, so the
 * Business Profile services must be wired in the plugin's own container.
 * Only the Connect Server HTTP client is stubbed.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile
 */
class RegisteredRoutesTest extends UnitTest {

	protected const CONNECT_SERVER_ROOT = 'https://wcs.example.com/';
	protected const ROUTE_CONNECT       = '/wc/gla/business-profile/connect';
	protected const ROUTE_CONNECTION    = '/wc/gla/business-profile/connection';

	/** @var OptionsInterface */
	protected $options;

	/** @var RESTServer */
	protected $server;

	/** @var array Requests sent to the Connect Server. */
	protected $history = [];

	public function setUp(): void {
		parent::setUp();

		$this->options = woogle_get_container()->get( OptionsInterface::class );
		$this->options->delete( OptionsInterface::BUSINESS_PROFILE );

		// A fresh server fires rest_api_init, so the plugin registers its own controllers.
		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
		$this->server = woogle_get_container()->get( RESTServer::class );

		$this->login_as_administrator();
	}

	public function tearDown(): void {
		// The connection is a shared service, so give it back the plugin container.
		if ( woogle_get_container()->has( Connection::class ) ) {
			woogle_get_container()->get( Connection::class )->set_container( woogle_get_container() );
		}

		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Stub the Connect Server responses for the plugin's own connection service.
	 *
	 * @param Response ...$responses
	 */
	protected function queue_connect_server_responses( Response ...$responses ): void {
		$this->history = [];
		$stack         = HandlerStack::create( new MockHandler( $responses ) );
		$stack->push( Middleware::history( $this->history ) );

		$container = new Container();
		$container->add( 'connect_server_root', self::CONNECT_SERVER_ROOT );
		$container->add( Client::class, new Client( [ 'handler' => $stack ] ) );

		// Without a registered connection the route requests below fail on their status instead.
		if ( woogle_get_container()->has( Connection::class ) ) {
			woogle_get_container()->get( Connection::class )->set_container( $container );
		}
	}

	public function test_plugin_registers_the_connect_and_connection_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( self::ROUTE_CONNECT, $routes );
		$this->assertArrayHasKey( self::ROUTE_CONNECTION, $routes );
		$this->assertArrayHasKey( 'GET', $routes[ self::ROUTE_CONNECTION ][0]['methods'] );
		$this->assertArrayHasKey( 'DELETE', $routes[ self::ROUTE_CONNECTION ][1]['methods'] );
	}

	public function test_registered_status_route_reports_the_stored_location() {
		$this->options->update(
			OptionsInterface::BUSINESS_PROFILE,
			[
				'account_id'       => '1234',
				'location_id'      => '5678',
				'location_title'   => 'Example Store',
				'location_address' => '123 Market St, San Francisco',
				'place_id'         => 'ChIJexample',
				'maps_uri'         => 'https://maps.google.com/?cid=1',
			]
		);
		$this->queue_connect_server_responses(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_BUSINESS_PROFILE ] ] ) )
		);

		$response = $this->server->dispatch_request( new Request( 'GET', self::ROUTE_CONNECTION ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			[
				'status'    => Connection::STATUS_CONNECTED,
				'id'        => '5678',
				'accountId' => '1234',
				'title'     => 'Example Store',
				'address'   => '123 Market St, San Francisco',
				'placeId'   => 'ChIJexample',
				'mapsUri'   => 'https://maps.google.com/?cid=1',
			],
			$response->get_data()
		);
	}

	public function test_registered_disconnect_route_clears_the_stored_location_locally() {
		$this->options->update( OptionsInterface::BUSINESS_PROFILE, [ 'location_id' => '5678' ] );
		$this->queue_connect_server_responses();

		$response = $this->server->dispatch_request( new Request( 'DELETE', self::ROUTE_CONNECTION ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( $this->options->get( OptionsInterface::BUSINESS_PROFILE ) );
		$this->assertCount( 0, $this->history );
	}
}
