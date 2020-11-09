<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Registerable interface.
 *
 * Used to designate an object that can be registered.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
interface Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void;
}
