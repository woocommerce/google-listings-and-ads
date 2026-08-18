<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\ClientInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchConsoleApiClient
 *
 * Small wrapper over Guzzle for talking to the Search Console Sites API. Routes
 * through the Connect Server proxy, throws {@see SearchConsoleApiException} on non-2xx.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class SearchConsoleApiClient {

	/** @var ClientInterface */
	protected $http;

	/** @var string */
	protected $base_url;

	/**
	 * SearchConsoleApiClient constructor.
	 *
	 * @param ClientInterface $http     Guzzle HTTP client.
	 * @param string          $base_url Connect Server Search Console proxy root.
	 */
	public function __construct( ClientInterface $http, string $base_url ) {
		$this->http     = $http;
		$this->base_url = rtrim( $base_url, '/' ) . '/';
	}

	/**
	 * Send a request and decode the JSON response.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Resource path appended to the base URL.
	 * @param array|null $body   Optional body to be JSON-encoded.
	 *
	 * @return array Decoded response body.
	 * @throws SearchConsoleApiException On non-2xx response.
	 */
	public function request( string $method, string $path, ?array $body = null ): array {
		$url     = $this->base_url . ltrim( $path, '/' );
		$headers = [];
		$payload = null;

		if ( null !== $body ) {
			$payload                 = (string) wp_json_encode( $body );
			$headers['Content-Type'] = 'application/json';
		}

		$request      = new Request( $method, $url, $headers, $payload );
		$method_label = __METHOD__;

		try {
			$response = $this->http->send( $request );

			return $this->decode_response( $response );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				throw new SearchConsoleApiException(
					$e->getResponse()->getStatusCode(),
					$this->decode_response( $e->getResponse() ),
					$method_label,
					$e
				);
			}

			throw $e;
		}
	}

	/**
	 * @param string $path
	 *
	 * @return array
	 * @throws SearchConsoleApiException On non-2xx response.
	 */
	public function get( string $path ): array {
		return $this->request( 'GET', $path );
	}

	/**
	 * @param string     $path
	 * @param array|null $body Defaults to no request body — `sites.add` takes none.
	 *
	 * @return array
	 * @throws SearchConsoleApiException On non-2xx response.
	 */
	public function put( string $path, ?array $body = null ): array {
		return $this->request( 'PUT', $path, $body );
	}

	/**
	 * @param string $path
	 *
	 * @return array
	 * @throws SearchConsoleApiException On non-2xx response.
	 */
	public function delete( string $path ): array {
		return $this->request( 'DELETE', $path );
	}

	/**
	 * Decode a JSON response body to an array.
	 *
	 * @param ResponseInterface $response
	 *
	 * @return array
	 */
	protected function decode_response( ResponseInterface $response ): array {
		$body    = (string) $response->getBody();
		$decoded = '' === $body ? [] : json_decode( $body, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
