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

	private const BATCH_BOUNDARY_PREFIX = 'gla_mapi_batch_boundary';

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
	 * Send a batch of sub-requests as a single multipart/mixed HTTP request and return
	 * the per-sub-request results keyed by the input key.
	 *
	 * @param array<int|string, array{method: string, path: string, body?: array}> $requests
	 *
	 * @return array<int|string, array{status: int, body: array}>
	 * @throws MerchantApiException On a non-2xx response for the batch request itself.
	 */
	public function batch( array $requests ): array {
		return $this->batch_async( $requests )->wait();
	}

	/**
	 * Asynchronous variant of {@see batch()}.
	 *
	 * @param array<int|string, array{method: string, path: string, body?: array}> $requests
	 *
	 * @return PromiseInterface Resolves to array<int|string, array{status: int, body: array}>.
	 */
	public function batch_async( array $requests ): PromiseInterface {
		$request      = $this->build_batch_request( $requests );
		$method_label = __METHOD__;

		return $this->http->sendAsync( $request )->then(
			function ( ResponseInterface $response ): array {
				return $this->parse_batch_response( $response );
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
	 * Build the multipart/mixed batch request. Each sub-request is an application/http
	 * part tagged with a Content-ID so response parts can be mapped back by key.
	 *
	 * @param array<int|string, array{method: string, path: string, body?: array}> $requests
	 *
	 * @return Request
	 */
	protected function build_batch_request( array $requests ): Request {
		$boundary = self::BATCH_BOUNDARY_PREFIX . '_' . wp_generate_password( 16, false );
		$body     = '';

		foreach ( $requests as $key => $sub ) {
			$body .= "--{$boundary}\r\n";
			$body .= "Content-Type: application/http\r\n";
			$body .= "Content-ID: <item{$key}>\r\n\r\n";
			$body .= sprintf( "%s /%s HTTP/1.1\r\n", $sub['method'], ltrim( $sub['path'], '/' ) );

			if ( isset( $sub['body'] ) ) {
				$body .= "Content-Type: application/json\r\n\r\n";
				$body .= (string) wp_json_encode( $sub['body'] ) . "\r\n";
			} else {
				$body .= "\r\n";
			}
		}

		$body .= "--{$boundary}--\r\n";

		return new Request(
			'POST',
			$this->base_url . 'batch',
			[ 'Content-Type' => "multipart/mixed; boundary={$boundary}" ],
			$body
		);
	}

	/**
	 * Parse a multipart/mixed batch response into per-sub-request results keyed by the
	 * original request key, extracted from each part's Content-ID.
	 *
	 * @param ResponseInterface $response
	 *
	 * @return array<int|string, array{status: int, body: array}>
	 */
	protected function parse_batch_response( ResponseInterface $response ): array {
		if ( ! preg_match( '/boundary=(?:"([^"]+)"|([^;\s]+))/', $response->getHeaderLine( 'Content-Type' ), $matches ) ) {
			do_action( 'woocommerce_gla_error', 'Merchant API batch response had no parseable multipart boundary.', __METHOD__ );
			return [];
		}

		$boundary = '' !== ( $matches[1] ?? '' ) ? $matches[1] : $matches[2];
		$results  = [];

		foreach ( explode( '--' . $boundary, (string) $response->getBody() ) as $part ) {
			$part = trim( $part );
			if ( '' === $part || '--' === $part ) {
				continue;
			}

			if ( ! preg_match( '/Content-ID:\s*<response-item([^>]+)>/i', $part, $id_match )
				|| ! preg_match( '#HTTP/\d(?:\.\d)?\s+(\d+)#', $part, $status_match ) ) {
				continue;
			}

			$http_offset = strpos( $part, 'HTTP/' );
			$inner_body  = '';
			if ( false !== $http_offset && preg_match( '/\r?\n\r?\n(.*)$/s', substr( $part, $http_offset ), $body_match ) ) {
				$inner_body = trim( $body_match[1] );
			}

			$decoded = '' === $inner_body ? [] : json_decode( $inner_body, true );

			$results[ $id_match[1] ] = [
				'status' => (int) $status_match[1],
				'body'   => is_array( $decoded ) ? $decoded : [],
			];
		}

		return $results;
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
