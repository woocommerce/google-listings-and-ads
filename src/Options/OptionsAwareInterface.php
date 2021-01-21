<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Interface OptionsAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
interface OptionsAwareInterface {

	/**
	 * Set the Options object.
	 *
	 * @param OptionsInterface $options
	 *
	 * @return void
	 */
	public function set_options_object( OptionsInterface $options ): void;
}
