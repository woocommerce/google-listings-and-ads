<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API;

use Automattic\Jetpack\Connection\Manager;
use Automattic\Jetpack\Connection\Tokens;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReconnectWordPress;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class ClientTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API
 */
class ClientTest extends UnitTest {
	use PluginHelper;

	/** @var MockObject|Manager $manager */
	protected $manager;

	/** @var MockObject|ReconnectWordPress $note */
	protected $note;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/**
	 * @var Container $container
	 */
	protected $container;

	/**
	 * @var Provider $provider
	 */
	protected $provider;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock( Manager::class );
		$this->note    = $this->createMock( ReconnectWordPress::class );
		$this->options = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->share( Manager::class, $this->manager );
		$this->container->share( ReconnectWordPress::class, $this->note );
		$this->container->share( OptionsInterface::class, $this->options );

		$this->provider = new GoogleServiceProvider();
		$this->provider->setLeagueContainer( $this->container );
	}

	/**
	 * Confirm that the client handler stack includes the following handlers:
	 * - `http_errors`
	 * - `auth_header`
	 * - `plugin_version_header`
	 */
	public function test_handlers_in_stack(): void {
		// Get string representation of the handler stack (fetches handlers from main container).
		$handlers = (string) woogle_get_container()->get( Client::class )->getConfig( 'handler' );

		$this->assertStringContainsString( 'http_errors', $handlers );
		$this->assertStringContainsString( 'auth_header', $handlers );
		$this->assertStringContainsString( 'plugin_version_header', $handlers );

		// By default we should not have a http URL.
		$this->assertStringNotContainsString( 'override_http_url', $handlers );
	}

	/**
	 * Confirm that service classes are available from the container after registering.
	 */
	public function test_registering_provided_services() {
		$this->provider->register();

		$this->assertNotNull( $this->container->get( GoogleAdsClient::class ) );
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
	 * Confirm that the error handler throws an error to reconnect Jetpack when the header is not included.
	 */
	public function test_error_handler_reconnect_jetpack() {
		$mocked_responses = [
			new Response( 401, [ 'www-authenticate' => 'X_JP_Auth' ], 'error' ),
		];

		// Set Jetpack as previously connected to trigger change note.
		$this->options->expects( $this->once() )->method( 'get' )->with( OptionsInterface::JETPACK_CONNECTED )->willReturn( true );

		// Expect Jetpack to be marked as disconnected.
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::JETPACK_CONNECTED, false );

		// Expect ReconnectWordPress note to be triggered.
		$this->note->expects( $this->once() )->method( 'get_entry' );

		$this->expectException( AccountReconnect::class );
		$this->expectExceptionMessage( AccountReconnect::jetpack_disconnected()->getMessage() );

		$client   = $this->mock_client_with_handler( 'error_handler', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );
	}

	/**
	 * Confirm that the error handler throws an error to reconnect Google with a permission denied status.
	 */
	public function test_error_handler_reconnect_google() {
		$mocked_responses = [
			new Response( 401, [], 'error' ),
		];

		// Expect Google to be marked as disconnected.
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::GOOGLE_CONNECTED, false );

		$this->expectException( AccountReconnect::class );
		$this->expectExceptionMessage( AccountReconnect::google_disconnected()->getMessage() );

		$client   = $this->mock_client_with_handler( 'error_handler', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );
	}

	/**
	 * Confirm that a request to listAccessibleCustomers does not return a redirect error.
	 */
	public function test_error_handler_list_accessible_customers() {
		$mocked_responses = [
			new Response( 401, [], 'error' ),
		];

		$this->expectException( RequestException::class );
		$this->expectExceptionMessage( 'error' );

		$client   = $this->mock_client_with_handler( 'error_handler', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local/google/google-ads/customers:listAccessibleCustomers' );
	}

	/**
	 * Confirm that the error handler throws a generic error when the status code is higher than 400 except a 401.
	 */
	public function test_error_handler_generic_error_response() {
		$mocked_responses = [
			new Response( 404, [], 'not found' ),
		];

		$this->expectException( RequestException::class );
		$this->expectExceptionMessage( 'not found' );

		$client   = $this->mock_client_with_handler( 'error_handler', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );
	}

	/**
	 * Confirm that an auth header is added to the request.
	 */
	public function test_add_auth_header() {
		$request = new Request( 'GET', 'https://testing.local' );

		// Mock JetPack tokens.
		$tokens = $this->createMock( Tokens::class );
		$tokens->method( 'get_access_token' )->willReturn(
			(object) [
				'secret'           => 'secret.token',
				'external_user_id' => 123,
			]
		);
		$this->manager->expects( $this->once() )->method( 'get_tokens' )->willReturn( $tokens );

		// Set Jetpack as previously disconnected to trigger removal of note.
		$this->options->expects( $this->once() )->method( 'get' )->with( OptionsInterface::JETPACK_CONNECTED )->willReturn( false );

		// Expect ReconnectWordPress note to be removed.
		$this->note->expects( $this->once() )->method( 'delete' );

		$this->invoke_handler( 'add_auth_header' )(
			function ( $request, $options ) {
				$this->assertStringStartsWith( 'X_JP_Auth token=', $request->getHeader( 'Authorization' )[0] );
			}
		)( $request, [] );
	}

	/**
	 * Confirm that an auth header fails when no token is available.
	 */
	public function test_add_auth_header_no_token() {
		$request = new Request( 'GET', 'https://testing.local' );

		// Mock empty JetPack tokens.
		$this->manager->expects( $this->once() )->method( 'get_tokens' )->willReturn( new Tokens() );

		$this->expectException( AccountReconnect::class );
		$this->expectExceptionMessage( AccountReconnect::jetpack_disconnected()->getMessage() );

		$this->invoke_handler( 'add_auth_header' )(
			function ( $request, $options ) {}
		)( $request, [] );
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

		return $handler->invoke( $this->provider );
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
