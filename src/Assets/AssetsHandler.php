<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;

/**
 * Class AssetsHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
final class AssetsHandler implements AssetsHandlerInterface {

	/**
	 * Assets known to this asset handler, keyed by handle.
	 *
	 * A single handle may hold multiple assets of different classes (e.g. a
	 * script and a style paired under the same handle).
	 *
	 * @var array<string, Asset[]>
	 */
	private $assets = [];

	/**
	 * Register a single asset.
	 *
	 * Multiple assets of different classes may share a handle (e.g. a script
	 * and a style). Re-registering the same handle + class combination is a
	 * silent no-op, matching WordPress's own `wp_register_script` /
	 * `wp_register_style` behavior.
	 *
	 * @param Asset $asset Asset to register.
	 */
	public function register( Asset $asset ): void {
		if ( $this->is_asset_registered( $asset ) ) {
			return;
		}

		$this->assets[ $asset->get_handle() ][] = $asset;
		$asset->register();
	}

	/**
	 * Register multiple assets.
	 *
	 * @param Asset[] $assets Array of assets to register.
	 */
	public function register_many( array $assets ): void {
		foreach ( $assets as $asset ) {
			$this->register( $asset );
		}
	}

	/**
	 * Enqueue every asset registered under the same handle as the given asset.
	 *
	 * When a handle has both a script and a style, both are enqueued.
	 *
	 * @param Asset $asset Asset whose handle should be enqueued.
	 *
	 * @throws InvalidAsset If the passed-in asset's handle is not valid.
	 *
	 * @see AssetsHandlerInterface::register To register assets.
	 * @see AssetsHandlerInterface::register_many To register multiple assets.
	 */
	public function enqueue( Asset $asset ): void {
		$this->enqueue_handle( $asset->get_handle() );
	}

	/**
	 * Enqueue multiple assets.
	 *
	 * @param Asset[] $assets Array of assets to enqueue.
	 *
	 * @throws InvalidAsset If any of the passed-in assets are not valid.
	 *
	 * @see AssetsHandlerInterface::register To register assets.
	 * @see AssetsHandlerInterface::register_many To register multiple assets.
	 */
	public function enqueue_many( array $assets ): void {
		foreach ( $assets as $asset ) {
			$this->enqueue( $asset );
		}
	}

	/**
	 * Enqueue every asset registered under a given handle.
	 *
	 * When a handle has both a script and a style, both are enqueued.
	 *
	 * @param string $handle Handle of the assets to enqueue.
	 *
	 * @throws InvalidAsset If the handle is unknown to this handler.
	 */
	public function enqueue_handle( string $handle ): void {
		$this->validate_handle_exists( $handle );
		foreach ( $this->assets[ $handle ] as $asset ) {
			$asset->enqueue();
		}
	}

	/**
	 * Enqueue multiple assets based on their handles.
	 *
	 * @param string[] $handles Array of asset handles to enqueue.
	 *
	 * @throws InvalidAsset If any of the passed-in asset handles are not valid.
	 */
	public function enqueue_many_handles( array $handles ): void {
		foreach ( $handles as $handle ) {
			$this->enqueue_handle( $handle );
		}
	}

	/**
	 * Dequeue every asset registered under a given handle.
	 *
	 * When a handle has both a script and a style, both are dequeued.
	 *
	 * @param string $handle Handle of the assets to dequeue.
	 *
	 * @throws InvalidAsset If the handle is unknown to this handler.
	 */
	public function dequeue_handle( string $handle ): void {
		$this->validate_handle_exists( $handle );
		foreach ( $this->assets[ $handle ] as $asset ) {
			$asset->dequeue();
		}
	}

	/**
	 * Enqueue all assets known to this asset handler.
	 */
	public function enqueue_all(): void {
		foreach ( $this->assets as $handle_assets ) {
			foreach ( $handle_assets as $asset ) {
				$asset->enqueue();
			}
		}
	}

	/**
	 * Validate that a given asset handle is known to the object.
	 *
	 * @param string $handle The asset handle to validate.
	 *
	 * @throws InvalidAsset When the asset handle is unknown to the object.
	 */
	protected function validate_handle_exists( string $handle ): void {
		if ( empty( $this->assets[ $handle ] ) ) {
			throw InvalidAsset::invalid_handle( $handle );
		}
	}

	/**
	 * Whether an asset of the same class is already registered under its handle.
	 *
	 * @param Asset $asset
	 *
	 * @return bool
	 */
	private function is_asset_registered( Asset $asset ): bool {
		$handle = $asset->get_handle();
		if ( empty( $this->assets[ $handle ] ) ) {
			return false;
		}
		foreach ( $this->assets[ $handle ] as $existing ) {
			if ( get_class( $existing ) === get_class( $asset ) ) {
				return true;
			}
		}
		return false;
	}
}
