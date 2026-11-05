<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantApiException
 *
 * Wraps a non-2xx response from the Merchant API.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi
 */
class MerchantApiException extends Exception implements GoogleListingsAndAdsException {

	/** @var int $http_status */
	protected $http_status;

	/** @var array $response_body */
	protected $response_body = [];

	/** @var array $errors */
	protected $errors = [];

	/**
	 * MerchantApiException constructor.
	 *
	 * @param int            $http_status   HTTP status code from the MAPI response.
	 * @param array          $response_body Decoded response body.
	 * @param string         $method        Calling method (passed to the logging action).
	 * @param Throwable|null $previous      Optional previous throwable.
	 */
	public function __construct( int $http_status, array $response_body, string $method, ?Throwable $previous = null ) {
		$this->http_status   = $http_status;
		$this->response_body = $response_body;
		$this->errors        = $response_body['error']['errors'] ?? [];

		$message = $response_body['error']['message'] ?? 'Merchant API request failed';

		parent::__construct( $message, $http_status, $previous );

		do_action( 'woocommerce_gla_mc_client_exception', $this, $method );
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
	 * Whether a decoded Merchant API error response says the caller has no access to the account.
	 *
	 * Google answers a request for an account the caller can't access with a 401 UNAUTHENTICATED
	 * status, the same status as an expired sign-in. Only this error detail tells the two apart.
	 *
	 * @param array $response_body Decoded response body.
	 *
	 * @return bool
	 */
	public static function response_denies_account_access( array $response_body ): bool {
		$details = $response_body['error']['details'] ?? [];
		if ( ! is_array( $details ) ) {
			return false;
		}

		foreach ( $details as $detail ) {
			if (
				is_array( $detail ) &&
				'merchantapi.googleapis.com' === ( $detail['domain'] ?? '' ) &&
				'PERMISSION_DENIED_ACCOUNTS' === ( $detail['metadata']['REASON'] ?? '' )
			) {
				return true;
			}
		}

		return false;
	}
}
