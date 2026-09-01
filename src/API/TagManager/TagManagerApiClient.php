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
	protected $http;

	/** @var string */
	protected $base_url;

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
		$url     = $this->base_url . ltrim( $path, '/' );
		$request = new Request( 'GET', $url );

		try {
			$response = $this->http->send( $request );

			return $this->decode_response( $response );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				throw new TagManagerApiException(
					$e->getResponse()->getStatusCode(),
					$this->decode_response( $e->getResponse() ),
					__METHOD__,
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
	protected function decode_response( ResponseInterface $response ): array {
		$body    = (string) $response->getBody();
		$decoded = '' === $body ? [] : json_decode( $body, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
