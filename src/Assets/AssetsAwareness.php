<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;

/**
 * Trait AssetsAwareness
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
trait AssetsAwareness {

	/**
	 * Assets handler instance to use.
	 *
	 * @var AssetsHandler
	 */
	protected $assets_handler;

	/**
	 * Array of asset objects.
	 *
	 * @var Asset[]
	 */
	protected $assets = [];

	/**
	 * Get the array of known assets.
	 *
	 * @return Asset[]
	 */
	protected function get_assets(): array {
		return $this->assets;
	}

	/**
	 * Register known assets.
	 */
	protected function register_assets(): void {
		foreach ( $this->get_assets() as $asset ) {
			$this->assets_handler->add( $asset );
		}
	}

	/**
	 * Enqueue known assets.
	 */
	protected function enqueue_assets(): void {
		foreach ( $this->get_assets() as $asset ) {
			$this->assets_handler->enqueue_handle( $asset->get_handle() );
		}
	}

	/**
	 * Enqueue a single asset.
	 *
	 * @param string $handle Handle of the asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset handle is not valid.
	 */
	protected function enqueue_asset( $handle ): void {
		$this->assets_handler->enqueue_handle( $handle );
	}
}
