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
	 * Create a new view object for a given relative path.
	 *
	 * @param string $relative_path Relative path to create the view for.
	 *
	 * @return View Instantiated view object.
	 */
	public function create( string $relative_path ): View;
}
