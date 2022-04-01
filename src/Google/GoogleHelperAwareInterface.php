<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit;

/**
 * Interface GoogleHelperAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google;
 */
interface GoogleHelperAwareInterface {

	/**
	 * @param GoogleHelper $google_helper
	 *
	 * @return void
	 */
	public function set_google_helper_object( GoogleHelper $google_helper ): void;
}
