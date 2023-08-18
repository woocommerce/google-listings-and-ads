<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidProperty;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\V14\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;
use Google\ApiCore\PagedListResponse;


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

		/** @var PagedListResponse $this->results */
		$this->results = $this->client->getGoogleAdsServiceClient()->search(
			$this->id,
			$this->build_query(),
			$this->search_args
		);
	}
}
