<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Assets;

use Automattic\WooCommerce\GoogleForWC\Exception\InvalidURI;
use Automattic\WooCommerce\GoogleForWC\PluginHelper;

/**
 * Trait SourceHelper
 *
 * @package Automattic\WooCommerce\GoogleForWC\Assets
 */
trait SourceHelper {

	use PluginHelper;

	/**
	 * Normalize a source path with a given file extension.
	 *
	 * @param string $path           The path to normalize.
	 * @param string $file_extension The file extension for the soure.
	 *
	 * @return string
	 */
	protected function normalize_source_path( string $path, string $file_extension ): string {
		$path = ltrim( $path, '/\\' );
		$path = $this->maybe_add_extension( $path, $file_extension );
		$path = trailingslashit( $this->get_root_dir() ) . $path;

		return $this->maybe_add_minimized_extension( $path, $file_extension );
	}

	/**
	 * Possibly add an extension to a path.
	 *
	 * @param string $path      Path where an extension may be needed.
	 * @param string $extension Extension to use.
	 *
	 * @return string
	 */
	protected function maybe_add_extension( string $path, string $extension ): string {
		$detected_extension = pathinfo( $path, PATHINFO_EXTENSION );

		if ( $extension !== $detected_extension ) {
			$path .= ".{$extension}";
		}

		return $path;
	}

	/**
	 * Possibly add a minimized extension to a path.
	 *
	 * @param string $path      Path where a minimized extension may be needed.
	 * @param string $extension The normal file extension.
	 *
	 * @return string
	 */
	protected function maybe_add_minimized_extension( string $path, string $extension ): string {
		$minimized_path = str_replace( ".{$extension}", ".min.{$extension}", $path );

		// Validate that at least one version of the file exists.
		$path_readable      = is_readable( $path );
		$minimized_readable = is_readable( $minimized_path );
		if ( ! $path_readable && ! $minimized_readable ) {
			InvalidURI::asset_path( $path );
		}

		// If we only have one available, return the available one no matter what.
		if ( ! $minimized_readable ) {
			return $path;
		} elseif ( ! $path_readable ) {
			return $minimized_path;
		}

		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $path : $minimized_path;
	}
}
