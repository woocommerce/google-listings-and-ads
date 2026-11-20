<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class PageProductResolver
 *
 * Resolves a Search Console page URL to the WooCommerce product it belongs to.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class PageProductResolver {

	/** @var PagePostResolver */
	protected $post_resolver;

	/** @var ProductHelper */
	protected $product_helper;

	/**
	 * PageProductResolver constructor.
	 *
	 * @param PagePostResolver $post_resolver
	 * @param ProductHelper    $product_helper
	 */
	public function __construct( PagePostResolver $post_resolver, ProductHelper $product_helper ) {
		$this->post_resolver  = $post_resolver;
		$this->product_helper = $product_helper;
	}

	/**
	 * Resolve a page URL to a product ID.
	 *
	 * A URL that belongs to a variation resolves to the parent product. A URL whose
	 * slug has since changed still resolves to the product that used to have it.
	 *
	 * @param string $url Absolute page URL as reported by Search Console.
	 *
	 * @return int|null The product ID, or null when the URL isn't a product on this site.
	 */
	public function resolve_product_id( string $url ): ?int {
		$post_id = $this->post_resolver->resolve_post_id( $url, [ 'product' ] );

		if ( 0 === $post_id ) {
			return null;
		}

		try {
			return $this->product_helper->maybe_swap_for_parent_id( $post_id );
		} catch ( InvalidValue $e ) {
			return null;
		}
	}
}
