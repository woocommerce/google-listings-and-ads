<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\EachPromise;

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
	 * Fetch a single product by its Google product ID.
	 *
	 * @param string $google_product_id
	 *
	 * @return Product
	 * @throws MerchantApiException On non-2xx response.
	 */
	public function get( string $google_product_id ): Product {
		$body = $this->client->get( $this->build_path( $google_product_id ) );

		return Product::from_array( $body );
	}

	/**
	 * Fetch multiple products in parallel
	 *
	 * @param string[] $google_product_ids
	 * @param int      $concurrency
	 *
	 * @return array<string, Product>
	 */
	public function get_many( array $google_product_ids, int $concurrency = 10 ): array {
		$client   = $this->client;
		$path_for = function ( string $id ): string {
			return $this->build_path( $id );
		};

		$promises = function () use ( $google_product_ids, $client, $path_for ) {
			foreach ( $google_product_ids as $id ) {
				yield $id => $client->get_async( $path_for( $id ) );
			}
		};

		$results = [];
		( new EachPromise(
			$promises(),
			[
				'concurrency' => $concurrency,
				'fulfilled'   => function ( array $body, string $id ) use ( &$results ) {
					$results[ $id ] = Product::from_array( $body );
				},
				'rejected'    => function ( $reason ) {
					if ( ! $reason instanceof MerchantApiException ) {
						do_action( 'woocommerce_gla_exception', $reason, __METHOD__ );
					}
					// MerchantApiException already fires woocommerce_gla_mc_client_exception.
				},
			]
		) )->promise()->wait();

		return $results;
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
		$page_token = '';

		do {
			$body = $this->client->get( $this->build_list_path( $page_size, $page_token ) );

			foreach ( $body['products'] ?? [] as $product ) {
				yield Product::from_array( $product );
			}

			$page_token = $body['nextPageToken'] ?? '';
		} while ( '' !== $page_token );
	}

	/**
	 * Build the resource path for a product.
	 *
	 * @param string $google_product_id
	 *
	 * @return string
	 */
	protected function build_path( string $google_product_id ): string {
		return sprintf(
			'%s/accounts/%s/products/%s',
			MapiPaths::PRODUCTS,
			$this->options->get_merchant_id(),
			$google_product_id
		);
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
