<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ViewFactory
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
interface ViewFactory {

	/**
	 * Create a new view object.
	 *
	 * @param string $path Path to the view file to render.
	 *
	 * @return View Instantiated view object.
	 */
	public function create( string $path ): View;
}
