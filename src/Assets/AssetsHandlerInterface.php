<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Assets;

use Automattic\WooCommerce\GoogleForWC\Exception\InvalidAsset;

/**
 * Interface AssetsHandlerInterface
 *
 * @package Automattic\WooCommerce\GoogleForWC\Assets
 */
interface AssetsHandlerInterface {

	/**
	 * Add a single asset to the asset handler.
	 *
	 * @param Asset $asset Asset to add.
	 */
	public function add( Asset $asset ): void;

	/**
	 * Enqueue a single asset based on its handle.
	 *
	 * @param string $handle Handle of the asset to enqueue.
	 *
	 * @throws InvalidAsset If the passed-in asset handle is not valid.
	 */
	public function enqueue_handle( string $handle ): void;

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
	public function enqueue(): void;
}
