<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ExportException
 *
 * Exception thrown when export operations fail.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports
 */
class ExportException extends RuntimeException implements GoogleListingsAndAdsException {
	/**
	 * Return a new instance for upload directory errors.
	 *
	 * @param string $error The error message from wp_upload_dir().
	 * @return static
	 */
	public static function upload_directory_error( string $error ): ExportException {
		return new static( $error );
	}

	/**
	 * Return a new instance when unable to determine upload directory.
	 *
	 * @return static
	 */
	public static function invalid_upload_directory(): ExportException {
		return new static( 'Unable to determine upload directory.' );
	}

	/**
	 * Return a new instance when export directory creation fails.
	 *
	 * @param string $directory_path The path that failed to be created.
	 * @return static
	 */
	public static function failed_to_create_directory( string $directory_path ): ExportException {
		return new static( sprintf( 'Failed to create export directory: %s', esc_html( $directory_path ) ) );
	}

	/**
	 * Return a new instance when file creation fails.
	 *
	 * @param string $file_path The file path that failed to be created.
	 * @return static
	 */
	public static function failed_to_create_file( string $file_path ): ExportException {
		return new static( sprintf( 'Failed to create CSV file: %s', esc_html( $file_path ) ) );
	}
}
