<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Interface TransientsAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
interface TransientsAwareInterface {

	/**
	 * Set the Transients object.
	 *
	 * @param TransientsInterface $transients
	 *
	 * @return void
	 */
	public function set_transients_object( TransientsInterface $transients ): void;
}
