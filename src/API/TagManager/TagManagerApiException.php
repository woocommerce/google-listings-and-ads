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

	/**
	 * Reasons Google gives in an error body when a request fails because of a quota or rate limit.
	 *
	 * @var string[]
	 */
	protected const QUOTA_ERROR_REASONS = [ 'rateLimitExceeded', 'userRateLimitExceeded', 'quotaExceeded', 'dailyLimitExceeded' ];

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

	/**
	 * Whether the request failed because of a quota or rate limit.
	 *
	 * Google reports an exceeded quota as a 403, the same status as a missing permission, so a 403
	 * is told apart by the reason in its error body, or the quota wording in its message.
	 *
	 * @return bool
	 */
	public function is_quota_error(): bool {
		if ( 429 === $this->http_status ) {
			return true;
		}

		if ( 403 !== $this->http_status ) {
			return false;
		}

		foreach ( $this->errors as $error ) {
			if ( in_array( $error['reason'] ?? '', self::QUOTA_ERROR_REASONS, true ) ) {
				return true;
			}
		}

		return false !== stripos( $this->getMessage(), 'quota' );
	}

	/**
	 * Whether Tag Manager refused the request because the user lacks the permission for it.
	 *
	 * Google answers with a 403, or with a 404 whose message says "Not found or permission denied"
	 * so it doesn't reveal whether an inaccessible resource exists.
	 *
	 * @return bool
	 */
	public function is_permission_denied(): bool {
		if ( $this->is_quota_error() ) {
			return false;
		}

		if ( 403 === $this->http_status ) {
			return true;
		}

		return 404 === $this->http_status && false !== stripos( $this->getMessage(), 'permission denied' );
	}
}
