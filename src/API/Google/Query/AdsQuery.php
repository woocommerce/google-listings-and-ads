<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidProperty;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\V20\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V20\Services\SearchGoogleAdsRequest;
use Google\Ads\GoogleAds\V20\Services\SearchSettings;
use Google\ApiCore\ApiException;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
abstract class AdsQuery extends Query {

	/**
	 * Client which handles the query.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client = null;

	/**
	 * Ads Account ID.
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Arguments to add to the search query.
	 *
	 * Note: While we allow pageSize to be set, we do not pass it to the API.
	 * pageSize has been deprecated in the API since V17 and is fixed to 10000 rows.
	 *
	 * @var array
	 */
	protected $search_args = [];

	/**
	 * Set the client which will handle the query.
	 *
	 * @param GoogleAdsClient $client Client instance.
	 * @param int             $id     Account ID.
	 *
	 * @return QueryInterface
	 * @throws InvalidProperty If the ID is empty.
	 */
	public function set_client( GoogleAdsClient $client, int $id ): QueryInterface {
		if ( empty( $id ) ) {
			throw InvalidProperty::not_null( get_class( $this ), 'id' );
		}

		$this->client = $client;
		$this->id     = $id;

		return $this;
	}

	/**
	 * Get the first row from the results.
	 *
	 * @return GoogleAdsRow
	 * @throws ApiException When no results returned or an error occurs.
	 */
	public function get_result(): GoogleAdsRow {
		$results = $this->get_results();

		if ( $results ) {
			foreach ( $results->iterateAllElements() as $row ) {
				return $row;
			}
		}

		throw new ApiException( __( 'No result from query', 'google-listings-and-ads' ), 404, '' );
	}

	/**
	 * Perform the query and save it to the results.
	 *
	 * @throws ApiException If the search call fails.
	 * @throws InvalidProperty If the client is not set.
	 */
	protected function query_results() {
		if ( ! $this->client || ! $this->id ) {
			throw InvalidProperty::not_null( get_class( $this ), 'client' );
		}

		$request = new SearchGoogleAdsRequest();

		if ( ! empty( $this->search_args['pageToken'] ) ) {
			$request->setPageToken( $this->search_args['pageToken'] );
		}

		// Allow us to get the total number of results.
		$request->setSearchSettings(
			new SearchSettings(
				[
					'return_total_results_count' => true,
				]
			)
		);

		$request->setQuery( $this->build_query() );
		$request->setCustomerId( $this->id );

		$this->results = $this->client->getGoogleAdsServiceClient()->search( $request );
	}
}
