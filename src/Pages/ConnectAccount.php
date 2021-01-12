<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Pages;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsAwareness;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class ConnectAccount
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Pages
 */
class ConnectAccount implements Service, Registerable, Conditional {

	use AssetsAwareness, AdminConditional;

	/**
	 * ConnectAccount constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 */
	public function __construct( AssetsHandlerInterface $assets_handler ) {
		$this->assets_handler = $assets_handler;
	}

	/**
	 * Get the array of known assets.
	 *
	 * @return Asset[]
	 */
	protected function get_assets(): array {
		return [];
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_assets();
		$this->enqueue_assets();
	}

	/**
	 * Set up the array of assets.
	 */
	protected function setup_assets(): void {
		// TODO: Implement setup_assets() method.
	}
}
