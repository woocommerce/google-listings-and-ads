<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use Closure;

/**
 * Trait ScriptHelper
 *
 * Helper functions related to scripts that are useful throughout the plugin.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
trait ScriptHelper {

	use SourceHelper;

	/**
	 * The asset handle.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * The full URI to the script.
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * Array of dependencies for the script.
	 *
	 * @var array
	 */
	protected $dependencies = [];

	/**
	 * The version string for the script.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Whether the script should be printed in the footer.
	 *
	 * @var bool
	 */
	protected $in_footer = false;

	/**
	 * Array of localizations to add to the script.
	 *
	 * @var array
	 */
	protected $localizations = [];

	/**
	 * Array of inline scripts to pass generic data from PHP to JavaScript with JSON format.
	 *
	 * @var array
	 */

	protected $inline_scripts = [];
	/**
	 * ScriptHelper constructor.
	 *
	 * @param string $handle       The script handle.
	 * @param string $uri          The URI for the script.
	 * @param array  $dependencies (Optional) Any dependencies the script has.
	 * @param string $version      (Optional) A version string for the script. Will default to the plugin version
	 *                             if not set.
	 * @param bool   $in_footer    (Optional) Whether the script should be printed in the footer. Defaults to false.
	 */
	public function __construct(
		string $handle,
		string $uri,
		array $dependencies = [],
		string $version = '',
		bool $in_footer = false
	) {
		$this->file_extension = 'js';
		$this->handle         = $handle;
		$this->uri            = $this->get_uri_from_path( $uri );
		$this->dependencies   = $dependencies;
		$this->version        = $version ?: $this->get_version();
		$this->in_footer      = $in_footer;
	}

	/**
	 * Add a localization to the script.
	 *
	 * @param string $object The object name.
	 * @param array  $data   Array of data for the object.
	 *
	 * @return $this
	 */
	public function add_localization( string $object, array $data ) {
		$this->localizations[ $object ] = $data;

		return $this;
	}

	/**
	 * Add a inline script to pass generic data from PHP to JavaScript.
	 *
	 * @param string $variable_name The global JavaScript variable name.
	 * @param array  $data          Array of data to be encoded to JSON format.
	 *
	 * @return $this
	 */
	public function add_inline_script( string $variable_name, array $data ) {
		$this->inline_scripts[ $variable_name ] = $data;

		return $this;
	}

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_register_closure(): Closure {
		return function() {
			if ( wp_script_is( $this->handle, 'registered' ) ) {
				return;
			}

			wp_register_script(
				$this->handle,
				$this->uri,
				$this->dependencies,
				$this->version,
				$this->in_footer
			);
		};
	}

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_enqueue_closure(): Closure {
		return function() {
			if ( ! wp_script_is( $this->handle, 'registered' ) ) {
				throw InvalidAsset::asset_not_registered( $this->handle );
			}

			foreach ( $this->localizations as $object_name => $data_array ) {
				wp_localize_script( $this->handle, $object_name, $data_array );
			}

			foreach ( $this->inline_scripts as $variable_name => $data_array ) {
				$inline_script = "var $variable_name = " . json_encode( $data_array );
				wp_add_inline_script( $this->handle, $inline_script, 'before' );
			}

			wp_enqueue_script( $this->handle );
		};
	}

	/**
	 * Get the dequeue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_dequeue_closure(): Closure {
		return function() {
			wp_dequeue_script( $this->handle );
		};
	}
}
