<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\ApiCore\ApiException;
use Google\ApiCore\PagedListResponse;

/**
 * Trait AdsQueryTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait AdsQueryTrait {

	/**
	 * Build a Google Ads Query string.
	 *
	 * @param array  $fields   List of fields to return.
	 * @param string $resource Resource name to query from.
	 * @param string $where    Condition clause.
	 *
	 * @return string
	 */
	protected function build_query( array $fields, string $resource, string $where = '' ): string {
		$joined = join( ',', $fields );
		$query  = "SELECT {$joined} FROM {$resource}";

		if ( ! empty( $where ) ) {
			$query .= " WHERE {$where}";
		}

		return $query;
	}

	/**
	 * Run a Google Ads Query.
	 *
	 * @param GoogleAdsClient $client Google Ads client.
	 * @param int             $ads_id Ads ID account to run query against.
	 * @param string          $query Query to run.
	 *
	 * @return PagedListResponse
	 * @throws ApiException If the search call fails.
	 */
	protected function query( GoogleAdsClient $client, int $ads_id, string $query ): PagedListResponse {
		return $client->getGoogleAdsServiceClient()->search( $ads_id, $query );
	}
}
