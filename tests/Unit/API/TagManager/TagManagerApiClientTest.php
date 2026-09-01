<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerApiClientTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager
 */
class TagManagerApiClientTest extends UnitTest {

	protected const BASE_URL = 'https://example.test/base/';

	/** @var MockHandler */
	protected $mock;

	/** @var array<int, array> */
	protected $history = [];

	/** @var TagManagerApiClient */
	protected $client;

	public function setUp(): void {
		parent::setUp();

		$this->history = [];
		$this->mock    = new MockHandler();

		$stack = HandlerStack::create( $this->mock );
		$stack->push( Middleware::history( $this->history ) );

		$this->client = new TagManagerApiClient(
			new Client( [ 'handler' => $stack ] ),
			self::BASE_URL
		);
	}

	public function test_get_returns_decoded_array_on_success() {
		$this->mock->append( new Response( 200, [], wp_json_encode( [ 'account' => [] ] ) ) );

		$result = $this->client->get( 'accounts' );

		$this->assertSame( [ 'account' => [] ], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'GET', $request->getMethod() );
		$this->assertSame( self::BASE_URL . 'accounts', (string) $request->getUri() );
	}

	public function test_get_strips_leading_slash_from_path() {
		$this->mock->append( new Response( 200, [], '{}' ) );

		$this->client->get( '/accounts/123/containers' );

		$request = $this->history[0]['request'];
		$this->assertSame( self::BASE_URL . 'accounts/123/containers', (string) $request->getUri() );
	}

	public function test_4xx_response_throws_tag_manager_api_exception() {
		$body = [
			'error' => [
				'code'    => 403,
				'message' => 'Request had insufficient authentication scopes.',
			],
		];
		$this->mock->append( new Response( 403, [], wp_json_encode( $body ) ) );

		try {
			$this->client->get( 'accounts' );
			$this->fail( 'Expected TagManagerApiException' );
		} catch ( TagManagerApiException $e ) {
			$this->assertSame( 403, $e->get_http_status() );
			$this->assertSame( $body, $e->get_response_body() );
		}
	}

	public function test_4xx_response_with_flat_message_shape_throws_exception_with_that_message() {
		// The Connect Server's own routing errors (e.g. an unsupported service or
		// path) return a flat `message` string, not a nested `error.message` —
		// a different shape than a proxied Google API error.
		$body = [
			'statusCode' => 400,
			'error'      => 'Bad Request',
			'message'    => 'Unsupported Google service',
		];
		$this->mock->append( new Response( 400, [], wp_json_encode( $body ) ) );

		try {
			$this->client->get( 'accounts' );
			$this->fail( 'Expected TagManagerApiException' );
		} catch ( TagManagerApiException $e ) {
			$this->assertSame( 'Unsupported Google service', $e->getMessage() );
		}
	}

	public function test_5xx_response_throws_tag_manager_api_exception() {
		$this->mock->append( new Response( 503, [], 'Service Unavailable' ) );

		$this->expectException( TagManagerApiException::class );
		$this->client->get( 'accounts' );
	}
}
