<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ValueInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
interface ValueInterface {

	/**
	 * Get the value of the object.
	 *
	 * @return mixed
	 */
	public function get();
}
