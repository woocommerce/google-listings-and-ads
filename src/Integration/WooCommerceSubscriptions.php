<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommerceSubscriptions
 *
 * @since 1.2.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WooCommerceSubscriptions implements IntegrationInterface {

	protected const VALUE_KEY = 'woocommerce_subscriptions';

	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WCS_INIT_TIMESTAMP' );
	}

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void {
		// No required adjustments yet.
	}
}
