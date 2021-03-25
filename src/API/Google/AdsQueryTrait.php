<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Exception;
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
	 * @param int $ads_id Ads ID account to run query against.
	 * @param string $query Query to run.
	 *
	 * @return PagedListResponse
	 * @throws ApiException
	 */
	protected function query( GoogleAdsClient $client, int $ads_id, string $query ): PagedListResponse {
		return $client->getGoogleAdsServiceClient()->search( $ads_id, $query );
	}

	/**
	 * Convert ID from a resource name to an int.
	 *
	 * @param string $name     Resource name containing ID number.
	 * @param string $resource Resource type.
	 *
	 * @return int
	 * @throws Exception When unable to parse resource ID.
	 */
	protected function parse_id( string $name, string $resource ): int {
		if ( ! preg_match( '/' . preg_quote( $resource, '/' ) . '/([0-9]+)/', $name, $matches ) || empty( $matches[1] ) ) {
			throw new Exception( __( 'Invalid resource ID', 'google-listings-and-ads' ) );
		}

		return absint( $matches[1] );
	}
}
