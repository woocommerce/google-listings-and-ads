<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API;

use Automattic\WooCommerce\GoogleListingsAndAds\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use ReflectionClass;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class ClientTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API
 */
class ClientTest extends ContainerAwareUnitTest {
	use PluginHelper;

	/**
	 * Confirm that the client handler stack includes the following handlers:
	 * - `http_errors`
	 * - `auth_header`
	 * - `plugin_version_header`
	 */
	public function test_handlers_in_stack(): void {
		// Get string representation of the handler stack.
		$handlers = (string) $this->container->get( Client::class )->getConfig( 'handler' );

		$this->assertStringContainsString( 'http_errors', $handlers );
		$this->assertStringContainsString( 'auth_header', $handlers );
		$this->assertStringContainsString( 'plugin_version_header', $handlers );

		// By default we should not have a http URL.
		$this->assertStringNotContainsString( 'override_http_url', $handlers );
	}

	/**
	 * Confirm that the error handler does not intervene for regular responses.
	 */
	public function test_error_handler_regular_response() {
		$mocked_responses = [
			new Response( 200, [], 'response' ),
		];

		$client   = $this->mock_client_with_handler( 'error_handler', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 'response', $response->getBody() );
	}

	/**
	 * Confirm that `add_plugin_version_header` adds the correct headers to the request.
	 *
	 * @return void
	 */
	public function test_plugin_version_headers(): void {
		$request = new Request( 'GET', 'https://testing.local' );

		$this->invoke_handler( 'add_plugin_version_header' )(
			function ( $request, $options ) {
				$this->assertEquals( $this->get_client_name(), $request->getHeader( 'x-client-name' )[0] );
				$this->assertEquals( $this->get_version(), $request->getHeader( 'x-client-version' )[0] );
			}
		)( $request, [] );
	}

	/**
	 * Calls a handler function through ReflectionMethod to allow testing protected handlers.
	 *
	 * @param string $handler_function
	 * @return callable Handler callback.
	 */
	protected function invoke_handler( string $handler_function ) {
		$handler = new ReflectionMethod( GoogleServiceProvider::class, $handler_function );
		$handler->setAccessible( true );

		// Get access to internal container to share with service provider.
		$woogle_container = new ReflectionClass( Container::class );
		$league_container = $woogle_container->getProperty( 'container' );
		$league_container->setAccessible( true );

		$provider = new GoogleServiceProvider();
		$provider->setLeagueContainer( $league_container->getValue( $this->container ) );
		return $handler->invoke( $provider );
	}

	/**
	 * Returns a mock client with an individual handler attached to the stack.
	 *
	 * @param string $handler_function Handler function name to include in stack.
	 * @param array  $mocked_responses List of responses to return.
	 *
	 * @return Client Mock client.
	 */
	protected function mock_client_with_handler( string $handler_function, array $mocked_responses ) {
		$mock     = new MockHandler( $mocked_responses );
		$handlers = HandlerStack::create( $mock );
		$handlers->push( $this->invoke_handler( 'error_handler' ) );
		return new Client( [ 'handler' => $handlers ] );
	}
}
