<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API;

use Automattic\Jetpack\Connection\Manager;
use Automattic\Jetpack\Connection\Tokens;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\JetpackAuthCircuitBreaker;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReconnectWordPress;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
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

	/** @var MockObject|JetpackAuthCircuitBreaker $circuit_breaker */
	protected $circuit_breaker;

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

		$this->circuit_breaker = $this->createMock( JetpackAuthCircuitBreaker::class );
		$this->manager         = $this->createMock( Manager::class );
		$this->note            = $this->createMock( ReconnectWordPress::class );
		$this->options         = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->addShared( JetpackAuthCircuitBreaker::class, $this->circuit_breaker );
		$this->container->addShared( Manager::class, $this->manager );
		$this->container->addShared( ReconnectWordPress::class, $this->note );
		$this->container->addShared( OptionsInterface::class, $this->options );

		$this->provider = new GoogleServiceProvider();
		$this->provider->setContainer( $this->container );
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

		$this->assertStringContainsString( 'auth_failure_short_circuit', $handlers );
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
	 * Confirm that the response status handler does not intervene for regular responses.
	 */
	public function test_handle_response_status_regular_response() {
		$mocked_responses = [
			new Response( 200, [], 'response' ),
		];

		$client   = $this->mock_client_with_handler( 'handle_response_status', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 'response', $response->getBody() );
	}

	/**
	 * Confirm that an accepted response is what marks Jetpack as connected and ends a sync pause.
	 */
	public function test_handle_response_status_regular_response_marks_jetpack_connected() {
		// Set Jetpack as previously disconnected to trigger removal of note.
		$this->options->expects( $this->once() )->method( 'get' )->with( OptionsInterface::JETPACK_CONNECTED )->willReturn( false );
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::JETPACK_CONNECTED, true );
		$this->note->expects( $this->once() )->method( 'delete' );
		$this->circuit_breaker->expects( $this->once() )->method( 'reset' );

		$client = $this->mock_client_with_handler( 'handle_response_status', [ new Response( 200, [], 'response' ) ] );
		$client->request( 'GET', 'https://testing.local' );
	}

	/**
	 * Confirm that once the Jetpack token was rejected in this request, later requests are not sent.
	 */
	public function test_short_circuit_after_auth_failure_rejects_without_sending() {
		$this->circuit_breaker->method( 'was_tripped_in_request' )->willReturn( true );

		$mock     = new MockHandler( [ new Response( 200, [], 'never sent' ) ] );
		$handlers = HandlerStack::create( $mock );
		$handlers->push( $this->invoke_handler( 'short_circuit_after_auth_failure' ) );
		$client = new Client( [ 'handler' => $handlers ] );

		try {
			$client->request( 'GET', 'https://testing.local' );
			$this->fail( 'Expected AccountReconnect to be thrown.' );
		} catch ( AccountReconnect $exception ) {
			$this->assertEquals( AccountReconnect::jetpack_disconnected()->getMessage(), $exception->getMessage() );
		}

		// The mocked response is still queued: nothing reached the transport.
		$this->assertSame( 1, $mock->count() );
	}

	/**
	 * Confirm that the short circuit is transparent while no failure was recorded.
	 */
	public function test_short_circuit_after_auth_failure_passes_requests_through() {
		$this->circuit_breaker->method( 'was_tripped_in_request' )->willReturn( false );

		$client   = $this->mock_client_with_handler( 'short_circuit_after_auth_failure', [ new Response( 200, [], 'sent' ) ] );
		$response = $client->request( 'GET', 'https://testing.local' );

		$this->assertEquals( 'sent', $response->getBody() );
	}

	public function test_retry_on_transient_error_retries_transient_status() {
		$client   = $this->mock_client_with_handler(
			'retry_on_transient_error',
			[
				new Response( 429, [], 'rate limited' ),
				new Response( 200, [], 'ok' ),
			]
		);
		$response = $client->request( 'GET', 'https://testing.local' );

		// The 429 is retried and the following success is returned.
		$this->assertEquals( 200, $response->getStatusCode() );
	}

	public function test_retry_on_transient_error_ignores_client_errors() {
		$client = $this->mock_client_with_handler(
			'retry_on_transient_error',
			[
				new Response( 400, [], 'bad request' ),
				new Response( 200, [], 'ok' ),
			]
		);

		// A 400 is not retried: it surfaces instead of consuming the queued success.
		$this->expectException( RequestException::class );
		$client->request( 'GET', 'https://testing.local' );
	}

	public function test_retry_runs_before_the_response_status_handler_on_the_full_stack() {
		// Real stack order: handle_response_status pushed first (outermost), retry last (innermost). This
		// POST is one is_retryable_request() rejects, so a 429 can only retry via the response
		// branch, proving retry sees the response before handle_response_status throws.
		$handler_stack = HandlerStack::create(
			new MockHandler(
				[
					new Response( 429, [], 'rate limited' ),
					new Response( 200, [], 'ok' ),
				]
			)
		);
		$handler_stack->remove( 'http_errors' );
		$handler_stack->push( $this->invoke_handler( 'handle_response_status' ), 'http_errors' );
		$handler_stack->push( $this->invoke_handler( 'retry_on_transient_error' ), 'retry_on_transient_error' );

		$response = ( new Client( [ 'handler' => $handler_stack ] ) )
			->request( 'POST', 'https://testing.local/datasources/v1/accounts/1/dataSources' );

		$this->assertEquals( 200, $response->getStatusCode() );
	}

	public function test_retry_on_transient_error_retries_5xx_on_product_insert() {
		$client = $this->mock_client_with_handler(
			'retry_on_transient_error',
			[
				new Response( 503, [], 'unavailable' ),
				new Response( 200, [], 'ok' ),
			]
		);

		// productInputs.insert is an upsert, so a 5xx POST is safe to retry.
		$response = $client->request( 'POST', 'https://testing.local/products/v1/accounts/1/productInputs:insert' );

		$this->assertEquals( 200, $response->getStatusCode() );
	}

	public function test_retry_on_transient_error_does_not_retry_5xx_on_non_idempotent_post() {
		$client = $this->mock_client_with_handler(
			'retry_on_transient_error',
			[
				new Response( 503, [], 'unavailable' ),
				new Response( 200, [], 'ok' ),
			]
		);

		// A 5xx on a non-idempotent, non-product POST is not retried: it surfaces.
		$this->expectException( RequestException::class );
		$client->request( 'POST', 'https://testing.local/datasources/v1/accounts/1/dataSources' );
	}

	public function test_retry_on_transient_error_retries_connection_errors() {
		$client = $this->mock_client_with_handler(
			'retry_on_transient_error',
			[
				new ConnectException( 'connection timed out', new Request( 'GET', 'https://testing.local' ) ),
				new Response( 200, [], 'ok' ),
			]
		);

		// A connection error (no response) is retried.
		$response = $client->request( 'GET', 'https://testing.local' );

		$this->assertEquals( 200, $response->getStatusCode() );
	}

	public function test_retry_on_transient_error_gives_up_after_the_retry_limit() {
		add_filter(
			'woocommerce_gla_mapi_retry_limit',
			function () {
				return 1;
			}
		);

		try {
			$client = $this->mock_client_with_handler(
				'retry_on_transient_error',
				[
					new Response( 503, [], 'unavailable' ),
					new Response( 503, [], 'unavailable' ),
				]
			);

			// With a retry limit of 1, a persistent 5xx surfaces after the single retry.
			$this->expectException( RequestException::class );
			$client->request( 'GET', 'https://testing.local' );
		} finally {
			remove_all_filters( 'woocommerce_gla_mapi_retry_limit' );
		}
	}

	public function test_retry_delay_caps_a_large_retry_after() {
		$method = new ReflectionMethod( GoogleServiceProvider::class, 'retry_delay' );
		$method->setAccessible( true );

		// Retry-After: 3600 would be 3,600,000ms uncapped; must be clamped to 30s so a
		// large value can't stall the background sync job.
		$delay = $method->invoke( $this->provider, 1, new Response( 503, [ 'Retry-After' => '3600' ] ) );

		$this->assertSame( 30000, $delay );
	}

	public function test_retry_delay_caps_the_backoff() {
		$method = new ReflectionMethod( GoogleServiceProvider::class, 'retry_delay' );
		$method->setAccessible( true );

		// A high retry count is clamped to the 30s cap (jitter is added inside the cap).
		$delay = $method->invoke( $this->provider, 20, null );

		$this->assertSame( 30000, $delay );
	}

	public function test_retry_on_transient_error_retries_no_response_transport_errors() {
		$request = new Request( 'GET', 'https://testing.local' );
		$client  = $this->mock_client_with_handler(
			'retry_on_transient_error',
			[
				new RequestException( 'connection reset', $request ),
				new Response( 200, [], 'ok' ),
			]
		);

		// A transport error with no response on an idempotent request is retried.
		$response = $client->request( 'GET', 'https://testing.local' );

		$this->assertEquals( 200, $response->getStatusCode() );
	}

	/**
	 * Confirm that the response status handler throws an error to reconnect Jetpack when the header is not included.
	 */
	public function test_handle_response_status_reconnect_jetpack() {
		$mocked_responses = [
			new Response( 401, [ 'www-authenticate' => 'X_JP_Auth' ], 'error' ),
		];

		// Set Jetpack as previously connected to trigger change note.
		$this->options->expects( $this->once() )->method( 'get' )->with( OptionsInterface::JETPACK_CONNECTED )->willReturn( true );

		// Expect Jetpack to be marked as disconnected.
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::JETPACK_CONNECTED, false );

		// Expect ReconnectWordPress note to be triggered.
		$this->note->expects( $this->once() )->method( 'get_entry' );

		// Expect syncing to be paused.
		$this->circuit_breaker->expects( $this->once() )->method( 'trip' );

		$this->expectException( AccountReconnect::class );
		$this->expectExceptionMessage( AccountReconnect::jetpack_disconnected()->getMessage() );

		$client   = $this->mock_client_with_handler( 'handle_response_status', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );
	}

	/**
	 * Confirm that the response status handler throws an error to reconnect Google with a permission denied status.
	 */
	public function test_handle_response_status_reconnect_google() {
		$mocked_responses = [
			new Response( 401, [], 'error' ),
		];

		// Expect Google to be marked as disconnected.
		$this->options->expects( $this->once() )->method( 'update' )->with( OptionsInterface::GOOGLE_CONNECTED, false );

		$this->expectException( AccountReconnect::class );
		$this->expectExceptionMessage( AccountReconnect::google_disconnected()->getMessage() );

		$client   = $this->mock_client_with_handler( 'handle_response_status', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local' );
	}

	/**
	 * Confirm that a request to listAccessibleCustomers does not return a redirect error.
	 */
	public function test_handle_response_status_list_accessible_customers() {
		$mocked_responses = [
			new Response( 401, [], 'error' ),
		];

		$this->expectException( RequestException::class );
		$this->expectExceptionMessage( 'error' );

		$client   = $this->mock_client_with_handler( 'handle_response_status', $mocked_responses );
		$response = $client->request( 'GET', 'https://testing.local/google/google-ads/customers:listAccessibleCustomers' );
	}

	/**
	 * Confirm that the response status handler throws a generic error when the status code is higher than 400 except a 401.
	 */
	public function test_handle_response_status_generic_error_response() {
		$mocked_responses = [
			new Response( 404, [], 'not found' ),
		];

		$this->expectException( RequestException::class );
		$this->expectExceptionMessage( 'not found' );

		$client   = $this->mock_client_with_handler( 'handle_response_status', $mocked_responses );
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

		// Having a token locally proves nothing about its validity: the connected state is left alone.
		$this->options->expects( $this->never() )->method( 'update' );
		$this->note->expects( $this->never() )->method( 'delete' );

		$this->invoke_handler( 'add_auth_header' )(
			function ( $request, $options ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				unset( $options );
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
			function ( $request, $options ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				unset( $options );
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
		$handlers->push( $this->invoke_handler( $handler_function ) );
		return new Client( [ 'handler' => $handlers ] );
	}
}
