<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Interface IntegrationInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
interface IntegrationInterface {
	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool;

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void;
}
