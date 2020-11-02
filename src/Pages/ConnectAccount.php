<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Pages;

use Automattic\WooCommerce\GoogleForWC\Assets\AdminScriptAsset;
use Automattic\WooCommerce\GoogleForWC\Assets\Asset;
use Automattic\WooCommerce\GoogleForWC\Assets\AssetsAwareness;
use Automattic\WooCommerce\GoogleForWC\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleForWC\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleForWC\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleForWC\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleForWC\Infrastructure\Service;

/**
 * Class ConnectAccount
 *
 * @package Automattic\WooCommerce\GoogleForWC\Pages
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
		return [
			new AdminScriptAsset( 'gfw-connect-account-page', 'js/build/index' ),
		];
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_assets();
		$this->enqueue_assets();
	}
}
