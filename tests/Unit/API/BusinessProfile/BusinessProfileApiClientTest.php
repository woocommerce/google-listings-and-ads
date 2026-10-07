<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileApiClientTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class BusinessProfileApiClientTest extends UnitTest {

	protected const BASE_URL = 'https://example.test/base/';

	/** @var MockHandler */
	protected $mock;

	/** @var array<int, array> */
	protected $history = [];

	/** @var BusinessProfileApiClient */
	protected $client;

	public function setUp(): void {
		parent::setUp();

		$this->history = [];
		$this->mock    = new MockHandler();

		$stack = HandlerStack::create( $this->mock );
		$stack->push( Middleware::history( $this->history ) );

		$this->client = new BusinessProfileApiClient(
			new Client( [ 'handler' => $stack ] ),
			self::BASE_URL
		);
	}

	public function test_get_returns_decoded_array_on_success() {
		$this->mock->append( new Response( 200, [], wp_json_encode( [ 'accounts' => [] ] ) ) );

		$result = $this->client->get( 'accounts' );

		$this->assertSame( [ 'accounts' => [] ], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'GET', $request->getMethod() );
		$this->assertSame( self::BASE_URL . 'accounts', (string) $request->getUri() );
	}

	public function test_get_strips_leading_slash_from_path() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$this->client->get( '/accounts/123/locations' );

		$request = $this->history[0]['request'];
		$this->assertSame( self::BASE_URL . 'accounts/123/locations', (string) $request->getUri() );
	}

	public function test_get_appends_query_parameters() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$this->client->get(
			'accounts/123/locations',
			[
				'pageSize' => 100,
				'readMask' => 'name,title',
			]
		);

		$request = $this->history[0]['request'];
		$this->assertSame(
			self::BASE_URL . 'accounts/123/locations?pageSize=100&readMask=name%2Ctitle',
			(string) $request->getUri()
		);
	}

	public function test_get_leaves_out_empty_query_parameters() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$this->client->get(
			'accounts',
			[
				'pageSize'  => 20,
				'pageToken' => '',
				'filter'    => null,
			]
		);

		$request = $this->history[0]['request'];
		$this->assertSame( self::BASE_URL . 'accounts?pageSize=20', (string) $request->getUri() );
	}

	public function test_get_returns_empty_array_for_empty_body() {
		$this->mock->append( new Response( 200, [], '' ) );

		$this->assertSame( [], $this->client->get( 'accounts' ) );
	}

	public function test_4xx_response_throws_business_profile_api_exception() {
		$body = [
			'error' => [
				'code'    => 403,
				'message' => 'Request had insufficient authentication scopes.',
				'status'  => 'PERMISSION_DENIED',
			],
		];
		$this->mock->append( new Response( 403, [], wp_json_encode( $body ) ) );

		try {
			$this->client->get( 'accounts' );
			$this->fail( 'Expected BusinessProfileApiException' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( 403, $e->get_http_status() );
			$this->assertSame( 403, $e->getCode() );
			$this->assertSame( 'Request had insufficient authentication scopes.', $e->getMessage() );
			$this->assertSame( $body, $e->get_response_body() );
		}
	}

	public function test_proxy_error_with_flat_message_uses_that_message() {
		$this->mock->append( new Response( 400, [], wp_json_encode( [ 'message' => 'Unsupported path' ] ) ) );

		$this->expectException( BusinessProfileApiException::class );
		$this->expectExceptionMessage( 'Unsupported path' );

		$this->client->get( 'accounts' );
	}

	public function test_exception_fires_logging_action() {
		$this->mock->append( new Response( 500, [], '' ) );

		$fired = 0;
		add_action(
			'woocommerce_gla_business_profile_client_exception',
			function () use ( &$fired ) {
				++$fired;
			}
		);

		try {
			$this->client->get( 'accounts' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( 'Business Profile API request failed', $e->getMessage() );
		}

		$this->assertSame( 1, $fired );
	}

	public function test_connection_error_throws_business_profile_api_exception() {
		$previous = new ConnectException( 'Connection refused', new Request( 'GET', 'accounts' ) );
		$this->mock->append( $previous );

		try {
			$this->client->get( 'accounts' );
			$this->fail( 'Expected BusinessProfileApiException' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( 503, $e->get_http_status() );
			$this->assertSame( [], $e->get_response_body() );
			$this->assertSame( $previous, $e->getPrevious() );
		}
	}

	public function test_request_error_without_response_throws_business_profile_api_exception() {
		$this->mock->append( new RequestException( 'Request timed out', new Request( 'GET', 'accounts' ) ) );

		try {
			$this->client->get( 'accounts' );
			$this->fail( 'Expected BusinessProfileApiException' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( 503, $e->get_http_status() );
			$this->assertInstanceOf( RequestException::class, $e->getPrevious() );
		}
	}
}
