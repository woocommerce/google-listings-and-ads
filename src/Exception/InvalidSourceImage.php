<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;

/**
 * Class InvalidSourceImage
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidSourceImage extends RuntimeException implements GoogleListingsAndAdsException {

	public const REASON_FETCH_FAILED = 'fetch_failed';
	public const REASON_TOO_LARGE    = 'too_large';

	/**
	 * The reason the source image was invalid.
	 *
	 * @var string
	 */
	protected $reason;

	/**
	 * Return an instance of the exception for a source image URL that could not be fetched.
	 *
	 * @param string $url The source image URL.
	 *
	 * @return static
	 */
	public static function fetch_failed( string $url ) {
		$exception         = new static( sprintf( 'There was a problem loading the url: %s', $url ) );
		$exception->reason = self::REASON_FETCH_FAILED;

		return $exception;
	}

	/**
	 * Return an instance of the exception for a source image exceeding the maximum allowed size.
	 *
	 * @return static
	 */
	public static function too_large() {
		$exception         = new static( 'Image size is too large.' );
		$exception->reason = self::REASON_TOO_LARGE;

		return $exception;
	}

	/**
	 * Get the reason the source image was invalid.
	 *
	 * @return string
	 */
	public function get_reason(): string {
		return $this->reason;
	}
}
