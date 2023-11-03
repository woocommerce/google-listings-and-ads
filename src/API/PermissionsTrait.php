<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API;

defined( 'ABSPATH' ) || exit;

/**
 * Trait PermissionsTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API
 */
trait PermissionsTrait {

	/**
	 * Check whether the current user can manage woocommerce.
	 *
	 * @return bool
	 */
	protected function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' );
	}
}
