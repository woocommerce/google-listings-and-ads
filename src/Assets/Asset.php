<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;

/**
 * Asset interface.
 *
 * An asset is something that can be enqueued by WordPress.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
interface Asset extends Registerable {

	/**
	 * Enqueue the asset within WordPress.
	 */
	public function enqueue(): void;

	/**
	 * Dequeue the asset within WordPress.
	 */
	public function dequeue(): void;

	/**
	 * Get the handle of the asset. The handle serves as the ID within WordPress.
	 *
	 * @return string
	 */
	public function get_handle(): string;
}
