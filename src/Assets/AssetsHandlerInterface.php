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
	 * Add a single asset to the asset handler.
	 *
	 * @param Asset $asset Asset to add.
	 */
	public function add( Asset $asset ): void;

	/**
	 * Add multiple assets to the asset handler.
	 *
	 * @param Asset[] $assets Array of assets to add.
	 */
	public function add_many( array $assets ): void;

	/**
	 * Enqueue a single asset.
	 *
	 * @param Asset $asset Asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset is not valid.
	 *
	 * @see AssetsHandlerInterface::add To add assets.
	 * @see AssetsHandlerInterface::add_many To add multiple assets.
	 */
	public function enqueue( Asset $asset ): void;

	/**
	 * Enqueue multiple assets.
	 *
	 * @param Asset[] $assets Array of assets to enqueue.
	 *
	 * @throws InvalidAsset If any of the passed-in assets are not valid.
	 *
	 * @see AssetsHandlerInterface::add To add assets.
	 * @see AssetsHandlerInterface::add_many To add multiple assets.
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
