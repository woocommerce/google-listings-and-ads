<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesService
 *
 * Builds the list of top organic product landing pages, ranked by clicks, with each
 * Search Console page URL resolved to its WooCommerce product.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class TopProductPagesService extends AbstractTopPagesService {

	/** @var int Default number of products returned. */
	public const DEFAULT_LIMIT = 100;

	/** @var PageProductResolver */
	protected $resolver;

	/** @var ProductHelper */
	protected $product_helper;

	/**
	 * TopProductPagesService constructor.
	 *
	 * @param SearchAnalyticsService $search_analytics
	 * @param PageProductResolver    $resolver
	 * @param ProductHelper          $product_helper
	 * @param TransientsInterface    $transients
	 */
	public function __construct( SearchAnalyticsService $search_analytics, PageProductResolver $resolver, ProductHelper $product_helper, TransientsInterface $transients ) {
		parent::__construct( $search_analytics, $transients );

		$this->resolver       = $resolver;
		$this->product_helper = $product_helper;
	}

	/**
	 * Get the top products of a property by clicks for a date range.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 * @param int|null          $limit    Maximum number of products, kept between 1 and the API's maximum. Defaults to {@see self::get_top_product_pages_limit()}.
	 *
	 * @return array[] Up to the limit of rows, each with `product_id`, `name`, `clicks`,
	 *                 `impressions`, `ctr` and `position`, sorted by clicks descending.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function get_top_product_pages( string $property, DateTimeInterface $after, DateTimeInterface $before, ?int $limit = null ): array {
		$limit = null === $limit ? $this->get_top_product_pages_limit() : SearchAnalyticsService::clamp_limit( $limit );

		return $this->get_top_pages( $property, $after, $before, $limit );
	}

	/**
	 * Get the transient the report's results are cached in.
	 *
	 * @return string
	 */
	protected function get_cache_name(): string {
		return TransientsInterface::TOP_PRODUCT_PAGES;
	}

	/**
	 * Group the pages of a product, including those of its variations, under the product's ID.
	 *
	 * @param string $url
	 *
	 * @return string|null The product ID, or null when the URL isn't a product on this site.
	 */
	protected function get_page_key( string $url ): ?string {
		$product_id = $this->resolver->resolve_product_id( $url );

		return null === $product_id ? null : (string) $product_id;
	}

	/**
	 * Identify a product by its ID and name.
	 *
	 * @param string $key Product ID.
	 * @param string $url
	 *
	 * @return array
	 */
	protected function describe_page( string $key, string $url ): array {
		$product_id = (int) $key;

		return [
			'product_id' => $product_id,
			'name'       => html_entity_decode( $this->product_helper->get_wc_product( $product_id )->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		];
	}

	/**
	 * Get the number of top products to return.
	 *
	 * The default is a hard limit that protects performance; a developer can raise it with the
	 * `woocommerce_gla_search_console_top_product_pages_limit` filter.
	 *
	 * Raising the limit increases how many Search Console URLs are resolved to a product per report load, up to
	 * `limit * 3` (see {@see AbstractTopPagesService::OVER_FETCH_MULTIPLIER}), so a very high value should be
	 * a deliberate choice rather than a default.
	 *
	 * @return int Between 1 and the API's maximum row limit, or the default when the filtered value isn't a number.
	 */
	public function get_top_product_pages_limit(): int {
		/**
		 * Filters the maximum number of top product pages returned from Search Console.
		 *
		 * @param int $limit Maximum number of products. Defaults to 100.
		 */
		$limit = apply_filters( 'woocommerce_gla_search_console_top_product_pages_limit', self::DEFAULT_LIMIT );

		return SearchAnalyticsService::validate_filtered_limit( $limit, self::DEFAULT_LIMIT );
	}
}
