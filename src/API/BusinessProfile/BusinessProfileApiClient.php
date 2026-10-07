<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\ClientInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\TransferException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileApiClient
 *
 * Small wrapper over Guzzle for talking to the Business Profile APIs. Routes
 * through the Connect Server proxy, throws {@see BusinessProfileApiException} on a non-2xx
 * response or a network failure.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class BusinessProfileApiClient {

	/** @var int Status reported when the request gets no response at all. */
	private const NETWORK_ERROR_STATUS = 503;

	/** @var ClientInterface */
	private $http;

	/** @var string */
	private $base_url;

	/**
	 * BusinessProfileApiClient constructor.
	 *
	 * @param ClientInterface $http     Guzzle HTTP client.
	 * @param string          $base_url Connect Server Business Profile proxy root.
	 */
	public function __construct( ClientInterface $http, string $base_url ) {
		$this->http     = $http;
		$this->base_url = rtrim( $base_url, '/' ) . '/';
	}

	/**
	 * Send a GET request and decode the JSON response.
	 *
	 * @param string $path  Resource path appended to the base URL.
	 * @param array  $query Query parameters. The request skips null and empty string values.
	 *
	 * @return array Decoded response body.
	 * @throws BusinessProfileApiException On a non-2xx response or a network failure.
	 * @throws AccountReconnect When the Jetpack connection or the Google Account needs reconnecting.
	 */
	public function get( string $path, array $query = [] ): array {
		$url   = $this->base_url . ltrim( $path, '/' );
		$query = array_filter(
			$query,
			static function ( $value ) {
				return null !== $value && '' !== $value;
			}
		);

		if ( ! empty( $query ) ) {
			$url .= '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}

		try {
			$response = $this->http->send( new Request( 'GET', $url ) );

			return $this->decode_response( $response );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				throw new BusinessProfileApiException(
					$e->getResponse()->getStatusCode(),
					$this->decode_response( $e->getResponse() ),
					__METHOD__,
					$e
				);
			}

			throw new BusinessProfileApiException( self::NETWORK_ERROR_STATUS, [], __METHOD__, $e );
		} catch ( TransferException $e ) {
			// A connection error, such as a refused connection or a DNS failure, is not a
			// RequestException in Guzzle 7, so it needs its own catch.
			throw new BusinessProfileApiException( self::NETWORK_ERROR_STATUS, [], __METHOD__, $e );
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
