<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

defined( 'ABSPATH' ) || exit;

/**
 * Interface Renderable
 *
 * Used to designate an object that can be rendered (e.g. views, blocks, shortcodes, etc.).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
interface Renderable {

	/**
	 * Render the renderable.
	 *
	 * @param array $context Optional. Contextual information to use while
	 *                       rendering. Defaults to an empty array.
	 *
	 * @return string Rendered result.
	 */
	public function render( array $context = [] ): string;
}
