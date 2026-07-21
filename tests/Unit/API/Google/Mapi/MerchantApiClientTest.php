<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\EachPromise;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantApiClientTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi
 */
class MerchantApiClientTest extends UnitTest {

	protected const BASE_URL = 'https://example.test/google-mapi/';

	/** @var MockHandler */
	protected $mock;

	/** @var array<int, array> */
	protected $history = [];

	/** @var MerchantApiClient */
	protected $client;

	public function setUp(): void {
		parent::setUp();

		$this->history = [];
		$this->mock    = new MockHandler();

		$stack = HandlerStack::create( $this->mock );
		$stack->push( Middleware::history( $this->history ) );

		$this->client = new MerchantApiClient(
			new Client( [ 'handler' => $stack ] ),
			self::BASE_URL
		);
	}

	public function test_get_returns_decoded_array_on_success() {
		$this->mock->append( new Response( 200, [], wp_json_encode( [ 'name' => 'accounts/1/products/abc' ] ) ) );

		$result = $this->client->get( 'accounts/1/products/abc' );

		$this->assertSame( [ 'name' => 'accounts/1/products/abc' ], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'GET', $request->getMethod() );
		$this->assertSame( self::BASE_URL . 'accounts/1/products/abc', (string) $request->getUri() );
	}

	public function test_post_sends_json_encoded_body() {
		$this->mock->append( new Response( 200, [], wp_json_encode( [ 'ok' => true ] ) ) );

		$body   = [ 'foo' => 'bar' ];
		$result = $this->client->post( 'some/path', $body );

		$this->assertSame( [ 'ok' => true ], $result );

		$request = $this->history[0]['request'];
		$this->assertSame( 'POST', $request->getMethod() );
		$this->assertSame( 'application/json', $request->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( wp_json_encode( $body ), (string) $request->getBody() );
	}

	public function test_4xx_response_throws_merchant_api_exception() {
		$body = [
			'error' => [
				'code'    => 400,
				'message' => 'Invalid request',
				'errors'  => [
					[
						'reason'  => 'invalidValue',
						'message' => 'bad',
					],
				],
			],
		];
		$this->mock->append( new Response( 400, [], wp_json_encode( $body ) ) );

		try {
			$this->client->get( 'bad/path' );
			$this->fail( 'Expected MerchantApiException' );
		} catch ( MerchantApiException $e ) {
			$this->assertSame( 400, $e->get_http_status() );
			$this->assertSame( $body, $e->get_response_body() );
			$this->assertSame( $body['error']['errors'], $e->get_errors() );
		}
	}

	public function test_5xx_response_throws_merchant_api_exception() {
		$this->mock->append( new Response( 503, [], 'Service Unavailable' ) );

		$this->expectException( MerchantApiException::class );
		$this->client->get( 'some/path' );
	}

	public function test_get_async_resolves_to_same_shape_as_get() {
		$body = [ 'name' => 'accounts/1/products/abc' ];
		$this->mock->append( new Response( 200, [], wp_json_encode( $body ) ) );

		$result = $this->client->get_async( 'accounts/1/products/abc' )->wait();

		$this->assertSame( $body, $result );
	}

	public function test_three_parallel_get_async_via_each_promise_all_fulfill() {
		$this->mock->append(
			new Response( 200, [], wp_json_encode( [ 'name' => 'accounts/1/products/a' ] ) ),
			new Response( 200, [], wp_json_encode( [ 'name' => 'accounts/1/products/b' ] ) ),
			new Response( 200, [], wp_json_encode( [ 'name' => 'accounts/1/products/c' ] ) )
		);

		$results = [];
		$ids     = [ 'a', 'b', 'c' ];

		$client   = $this->client;
		$promises = function () use ( $ids, $client ) {
			foreach ( $ids as $id ) {
				yield $id => $client->get_async( "accounts/1/products/{$id}" );
			}
		};

		( new EachPromise(
			$promises(),
			[
				'concurrency' => 3,
				'fulfilled'   => function ( array $body, string $id ) use ( &$results ) {
					$results[ $id ] = $body;
				},
			]
		) )->promise()->wait();

		$this->assertCount( 3, $results );
		$this->assertSame( 'accounts/1/products/a', $results['a']['name'] );
		$this->assertSame( 'accounts/1/products/b', $results['b']['name'] );
		$this->assertSame( 'accounts/1/products/c', $results['c']['name'] );
	}

	public function test_batch_demuxes_per_subrequest_results() {
		$boundary  = 'resp_boundary';
		$multipart = $this->build_multipart_response(
			$boundary,
			[
				0 => [ 200, [ 'name' => 'accounts/1/products/gla_10' ] ],
				1 => [ 400, [ 'error' => [ 'message' => 'bad' ] ] ],
			]
		);
		$this->mock->append( new Response( 200, [ 'Content-Type' => "multipart/mixed; boundary={$boundary}" ], $multipart ) );

		$results = $this->client->batch(
			[
				0 => [
					'method' => 'POST',
					'path'   => 'products/v1/a/productInputs:insert',
					'body'   => [ 'offerId' => 'gla_10' ],
				],
				1 => [
					'method' => 'POST',
					'path'   => 'products/v1/a/productInputs:insert',
					'body'   => [ 'offerId' => 'gla_11' ],
				],
			]
		);

		$this->assertSame( 200, $results[0]['status'] );
		$this->assertSame( 'accounts/1/products/gla_10', $results[0]['body']['name'] );
		$this->assertSame( 400, $results[1]['status'] );
		$this->assertSame( 'bad', $results[1]['body']['error']['message'] );
	}

	public function test_batch_sends_multipart_request() {
		$this->mock->append( new Response( 200, [ 'Content-Type' => 'multipart/mixed; boundary=b' ], "--b--\r\n" ) );

		$this->client->batch(
			[
				0 => [
					'method' => 'POST',
					'path'   => 'products/v1/a/productInputs:insert',
					'body'   => [ 'offerId' => 'gla_10' ],
				],
			]
		);

		$request = $this->history[0]['request'];
		$this->assertSame( 'POST', $request->getMethod() );
		$this->assertSame( self::BASE_URL . 'batch', (string) $request->getUri() );
		$this->assertStringContainsString( 'multipart/mixed; boundary=', $request->getHeaderLine( 'Content-Type' ) );

		$body = (string) $request->getBody();
		$this->assertStringContainsString( 'Content-ID: <item0>', $body );
		$this->assertStringContainsString( 'POST /products/v1/a/productInputs:insert HTTP/1.1', $body );
		$this->assertStringContainsString( '"offerId":"gla_10"', $body );
	}

	/**
	 * Build a Google-style multipart/mixed batch response body.
	 *
	 * @param string                                     $boundary
	 * @param array<int|string, array{0: int, 1: array}> $subs     Keyed by request key: [ status, body ].
	 *
	 * @return string
	 */
	protected function build_multipart_response( string $boundary, array $subs ): string {
		$out = '';
		foreach ( $subs as $key => $sub ) {
			$out .= "--{$boundary}\r\n";
			$out .= "Content-Type: application/http\r\n";
			$out .= "Content-ID: <response-item{$key}>\r\n\r\n";
			$out .= "HTTP/1.1 {$sub[0]} OK\r\n";
			$out .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
			$out .= wp_json_encode( $sub[1] ) . "\r\n\r\n";
		}
		$out .= "--{$boundary}--\r\n";

		return $out;
	}
}
