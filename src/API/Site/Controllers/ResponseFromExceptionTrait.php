<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Exception;
use WP_REST_Response as Response;

/**
 * Trait ResponseFromExceptionTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 *
 * @since 1.5.0
 */
trait ResponseFromExceptionTrait {

	/**
	 * Get REST response from an exception.
	 *
	 * @param Exception $exception
	 *
	 * @return Response
	 */
	protected function response_from_exception( Exception $exception ): Response {
		$code   = $exception->getCode();
		$status = $code && is_numeric( $code ) ? $code : 400;

		if ( $exception instanceof ExceptionWithResponseData ) {
			return new Response( $exception->get_response_data( true ), $status );
		}

		return new Response( [ 'message' => $exception->getMessage() ], $status );
	}
}
