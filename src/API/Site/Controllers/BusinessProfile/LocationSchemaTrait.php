<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile;

defined( 'ABSPATH' ) || exit;

/**
 * Trait LocationSchemaTrait
 *
 * Schema properties for one Business Profile location, in the shape
 * LocationService returns it.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile
 */
trait LocationSchemaTrait {

	/**
	 * Get the schema properties for one location.
	 *
	 * @return array
	 */
	protected function get_location_schema_properties(): array {
		return [
			'id'        => [
				'type'        => 'string',
				'description' => __( 'Location ID.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'accountId' => [
				'type'        => 'string',
				'description' => __( 'ID of the Google Business Profile account the location is listed under.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'title'     => [
				'type'        => 'string',
				'description' => __( 'Location name.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'address'   => [
				'type'        => 'string',
				'description' => __( 'Location address on one line. Empty for a location without a storefront address.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'placeId'   => [
				'type'        => 'string',
				'description' => __( 'Google Maps place ID. Empty when Google has none for the location.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'mapsUri'   => [
				'type'        => 'string',
				'description' => __( 'Link to the location on Google Maps.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
		];
	}
}
