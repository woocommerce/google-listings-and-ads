<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidProperty;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiReportQuery
 *
 * Runs a report query against the Merchant API accounts.reports.search resource.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
abstract class MapiReportQuery extends Query {

	/**
	 * Client which handles the query.
	 *
	 * @var MerchantApiClient
	 */
	protected $client = null;

	/**
	 * Merchant account ID.
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
	 * @param MerchantApiClient $client Client instance.
	 * @param int               $id     Account ID.
	 *
	 * @return QueryInterface
	 */
	public function set_client( MerchantApiClient $client, int $id ): QueryInterface {
		$this->client = $client;
		$this->id     = $id;

		return $this;
	}

	/**
	 * Perform the query and save it to the results.
	 *
	 * @throws InvalidProperty      If the client is not set.
	 * @throws MerchantApiException On a non-2xx response.
	 */
	protected function query_results() {
		if ( ! $this->client || ! $this->id ) {
			throw InvalidProperty::not_null( get_class( $this ), 'client' );
		}

		$body = [ 'query' => $this->build_query() ];

		if ( ! empty( $this->search_args['pageSize'] ) ) {
			$body['pageSize'] = $this->search_args['pageSize'];
		}

		if ( ! empty( $this->search_args['pageToken'] ) ) {
			$body['pageToken'] = $this->search_args['pageToken'];
		}

		$results = $this->client->post(
			sprintf( '%s/accounts/%d/reports:search', MapiPaths::REPORTS, $this->id ),
			$body
		);

		/**
		 * Filter the report search response prior to setting the property.
		 *
		 * @param array $results The report search response.
		 * @param array $body    The request body sent to the reports:search endpoint.
		 */
		$this->results = apply_filters( 'woocommerce_gla_mapi_report_query_response', $results, $body );
	}

	/**
	 * Add a where date between clause using the Merchant API date field for this query's view.
	 *
	 * @param string $after  Start of date range (YYYY-MM-DD).
	 * @param string $before End of date range (YYYY-MM-DD).
	 *
	 * @return QueryInterface
	 */
	public function where_date_between( string $after, string $before ): QueryInterface {
		return $this->where( "{$this->resource}.date", [ $after, $before ], 'BETWEEN' );
	}
}
