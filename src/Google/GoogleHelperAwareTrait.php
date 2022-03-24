<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit;

/**
 * Trait GoogleHelperAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google;
 */
trait GoogleHelperAwareTrait {

	/**
	 * The GoogleHelper object.
	 *
	 * @var google_helper
	 */
	protected $google_helper;

	/**
	 * @param GoogleHelper $google_helper
	 *
	 * @return void
	 */
	public function set_google_helper_object( GoogleHelper $google_helper ): void {
		$this->google_helper = $google_helper;
	}
}
