<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\ClientInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerApiClient
 *
 * Small wrapper over Guzzle for talking to the Tag Manager API. Routes through
 * the Connect Server proxy, throws {@see TagManagerApiException} on non-2xx.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager
 */
class TagManagerApiClient {

	/** @var ClientInterface */
	private $http;

	/** @var string */
	private $base_url;

	/**
	 * TagManagerApiClient constructor.
	 *
	 * @param ClientInterface $http     Guzzle HTTP client.
	 * @param string          $base_url Connect Server Tag Manager proxy root.
	 */
	public function __construct( ClientInterface $http, string $base_url ) {
		$this->http     = $http;
		$this->base_url = rtrim( $base_url, '/' ) . '/';
	}

	/**
	 * Send a GET request and decode the JSON response.
	 *
	 * @param string $path Resource path appended to the base URL.
	 *
	 * @return array Decoded response body.
	 * @throws TagManagerApiException On non-2xx response.
	 */
	public function get( string $path ): array {
		return $this->send( new Request( 'GET', $this->build_url( $path ) ), __METHOD__ );
	}

	/**
	 * Send a POST request with a JSON body and decode the JSON response.
	 *
	 * @param string $path Resource path appended to the base URL.
	 * @param array  $body Request payload, sent JSON-encoded.
	 *
	 * @return array Decoded response body.
	 * @throws TagManagerApiException On non-2xx response.
	 */
	public function post( string $path, array $body ): array {
		$request = new Request(
			'POST',
			$this->build_url( $path ),
			[ 'Content-Type' => 'application/json' ],
			wp_json_encode( $body )
		);

		return $this->send( $request, __METHOD__ );
	}

	/**
	 * @param string $path Resource path appended to the base URL.
	 *
	 * @return string
	 */
	private function build_url( string $path ): string {
		return $this->base_url . ltrim( $path, '/' );
	}

	/**
	 * Send a request and decode the JSON response.
	 *
	 * @param Request $request
	 * @param string  $method  Calling method (passed to the exception's logging action).
	 *
	 * @return array Decoded response body.
	 * @throws TagManagerApiException On non-2xx response.
	 */
	private function send( Request $request, string $method ): array {
		try {
			$response = $this->http->send( $request );

			return $this->decode_response( $response );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				throw new TagManagerApiException(
					$e->getResponse()->getStatusCode(),
					$this->decode_response( $e->getResponse() ),
					$method,
					$e
				);
			}

			throw $e;
		}
	}

	/**
	 * @param ResponseInterface $response
	 *
	 * @return array
	 */
	private function decode_response( ResponseInterface $response ): array {
		$body    = (string) $response->getBody();
		$decoded = '' === $body ? [] : json_decode( $body, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
