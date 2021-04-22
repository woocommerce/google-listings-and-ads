<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use Closure;

/**
 * Class StyleAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
class StyleAsset extends BaseAsset {

	/**
	 * The media for which this stylesheet has been defined.
	 *
	 * @var string
	 */
	protected $media;

	/**
	 * StyleAsset constructor.
	 *
	 * @param string       $handle         The asset handle.
	 * @param string       $uri            The URI for the asset.
	 * @param array        $dependencies   (Optional) Any dependencies the asset has.
	 * @param string       $version        (Optional) A version string for the asset. Will default to the plugin version
	 *                                     if not set.
	 * @param Closure|null $load_condition (Optional) Only enqueue the asset if this condition closure returns true.
	 *                                     Returns true by default.
	 * @param string       $media          Optional. The media for which this stylesheet has been defined.
	 *                                     Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media
	 *                                     queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function __construct(
		string $handle,
		string $uri,
		array $dependencies = [],
		string $version = '',
		Closure $load_condition = null,
		string $media = 'all'
	) {
		$this->media = $media;
		parent::__construct( 'css', $handle, $uri, $dependencies, $version, $load_condition );
	}

	/**
	 * Get the register closure to use.
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
