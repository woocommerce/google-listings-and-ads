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
	 * Assets known to this asset handler.
	 *
	 * @var Asset[]
	 */
	private $assets = [];

	/**
	 * Register a single asset.
	 *
	 * @param Asset $asset Asset to register.
	 */
	public function register( Asset $asset ): void {
		$this->validate_handle_not_exists( $asset->get_handle() );
		$this->assets[ $asset->get_handle() ] = $asset;
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
	 * Enqueue a single asset.
	 *
	 * @param Asset $asset Asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset is not valid.
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
	 * Enqueue a single asset based on its handle.
	 *
	 * @param string $handle Handle of the asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset handle is not valid.
	 */
	public function enqueue_handle( string $handle ): void {
		$this->validate_handle_exists( $handle );
		$this->assets[ $handle ]->enqueue();
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
	 * Dequeue a single asset based on its handle.
	 *
	 * @param string $handle Handle of the asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset handle is not valid.
	 */
	public function dequeue_handle( string $handle ): void {
		$this->validate_handle_exists( $handle );
		$this->assets[ $handle ]->dequeue();
	}

	/**
	 * Enqueue all assets known to this asset handler.
	 */
	public function enqueue_all(): void {
		foreach ( $this->assets as $asset_object ) {
			$asset_object->enqueue();
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
		if ( ! array_key_exists( $handle, $this->assets ) ) {
			throw InvalidAsset::invalid_handle( $handle );
		}
	}

	/**
	 * Validate that a given asset handle does not already exist.
	 *
	 * @param string $handle
	 *
	 * @throws InvalidAsset When the handle exists.
	 */
	protected function validate_handle_not_exists( string $handle ): void {
		if ( array_key_exists( $handle, $this->assets ) ) {
			throw InvalidAsset::handle_exists( $handle );
		}
	}
}
