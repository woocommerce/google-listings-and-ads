<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use Closure;

trait StyleHelper {

	use SourceHelper;

	/**
	 * The asset handle.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * The full URI to the style.
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * Array of dependencies for the style.
	 *
	 * @var array
	 */
	protected $dependencies = [];

	/**
	 * The version string for the style.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * The media for which this stylesheet has been defined.
	 *
	 * @var string
	 */
	protected $media;

	/**
	 * StyleHelper constructor.
	 *
	 * @param string $handle       The style handle.
	 * @param string $uri          The URI for the style.
	 * @param array  $dependencies (Optional) Any dependencies the style has.
	 * @param string $version      (Optional) A version string for the style. Will default to the plugin version
	 *                             if not set.
	 * @param string $media        Optional. The media for which this stylesheet has been defined.
	 *                             Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media
	 *                             queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function __construct(
		string $handle,
		string $uri,
		array $dependencies = [],
		string $version = '',
		string $media = 'all'
	) {
		$this->file_extension = 'css';
		$this->handle         = $handle;
		$this->uri            = $this->get_uri_from_path( $uri );
		$this->dependencies   = $dependencies;
		$this->version        = $version ?: $this->get_version();
		$this->media          = $media;
	}

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_register_closure(): Closure {
		return function() {
			if ( wp_style_is( $this->handle, 'registered' ) ) {
				return;
			}

			wp_register_style(
				$this->handle,
				$this->uri,
				$this->dependencies,
				$this->version,
				$this->media
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
			if ( ! wp_style_is( $this->handle, 'registered' ) ) {
				throw InvalidAsset::asset_not_registered( $this->handle );
			}

			wp_enqueue_style( $this->handle );
		};
	}

	/**
	 * Get the dequeue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_dequeue_closure(): Closure {
		return function() {
			wp_dequeue_style( $this->handle );
		};
	}
}
