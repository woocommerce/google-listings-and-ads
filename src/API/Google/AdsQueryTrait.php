<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\ApiCore\ApiException;
use Google\ApiCore\PagedListResponse;

/**
 * Trait AdsQueryTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait AdsQueryTrait {

	/**
	 * Run a Google Ads Query.
	 *
	 * @param string $query Query to run.
	 * @param array  $args  Arguments to send to the query.
	 *
	 * @return PagedListResponse
	 * @throws ApiException If the search call fails.
	 */
	protected function query( string $query, array $args = [] ): PagedListResponse {
		return $this->client->getGoogleAdsServiceClient()->search( $this->options->get_ads_id(), $query, $args );
	}
}
