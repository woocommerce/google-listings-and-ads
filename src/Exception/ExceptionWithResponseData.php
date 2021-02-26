<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class ExceptionWithResponseData
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class ExceptionWithResponseData extends Exception implements GoogleListingsAndAdsException {

	/** @var array $response_data */
	private $response_data = [];

	/**
	 * Construct the exception. Note: The message is NOT binary safe.
	 *
	 * @link https://php.net/manual/en/exception.construct.php
	 *
	 * @param string         $message [optional] The Exception message to throw.
	 * @param int            $code [optional] The Exception code.
	 * @param Throwable|null $previous [optional] The previous throwable used for the exception chaining.
	 * @param array          $data [optional] Extra data to attach to the exception (ostensibly for use in an HTTP response).
	 */
	public function __construct( string $message = '', int $code = 0, Throwable $previous = null, array $data = [] ) {
		parent::__construct( $message, $code, $previous );

		if ( ! empty( $data ) && is_array( $data ) ) {
			$this->response_data = $data;
		}
	}

	/**
	 * @param bool $with_message include the message in the returned data array.
	 *
	 * @return array
	 */
	public function get_response_data( bool $with_message = false ): array {
		if ( $with_message ) {
			return array_merge( [ 'message' => $this->getMessage() ], $this->response_data );
		}
		return $this->response_data;
	}

	/**
	 * @param array $response_data
	 */
	public function set_response_data( array $response_data ) {
		$this->response_data = $response_data;
	}
}
