<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\ExportException;

defined( 'ABSPATH' ) || exit;

/**
 * Class CsvExportWriter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer
 */
class CsvExportWriter {
	/**
	 * Name of the subdirectory inside uploads/ to store exports.
	 */
	protected const EXPORT_FOLDER = 'gla-exports';

	/**
	 * Apache rules denying all web access to a directory.
	 */
	private const HTACCESS_RULES = <<<'HTACCESS'
# Apache 2.4+
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>

# Apache 2.2
<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>

HTACCESS;

	/**
	 * Filesystem handler.
	 *
	 * @var WP_Filesystem_Direct
	 */
	protected $fs;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->fs = $wp_filesystem;
	}

	/**
	 * Create an export file inside a subfolder of the export directory.
	 *
	 * Both the export directory and the subfolder are protected against
	 * directory listing and direct web access.
	 *
	 * @param string $filename  File name without extension.
	 * @param string $subfolder Name of the subfolder to store the file in.
	 *
	 * @return string Full path to the file.
	 *
	 * @throws ExportException When unable to create a directory or file.
	 */
	public function create_file( string $filename, string $subfolder ): string {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			throw ExportException::upload_directory_error( $upload_dir['error'] );
		}

		if ( empty( $upload_dir['basedir'] ) || ! is_dir( $upload_dir['basedir'] ) ) {
			throw ExportException::invalid_upload_directory();
		}

		$export_path = trailingslashit( $upload_dir['basedir'] ) . self::EXPORT_FOLDER;
		$dir_path    = trailingslashit( $export_path ) . $subfolder;

		foreach ( [ $export_path, $dir_path ] as $path ) {
			if ( ! $this->fs->is_dir( $path ) ) {
				wp_mkdir_p( $path );

				if ( ! $this->fs->is_dir( $path ) ) {
					throw ExportException::failed_to_create_directory( $path );
				}
			}

			$this->protect_directory( $path );
		}

		$file = trailingslashit( $dir_path ) . $filename . '.csv';

		// Don't create file if already exists.
		if ( $this->fs->exists( $file ) ) {
			return $file;
		}

		$success = $this->fs->put_contents( $file, '' );

		if ( ! $success ) {
			throw ExportException::failed_to_create_file( $file );
		}

		return $file;
	}

	/**
	 * Append a row to the export file.
	 *
	 * @param string $file_path
	 * @param array  $row
	 * @return void
	 */
	public function append_row( string $file_path, array $row ): void {
		// Decode row values.
		$decoded_row = array_map(
			static function ( $value ) {
				return is_string( $value ) ? html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $value;
			},
			$row
		);

		// Read existing content.
		$content  = $this->fs->get_contents( $file_path );
		$is_empty = empty( $content );

		/**
		 * Use php://temp for in-memory buffering of CSV data.
		 *
		 * This avoids touching the real filesystem and ensures compatibility across
		 * environments. We use fputcsv to generate well-formatted lines, which would
		 * otherwise require unsafe escaping logic if concatenated manually.
		 *
		 * Although WordPress Coding Standards warn against fopen/fclose,
		 * this stream is fully memory-backed and safe to use.
		 */
		$fp = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( $is_empty ) {
			fputcsv( $fp, array_keys( $decoded_row ), ',', '"', '\\' );
		}

		fputcsv( $fp, array_values( $decoded_row ), ',', '"', '\\' );
		rewind( $fp );

		$new_data = stream_get_contents( $fp );

		/**
		 * Closes the temporary in-memory stream used for CSV formatting.
		 *
		 * Safe to ignore PHPCS warning because no real file I/O occurs.
		 */
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$existing = $is_empty ? '' : $this->fs->get_contents( $file_path );
		$this->fs->put_contents( $file_path, $existing . $new_data, FS_CHMOD_FILE );
	}

	/**
	 * Get the size of a file in bytes.
	 *
	 * @param string $file_path Full path to the file.
	 * @return int File size in bytes, or 0 if file doesn't exist.
	 */
	public function get_file_size( string $file_path ): int {
		if ( ! $this->fs->exists( $file_path ) ) {
			return 0;
		}

		// Clear PHP's stat cache to get accurate size after recent writes.
		clearstatcache( true, $file_path );

		return (int) $this->fs->size( $file_path );
	}

	/**
	 * Delete a file.
	 *
	 * @param string $file_path Full path to the file.
	 * @return bool True on success, false on failure.
	 */
	public function delete_file( string $file_path ): bool {
		if ( ! $this->fs->exists( $file_path ) ) {
			return false;
		}

		return $this->fs->delete( $file_path );
	}

	/**
	 * Add an empty index.html and a deny-all .htaccess to a directory.
	 *
	 * Existing files are left untouched.
	 *
	 * @param string $dir_path Full path to the directory.
	 * @return void
	 */
	private function protect_directory( string $dir_path ): void {
		$files = [
			'index.html' => '',
			'.htaccess'  => self::HTACCESS_RULES,
		];

		foreach ( $files as $name => $contents ) {
			$file = trailingslashit( $dir_path ) . $name;

			if ( ! $this->fs->exists( $file ) ) {
				$this->fs->put_contents( $file, $contents, FS_CHMOD_FILE );
			}
		}
	}
}
