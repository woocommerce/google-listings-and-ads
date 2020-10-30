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
	 * The file extension for the source.
	 *
	 * @var string
	 */
	protected $file_extension;

	/**
	 * Convert a file path to a URI for a source.
	 *
	 * @param string $path The source file path.
	 *
	 * @return string
	 */
	protected function get_uri_from_path( string $path ): string {
		$path = $this->normalize_source_path( $path );
		$path = str_replace( $this->get_root_dir(), '', $path );

		return $this->get_plugin_url( $path );
	}

	/**
	 * Normalize a source path with a given file extension.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string
	 */
	protected function normalize_source_path( string $path ): string {
		$path = ltrim( $path, '/' );
		$path = $this->maybe_add_extension( $path );
		$path = "{$this->get_root_dir()}/{$path}";

		return $this->maybe_add_minimized_extension( $path );
	}

	/**
	 * Possibly add an extension to a path.
	 *
	 * @param string $path Path where an extension may be needed.
	 *
	 * @return string
	 */
	protected function maybe_add_extension( string $path ): string {
		$detected_extension = pathinfo( $path, PATHINFO_EXTENSION );
		if ( $this->file_extension !== $detected_extension ) {
			$path .= ".{$this->file_extension}";
		}

		return $path;
	}

	/**
	 * Possibly add a minimized extension to a path.
	 *
	 * @param string $path Path where a minimized extension may be needed.
	 *
	 * @return string
	 */
	protected function maybe_add_minimized_extension( string $path ): string {
		$minimized_path = str_replace( ".{$this->file_extension}", ".min.{$this->file_extension}", $path );

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
