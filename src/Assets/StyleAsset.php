<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;

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
	 * @param string        $handle                        The asset handle.
	 * @param string        $uri                           The URI for the asset.
	 * @param array         $dependencies                  (Optional) Any dependencies the asset has.
	 * @param string        $version                       (Optional) A version string for the asset. Will default to the plugin version
	 *                                                     if not set.
	 * @param callable|null $enqueue_condition_callback    (Optional) The asset is always enqueued if this callback
	 *                                                     returns true or isn't set.
	 * @param string        $media                         Optional. The media for which this stylesheet has been defined.
	 *                                                     Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media
	 *                                                     queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function __construct(
		string $handle,
		string $uri,
		array $dependencies = [],
		string $version = '',
		callable $enqueue_condition_callback = null,
		string $media = 'all'
	) {
		$this->media = $media;
		parent::__construct( 'css', $handle, $uri, $dependencies, $version, $enqueue_condition_callback );
	}

	/**
	 * Get the register callback to use.
	 *
	 * @return callable
	 */
	protected function get_register_callback(): callable {
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
	 * Get the enqueue callback to use.
	 *
	 * @return callable
	 */
	protected function get_enqueue_callback(): callable {
		return function() {
			if ( ! wp_style_is( $this->handle, 'registered' ) ) {
				throw InvalidAsset::asset_not_registered( $this->handle );
			}

			wp_enqueue_style( $this->handle );
		};
	}

	/**
	 * Get the dequeue callback to use.
	 *
	 * @return callable
	 */
	protected function get_dequeue_callback(): callable {
		return function() {
			wp_dequeue_style( $this->handle );
		};
	}

}
