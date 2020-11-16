<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class BaseEndpoint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site
 */
abstract class BaseEndpoint implements Service, Registerable {
	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function() {
				$this->register_routes();
			}
		);
	}

	/**
	 * Register rest routes with WordPress.
	 */
	abstract protected function register_routes(): void;
}
