<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterApiException
 *
 * Represents a structured error originating from Merchant Center account
 * create / link / website claim operations performed via the remote manager API.
 */
class MerchantCenterApiException extends Exception {

	/** @var string */
	protected $error_code;

	/** @var array<string,string> */
	protected $errors = [];

	/**
	 * @param string               $message    Human readable message.
	 * @param int                  $status     HTTP status code.
	 * @param string               $error_code Top level error code (MerchantCenterErrorCode constant).
	 * @param array<string,string> $errors     Detailed errors mapping code => message.
	 * @param Throwable|null       $previous   Previous exception.
	 */
	public function __construct( string $message, int $status, string $error_code, array $errors = [], ?Throwable $previous = null ) {
		parent::__construct( $message, $status, $previous );
		$this->error_code = $error_code;
		$this->errors     = ! empty( $errors ) ? $errors : [ $error_code => $message ];
	}

	/**
	 * Get the error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Get the detailed errors.
	 *
	 * @return array<string,string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Format errors as list of objects for REST response.
	 *
	 * @return array
	 */
	public function get_errors_objects(): array {
		$formatted = [];

		foreach ( $this->errors as $code => $message ) {
			$formatted[] = [
				'code'    => $code,
				'message' => $message,
			];
		}
		return $formatted;
	}
}
