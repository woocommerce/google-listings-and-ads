<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use Exception;

/**
 * Trait DoExceptionAction
 *
 * @since   x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
trait DoExceptionAction {

	/**
	 * Handle a Google Listings and Ads exception with an action.
	 *
	 * This allows for consistency in handling these exceptions with an action across the
	 * entire plugin.
	 *
	 * @since x.x.x
	 *
	 * @param Exception $e
	 * @param string    $method
	 *
	 * @return void
	 */
	protected function handle_exception( Exception $e, string $method ): void {
		do_action( 'woocommerce_gla_exception', $e, $method );
	}
}
