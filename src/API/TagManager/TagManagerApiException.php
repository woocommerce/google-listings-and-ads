<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerApiException
 *
 * Wraps a non-2xx response from the Tag Manager API proxy.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager
 */
class TagManagerApiException extends Exception implements GoogleListingsAndAdsException {

	/** @var int $http_status */
	protected $http_status;

	/** @var array $response_body */
	protected $response_body = [];

	/** @var array $errors */
	protected $errors = [];

	/**
	 * TagManagerApiException constructor.
	 *
	 * @param int            $http_status   HTTP status code from the Tag Manager API response.
	 * @param array          $response_body Decoded response body.
	 * @param string         $method        Calling method (passed to the logging action).
	 * @param Throwable|null $previous      Optional previous throwable.
	 */
	public function __construct( int $http_status, array $response_body, string $method, ?Throwable $previous = null ) {
		$this->http_status   = $http_status;
		$this->response_body = $response_body;
		$this->errors        = $response_body['error']['errors'] ?? [];

		// The proxy itself (a bad path, an unsupported scope) returns a flat
		// `message` string; a proxied Google API error nests it under `error`.
		$message = $response_body['error']['message'] ?? $response_body['message'] ?? 'Tag Manager API request failed';

		parent::__construct( $message, $http_status, $previous );

		do_action( 'woocommerce_gla_tag_manager_client_exception', $this, $method );
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
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}
}
