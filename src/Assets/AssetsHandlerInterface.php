<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;

/**
 * Interface AssetsHandlerInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
interface AssetsHandlerInterface {

	/**
	 * Register a single asset.
	 *
	 * @param Asset $asset Asset to register.
	 */
	public function register( Asset $asset ): void;

	/**
	 * Register multiple assets.
	 *
	 * @param Asset[] $assets Array of assets to register.
	 */
	public function register_many( array $assets ): void;

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
	public function enqueue( Asset $asset ): void;

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
	public function enqueue_many( array $assets ): void;

	/**
	 * Enqueue a single asset based on its handle.
	 *
	 * @param string $handle Handle of the asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset handle is not valid.
	 */
	public function enqueue_handle( string $handle ): void;

	/**
	 * Enqueue multiple assets based on their handles.
	 *
	 * @param string[] $handles Array of asset handles to enqueue.
	 *
	 * @throws InvalidAsset If any of the passed-in asset handles are not valid.
	 */
	public function enqueue_many_handles( array $handles ): void;

	/**
	 * Dequeue a single asset based on its handle.
	 *
	 * @param string $handle Handle of the asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset handle is not valid.
	 */
	public function dequeue_handle( string $handle ): void;

	/**
	 * Enqueue all assets known to this asset handler.
	 */
	public function enqueue_all(): void;
}
