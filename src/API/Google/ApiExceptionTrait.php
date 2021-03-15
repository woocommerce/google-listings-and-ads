<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\ApiCore\ApiException;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Trait ApiExceptionTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait ApiExceptionTrait {

	/**
	 * Check if the ApiException contains a specific error.
	 *
	 * @param ApiException $exception  Exception to check.
	 * @param string       $error_code Error code we are checking.
	 *
	 * @return bool
	 */
	protected function has_api_exception_error( ApiException $exception, string $error_code ): bool {
		$meta = $exception->getMetadata();
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			return false;
		}

		foreach ( $meta as $data ) {
			if ( empty( $data['errors'] || ! is_array( $data['errors'] ) ) ) {
				continue;
			}

			foreach ( $data['errors'] as $error ) {
				if ( in_array( $error_code, $error['errorCode'], true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get an error message from a ClientException.
	 *
	 * @param ClientExceptionInterface $exception Exception to check.
	 * @param string                   $default   Default error message.
	 *
	 * @return string
	 */
	protected function client_exception_message( ClientExceptionInterface $exception, string $default ): string {
		if ( $exception instanceof BadResponseException ) {
			$response = json_decode( $exception->getResponse()->getBody()->getContents(), true );
			$message  = $response['message'] ?? false;
			return $message ? $default . ': ' . $message : $default;
		}
		return $default;
	}
}
