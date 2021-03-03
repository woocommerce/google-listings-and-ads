<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\View;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\View;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Class PHPViewFactory
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\View
 */
final class PHPViewFactory implements Service, ViewFactory {

	/**
	 * Create a new view object for a given relative path.
	 *
	 * @param string $relative_path Relative path to create the view for.
	 *
	 * @return View Instantiated view object.
	 *
	 * @throws ViewException If an invalid path was passed into the View.
	 */
	public function create( string $relative_path ): View {
		return new PHPView( $relative_path, $this );
	}
}
