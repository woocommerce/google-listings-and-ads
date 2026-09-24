<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchConsoleApiClientTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class SearchConsoleApiClientTest extends UnitTest {

	protected const BASE_URL = 'https://example.test/base/';

	/** @var MockHandler */
	protected $mock;

	/** @var array<int, array> */
	protected $history = [];

	/** @var SearchConsoleApiClient */
	protected $client;

	public function setUp(): void {
		parent::setUp();

		$this->history = [];
		$this->mock    = new MockHandler();

		$stack = HandlerStack::create( $this->mock );
		$stack->push( Middleware::history( $this->history ) );

		$this->client = new SearchConsoleApiClient(
			new Client( [ 'handler' => $stack ] ),
			self::BASE_URL
		);
	}

	public function test_get_returns_decoded_array_on_success() {
		$this->mock->append( new Response( 200, [], wp_json_encode( [ 'siteEntry' => [] ] ) ) );

		$result = $this->client->get( 'sites' );

		$this->assertSame( [ 'siteEntry' => [] ], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'GET', $request->getMethod() );
		$this->assertSame( self::BASE_URL . 'sites', (string) $request->getUri() );
	}

	public function test_put_sends_json_encoded_body_and_returns_decoded_response() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$result = $this->client->put( 'sites/https%3A%2F%2Fexample.com%2F' );

		$this->assertSame( [], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'PUT', $request->getMethod() );
		$this->assertSame( self::BASE_URL . 'sites/https%3A%2F%2Fexample.com%2F', (string) $request->getUri() );
	}

	public function test_put_with_empty_body_sends_no_content_type_header() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$this->client->put( 'sites/https%3A%2F%2Fexample.com%2F' );

		$request = $this->history[0]['request'];
		$this->assertSame( '', $request->getHeaderLine( 'Content-Type' ) );
	}

	public function test_delete_returns_decoded_array_on_success() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$result = $this->client->delete( 'sites/https%3A%2F%2Fexample.com%2F' );

		$this->assertSame( [], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'DELETE', $request->getMethod() );
	}

	public function test_4xx_response_throws_search_console_api_exception() {
		$body = [
			'error' => [
				'code'    => 403,
				'message' => 'User does not have sufficient permission',
			],
		];
		$this->mock->append( new Response( 403, [], wp_json_encode( $body ) ) );

		try {
			$this->client->get( 'sites' );
			$this->fail( 'Expected SearchConsoleApiException' );
		} catch ( SearchConsoleApiException $e ) {
			$this->assertSame( 403, $e->get_http_status() );
			$this->assertSame( $body, $e->get_response_body() );
		}
	}

	public function test_5xx_response_throws_search_console_api_exception() {
		$this->mock->append( new Response( 503, [], 'Service Unavailable' ) );

		$this->expectException( SearchConsoleApiException::class );
		$this->client->get( 'sites' );
	}
}
