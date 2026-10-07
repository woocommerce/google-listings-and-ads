<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationService
 *
 * Lists the Business Profile locations the connected Google user can reach,
 * and stores the one location the merchant connects.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class LocationService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var int The largest page size `accounts.list` accepts. */
	private const ACCOUNTS_PAGE_SIZE = 20;

	/** @var int The largest page size `accounts.locations.list` accepts. */
	private const LOCATIONS_PAGE_SIZE = 100;

	/**
	 * Location fields to request. `accounts.locations.list` returns nothing without a read mask.
	 *
	 * @var string
	 */
	private const LOCATION_READ_MASK = 'name,title,storefrontAddress,metadata';

	/** @var string The account type Google gives the merchant's own account. */
	private const ACCOUNT_TYPE_PERSONAL = 'PERSONAL';

	/** @var BusinessProfileApiClient */
	private $client;

	/**
	 * LocationService constructor.
	 *
	 * @param BusinessProfileApiClient $client
	 */
	public function __construct( BusinessProfileApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * List every location the connected Google user can reach, each one once.
	 *
	 * When more than one account can reach a location, the list keeps the first
	 * account that returns it. The merchant's personal account goes first.
	 *
	 * @return array Each entry shaped `{ id, accountId, title, address, placeId, mapsUri }`.
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 */
	public function list_locations(): array {
		$locations = [];

		foreach ( $this->list_accounts() as $account ) {
			$account_id = self::get_resource_id( $account['name'] ?? '' );

			if ( '' === $account_id ) {
				continue;
			}

			foreach ( $this->list_account_locations( $account_id ) as $location ) {
				$locations[] = self::format_location( $location, $account_id );
			}
		}

		// Gather every account's locations before removing duplicates. Two accounts
		// read far apart in the loop can still return the same location.
		return self::remove_duplicate_locations( $locations );
	}

	/**
	 * Get the connected location.
	 *
	 * @return array|null Shaped like a {@see self::list_locations()} entry, or null when the merchant has not connected one.
	 */
	public function get_selected_location(): ?array {
		$data = $this->options->get( OptionsInterface::GOOGLE_BUSINESS_PROFILE, [] );

		if ( ! is_array( $data ) || empty( $data['location_id'] ) ) {
			return null;
		}

		return [
			'id'        => (string) $data['location_id'],
			'accountId' => (string) ( $data['account_id'] ?? '' ),
			'title'     => (string) ( $data['location_title'] ?? '' ),
			'address'   => (string) ( $data['location_address'] ?? '' ),
			'placeId'   => (string) ( $data['place_id'] ?? '' ),
			'mapsUri'   => (string) ( $data['maps_uri'] ?? '' ),
		];
	}

	/**
	 * Connect one location, storing it in place of anything stored before.
	 *
	 * The merchant connects one location at a time. To change it, the merchant
	 * disconnects first, which clears the stored location.
	 *
	 * @param string $location_id The location ID, as returned by {@see self::list_locations()}.
	 *
	 * @return array The connected location.
	 * @throws Exception When a location is already connected, the location isn't reachable, or saving fails.
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 */
	public function select_location( string $location_id ): array {
		if ( null !== $this->get_selected_location() ) {
			throw new Exception(
				__( 'A Google Business Profile location is already connected. Disconnect it before connecting another location.', 'google-listings-and-ads' ),
				409
			);
		}

		$location = $this->find_location( $location_id );

		if ( null === $location ) {
			throw new Exception(
				__( 'This Google Business Profile location is no longer available. Check again to refresh the list.', 'google-listings-and-ads' ),
				404
			);
		}

		$saved = $this->options->update(
			OptionsInterface::GOOGLE_BUSINESS_PROFILE,
			[
				'account_id'       => $location['accountId'],
				'location_id'      => $location['id'],
				'location_title'   => $location['title'],
				'location_address' => $location['address'],
				'place_id'         => $location['placeId'],
				'maps_uri'         => $location['mapsUri'],
			]
		);

		if ( ! $saved ) {
			throw new Exception( __( 'Unable to save the Google Business Profile location.', 'google-listings-and-ads' ), 500 );
		}

		return $location;
	}

	/**
	 * Find a location the connected Google user can reach.
	 *
	 * @param string $location_id The location ID.
	 *
	 * @return array|null
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 */
	private function find_location( string $location_id ): ?array {
		if ( '' === $location_id ) {
			return null;
		}

		foreach ( $this->list_locations() as $location ) {
			if ( $location_id === $location['id'] ) {
				return $location;
			}
		}

		return null;
	}

	/**
	 * List the connected Google user's accounts, personal account first.
	 *
	 * @return array Raw `Account` resources.
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 */
	private function list_accounts(): array {
		$accounts = $this->get_all_pages(
			'accounts',
			[ 'pageSize' => self::ACCOUNTS_PAGE_SIZE ],
			'accounts'
		);

		$personal = [];
		$other    = [];

		foreach ( $accounts as $account ) {
			if ( self::ACCOUNT_TYPE_PERSONAL === ( $account['type'] ?? '' ) ) {
				$personal[] = $account;
			} else {
				$other[] = $account;
			}
		}

		return array_merge( $personal, $other );
	}

	/**
	 * List one account's locations.
	 *
	 * @param string $account_id The Business Profile account ID.
	 *
	 * @return array Raw `Location` resources.
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 */
	private function list_account_locations( string $account_id ): array {
		return $this->get_all_pages(
			"accounts/{$account_id}/locations",
			[
				'pageSize' => self::LOCATIONS_PAGE_SIZE,
				'readMask' => self::LOCATION_READ_MASK,
			],
			'locations'
		);
	}

	/**
	 * Follow `nextPageToken` until the last page and return every page's items.
	 *
	 * @param string $path      Resource path.
	 * @param array  $query     Query parameters sent with every page.
	 * @param string $items_key Response key holding the page's items.
	 *
	 * @return array
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 */
	private function get_all_pages( string $path, array $query, string $items_key ): array {
		$items      = [];
		$page_token = '';

		do {
			$response = $this->client->get( $path, array_merge( $query, [ 'pageToken' => $page_token ] ) );
			$page     = $response[ $items_key ] ?? [];

			if ( is_array( $page ) ) {
				$items = array_merge( $items, array_filter( $page, 'is_array' ) );
			}

			$page_token = (string) ( $response['nextPageToken'] ?? '' );
		} while ( '' !== $page_token );

		return $items;
	}

	/**
	 * Keep the first of each location, matching on place ID.
	 *
	 * A location without a place ID matches on its own ID instead. This keeps
	 * locations that all lack a place ID from merging into one.
	 *
	 * @param array $locations Formatted locations.
	 *
	 * @return array
	 */
	private static function remove_duplicate_locations( array $locations ): array {
		$unique = [];

		foreach ( $locations as $location ) {
			$key = '' !== $location['placeId'] ? "place:{$location['placeId']}" : "location:{$location['id']}";

			if ( ! isset( $unique[ $key ] ) ) {
				$unique[ $key ] = $location;
			}
		}

		return array_values( $unique );
	}

	/**
	 * Map a Business Profile `Location` resource onto the shape the account card expects.
	 *
	 * @param array  $location   Raw `Location` resource.
	 * @param string $account_id The account that returned the location.
	 *
	 * @return array Shaped `{ id, accountId, title, address, placeId, mapsUri }`.
	 */
	private static function format_location( array $location, string $account_id ): array {
		return [
			'id'        => self::get_resource_id( $location['name'] ?? '' ),
			'accountId' => $account_id,
			'title'     => (string) ( $location['title'] ?? '' ),
			'address'   => self::format_address( $location['storefrontAddress'] ?? [] ),
			'placeId'   => (string) ( $location['metadata']['placeId'] ?? '' ),
			'mapsUri'   => (string) ( $location['metadata']['mapsUri'] ?? '' ),
		];
	}

	/**
	 * Join a `PostalAddress` into one line, for example `123 Market St, San Francisco, CA, 94103`.
	 *
	 * @param mixed $address Raw `PostalAddress`. Locations that serve an area have none.
	 *
	 * @return string
	 */
	private static function format_address( $address ): string {
		if ( ! is_array( $address ) ) {
			return '';
		}

		$lines = is_array( $address['addressLines'] ?? null ) ? $address['addressLines'] : [];
		$parts = array_merge(
			$lines,
			[
				$address['locality'] ?? '',
				$address['administrativeArea'] ?? '',
				$address['postalCode'] ?? '',
			]
		);

		$parts = array_filter(
			array_map( 'strval', array_filter( $parts, 'is_scalar' ) ),
			static function ( string $part ) {
				return '' !== trim( $part );
			}
		);

		return implode( ', ', $parts );
	}

	/**
	 * Get the ID at the end of a resource name, for example `1111` from `locations/1111`.
	 *
	 * @param mixed $name Resource name.
	 *
	 * @return string
	 */
	private static function get_resource_id( $name ): string {
		if ( ! is_string( $name ) || '' === $name ) {
			return '';
		}

		$parts = explode( '/', $name );

		return (string) end( $parts );
	}
}
