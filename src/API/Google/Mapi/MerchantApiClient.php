<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\ClientInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\PromiseInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantApiClient
 *
 * Small wrapper over Guzzle for talking to the Merchant API. Routes through the
 * Connect Server proxy
 * throws {@see MerchantApiException} on non-2xx.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi
 */
class MerchantApiClient {

	/** @var ClientInterface */
	protected $http;

	/** @var string */
	protected $base_url;

	/**
	 * MerchantApiClient constructor.
	 *
	 * @param ClientInterface $http     Guzzle HTTP client.
	 * @param string          $base_url Connect Server MAPI proxy root
	 */
	public function __construct( ClientInterface $http, string $base_url ) {
		$this->http     = $http;
		$this->base_url = rtrim( $base_url, '/' ) . '/';
	}

	/**
	 * Send a request synchronously.
	 *
	 * @param string     $method  HTTP method.
	 * @param string     $path    Resource path appended to the base URL.
	 * @param array|null $body    Optional body to be JSON-encoded.
	 * @param array      $headers Optional extra headers.
	 *
	 * @return array Decoded response body.
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function request( string $method, string $path, ?array $body = null, array $headers = [] ): array {
		return $this->request_async( $method, $path, $body, $headers )->wait();
	}

	/**
	 * Send a request asynchronously.
	 *
	 * @param string     $method  HTTP method.
	 * @param string     $path    Resource path appended to the base URL.
	 * @param array|null $body    Optional body to be JSON-encoded.
	 * @param array      $headers Optional extra headers.
	 *
	 * @return PromiseInterface
	 */
	public function request_async( string $method, string $path, ?array $body = null, array $headers = [] ): PromiseInterface {
		$url     = $this->base_url . ltrim( $path, '/' );
		$payload = null;

		if ( null !== $body ) {
			$payload                 = (string) wp_json_encode( $body );
			$headers['Content-Type'] = 'application/json';
		}

		$request      = new Request( $method, $url, $headers, $payload );
		$method_label = __METHOD__;

		return $this->http->sendAsync( $request )->then(
			function ( ResponseInterface $response ): array {
				return $this->decode_response( $response );
			},
			function ( $reason ) use ( $method_label ) {
				if ( $reason instanceof RequestException && $reason->hasResponse() ) {
					throw new MerchantApiException(
						$reason->getResponse()->getStatusCode(),
						$this->decode_response( $reason->getResponse() ),
						$method_label,
						$reason
					);
				}

				throw $reason;
			}
		);
	}

	/**
	 * @param string $path
	 *
	 * @return array
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function get( string $path ): array {
		return $this->request( 'GET', $path );
	}

	/**
	 * @param string $path
	 *
	 * @return PromiseInterface
	 */
	public function get_async( string $path ): PromiseInterface {
		return $this->request_async( 'GET', $path );
	}

	/**
	 * @param string $path
	 * @param array  $body
	 *
	 * @return array
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function post( string $path, array $body ): array {
		return $this->request( 'POST', $path, $body );
	}

	/**
	 * @param string $path
	 * @param array  $body
	 *
	 * @return array
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function patch( string $path, array $body ): array {
		return $this->request( 'PATCH', $path, $body );
	}

	/**
	 * @param string $path
	 *
	 * @return array
	 * @throws MerchantApiException On non-2xx response.
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
