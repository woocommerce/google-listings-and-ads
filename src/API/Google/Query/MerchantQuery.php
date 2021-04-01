<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Exception;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_SearchRequest as SearchRequest;
use Google_Service_ShoppingContent_SearchResponse as SearchResponse;
use Google\Exception as GoogleException;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
abstract class MerchantQuery extends Query {

	/**
	 * Client which handles the query.
	 *
	 * @var ShoppingService
	 */
	protected $client = null;

	/**
	 * Merchant Account ID.
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Arguments to add to the search query.
	 *
	 * @var array
	 */
	protected $search_args = [];

	/**
	 * Set the client which will handle the query.
	 *
	 * @param ShoppingService $client Client instance.
	 * @param int             $id     Account ID.
	 *
	 * @return QueryInterface
	 */
	public function set_client( ShoppingService $client, int $id ) {
		$this->client = $client;
		$this->id     = $id;

		return $this;
	}

	/**
	 * Perform the query and save it to the results.
	 *
	 * @throws GoogleException If the search call fails.
	 * @throws Exception If the client is not set.
	 */
	protected function query_results() {
		if ( ! $this->client || ! $this->id ) {
			throw new Exception( __( 'Client must be set to query results.', 'google-listings-and-ads' ) );
		}

		$request = new SearchRequest();
		$request->setQuery( $this->build_query() );

		if ( ! empty( $this->search_args['pageSize'] ) ) {
			$request->setPageSize( $this->search_args['pageSize'] );
		}

		if ( ! empty( $this->search_args['pageToken'] ) ) {
			$request->setPageToken( $this->search_args['pageToken'] );
		}

		/** @var SearchResponse $this->results */
		$this->results = $this->client->reports->search( $this->id, $request );
	}
}
