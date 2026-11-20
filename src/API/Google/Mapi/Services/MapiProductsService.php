<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiProductsService
 *
 * Resource service for the Merchant API products endpoint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiProductsService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiProductsService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Yield all products for the account, following pagination.
	 *
	 * Returns a generator so callers can stream the catalog without holding every
	 * product in memory at once.
	 *
	 * @param int $page_size Maximum products to request per page.
	 *
	 * @return iterable<Product>
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function list( int $page_size = 250 ): iterable {
		$page_token = null;

		do {
			$page = $this->list_page( $page_token, $page_size );

			foreach ( $page['products'] as $product ) {
				yield $product;
			}

			$page_token = $page['next_page_token'];
		} while ( null !== $page_token );
	}

	/**
	 * Fetch a single page of the account's products.
	 *
	 * Page-wise counterpart of {@see list()} for callers that carry the pagination
	 * state themselves across separate PHP requests (e.g. a batched job storing the
	 * token between scheduled actions).
	 *
	 * @param string|null $page_token Token of the page to fetch; null for the first page.
	 * @param int         $page_size  Maximum products per page; clamped to the API range 1-1000.
	 *
	 * @return array{products: Product[], next_page_token: ?string}
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function list_page( ?string $page_token = null, int $page_size = 1000 ): array {
		$page_size = min( 1000, max( 1, $page_size ) );

		$body = $this->client->get( $this->build_list_path( $page_size, $page_token ?? '' ) );

		$products = [];
		foreach ( $body['products'] ?? [] as $product ) {
			$products[] = Product::from_array( $product );
		}

		$next_token = $body['nextPageToken'] ?? null;

		return [
			'products'        => $products,
			'next_page_token' => is_string( $next_token ) && '' !== $next_token ? $next_token : null,
		];
	}

	/**
	 * Build the resource path for listing products.
	 *
	 * @param int    $page_size
	 * @param string $page_token
	 *
	 * @return string
	 */
	protected function build_list_path( int $page_size, string $page_token ): string {
		$path = sprintf(
			'%s/accounts/%s/products?pageSize=%d',
			MapiPaths::PRODUCTS,
			$this->options->get_merchant_id(),
			$page_size
		);

		if ( '' !== $page_token ) {
			$path .= '&pageToken=' . rawurlencode( $page_token );
		}

		return $path;
	}
}
