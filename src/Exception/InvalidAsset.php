<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use LogicException;

/**
 * Class InvalidAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidAsset extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Return an instance of the exception when an asset attempts to be enqueued without first being
	 * registered.
	 *
	 * @param string $handle The asset handle.
	 *
	 * @return static
	 */
	public static function asset_not_registered( string $handle ) {
		return new static(
			sprintf(
				'The asset "%s" was not registered before it was enqueued. The register() method must be called during init.',
				$handle
			)
		);
	}

	/**
	 * Return an instance of the exception when an asset handle is invalid.
	 *
	 * @param string $handle The invalid handle.
	 *
	 * @return static
	 */
	public static function invalid_handle( string $handle ) {
		return new static( sprintf( 'The asset handle "%s" is invalid.', $handle ) );
	}

	/**
	 * Return a new instance of the exception when an asset with the given handle already exists.
	 *
	 * @param string $handle The asset handle that exists.
	 *
	 * @return static
	 */
	public static function handle_exists( string $handle ) {
		return new static( sprintf( 'The asset handle "%s" already exists.', $handle ) );
	}

	/**
	 * Create a new exception for an unreadable asset.
	 *
	 * @param string $path
	 *
	 * @return static
	 */
	public static function invalid_path( string $path ) {
		return new static( sprintf( 'The asset "%s" is unreadable. Do build scripts need to be run?', $path ) );
	}
}
