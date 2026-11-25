<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileApiException
 *
 * Wraps a failed Business Profile request: a non-2xx response from the API proxy, a network
 * failure, or paging that would never end. Its response data keeps Google's decoded error,
 * so a REST response can pass it on to the account card.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class BusinessProfileApiException extends ExceptionWithResponseData {

	/** @var int $http_status */
	protected $http_status;

	/** @var array $response_body */
	protected $response_body = [];

	/** @var array $errors */
	protected $errors = [];

	/**
	 * BusinessProfileApiException constructor.
	 *
	 * @param int            $http_status   Google's HTTP status, or the client's own status when no response arrived.
	 * @param array          $response_body Decoded response body.
	 * @param string         $method        Calling method, passed to the logging action.
	 * @param Throwable|null $previous      Optional previous throwable.
	 */
	public function __construct( int $http_status, array $response_body, string $method, ?Throwable $previous = null ) {
		$this->http_status   = $http_status;
		$this->response_body = $response_body;
		$this->errors        = is_array( $response_body['error']['errors'] ?? null ) ? $response_body['error']['errors'] : [];

		// An error from the proxy itself, such as a bad path, has a flat `message`.
		// A proxied Google API error nests it under `error`.
		$message = $response_body['error']['message'] ?? $response_body['message'] ?? null;

		// Merchants see this message, and a network failure has no response to take it from.
		if ( ! is_string( $message ) || '' === $message ) {
			$message = __( 'Unable to reach Google Business Profile. Please try again.', 'google-listings-and-ads' );
		}

		$error = is_array( $response_body['error'] ?? null ) ? $response_body['error'] : [];

		parent::__construct(
			$message,
			$http_status,
			$previous,
			[
				'code' => 'API_ERROR',
				'data' => $error,
			]
		);

		/**
		 * Fires when a Business Profile request fails: a non-2xx response, a network failure,
		 * or paging that would never end.
		 *
		 * @param BusinessProfileApiException $exception The exception wrapping the failed response.
		 * @param string                      $method    The method that sent the request.
		 */
		do_action( 'woocommerce_gla_business_profile_client_exception', $this, $method );
	}

	/**
	 * @return int
	 */
	public function get_http_status(): int {
		return $this->http_status;
	}

	/**
	 * @return array
	 */
	public function get_response_body(): array {
		return $this->response_body;
	}

	/**
	 * Get the individual errors Google returned, each with a `reason` such as a missing permission or a used-up quota.
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}
}
