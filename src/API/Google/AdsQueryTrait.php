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
	 *
	 * @return PagedListResponse
	 * @throws ApiException If the search call fails.
	 */
	protected function query( string $query ): PagedListResponse {
		return $this->client->getGoogleAdsServiceClient()->search( $this->options->get_ads_id(), $query );
	}
}
