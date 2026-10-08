<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class LocationServiceTest extends UnitTest {

	protected const READ_MASK = 'name,title,storefrontAddress,metadata';

	/** @var MockObject|BusinessProfileApiClient $client */
	protected $client;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var LocationService $service */
	protected $service;

	/**
	 * Responses keyed by `{path}|{pageToken}`.
	 *
	 * @var array
	 */
	protected $responses = [];

	/**
	 * Every request the mocked client received, as `[ path, query ]`.
	 *
	 * @var array
	 */
	protected $requests = [];

	public function setUp(): void {
		parent::setUp();

		$this->responses = [];
		$this->requests  = [];

		$this->client = $this->createMock( BusinessProfileApiClient::class );
		$this->client->method( 'get' )->willReturnCallback(
			function ( string $path, array $query = [] ) {
				$this->requests[] = [ $path, $query ];

				return $this->responses[ $path . '|' . ( $query['pageToken'] ?? '' ) ] ?? [];
			}
		);

		$this->options = $this->createMock( OptionsInterface::class );

		$this->service = new LocationService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_list_locations_follows_every_accounts_page() {
		$this->given_page(
			'accounts',
			'',
			[
				'accounts'      => [ self::account( '1' ) ],
				'nextPageToken' => 'a2',
			]
		);
		$this->given_page(
			'accounts',
			'a2',
			[
				'accounts'      => [ self::account( '2' ) ],
				'nextPageToken' => 'a3',
			]
		);
		$this->given_page( 'accounts', 'a3', [ 'accounts' => [ self::account( '3' ) ] ] );
		$this->given_page( 'accounts/1/locations', '', [ 'locations' => [ self::location( '11', 'place-11' ) ] ] );
		$this->given_page( 'accounts/2/locations', '', [ 'locations' => [ self::location( '22', 'place-22' ) ] ] );
		$this->given_page( 'accounts/3/locations', '', [ 'locations' => [ self::location( '33', 'place-33' ) ] ] );

		$locations = $this->service->list_locations();

		$this->assertSame( [ '11', '22', '33' ], array_column( $locations, 'id' ) );

		$account_requests = $this->requests_for( 'accounts' );
		$this->assertCount( 3, $account_requests );
		foreach ( $account_requests as $query ) {
			$this->assertSame( 20, $query['pageSize'] );
		}
	}

	public function test_list_locations_follows_every_locations_page_with_read_mask() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ self::account( '1' ), self::account( '2' ) ] ] );
		$this->given_page(
			'accounts/1/locations',
			'',
			[
				'locations'     => [ self::location( '11', 'place-11' ) ],
				'nextPageToken' => 'l2',
			]
		);
		$this->given_page( 'accounts/1/locations', 'l2', [ 'locations' => [ self::location( '12', 'place-12' ) ] ] );
		$this->given_page(
			'accounts/2/locations',
			'',
			[
				'locations'     => [ self::location( '21', 'place-21' ) ],
				'nextPageToken' => 'l2',
			]
		);
		$this->given_page( 'accounts/2/locations', 'l2', [ 'locations' => [ self::location( '22', 'place-22' ) ] ] );

		$locations = $this->service->list_locations();

		$this->assertSame( [ '11', '12', '21', '22' ], array_column( $locations, 'id' ) );

		$location_requests = array_merge(
			$this->requests_for( 'accounts/1/locations' ),
			$this->requests_for( 'accounts/2/locations' )
		);
		$this->assertCount( 4, $location_requests );
		foreach ( $location_requests as $query ) {
			$this->assertSame( 100, $query['pageSize'] );
			$this->assertSame( self::READ_MASK, $query['readMask'] );
		}
	}

	public function test_list_locations_lists_a_location_reachable_through_two_accounts_once() {
		$this->given_page(
			'accounts',
			'',
			[ 'accounts' => [ self::account( '1', 'PERSONAL' ), self::account( '2', 'ORGANIZATION' ) ] ]
		);
		// Both accounts return the shared location, on different pages. The
		// collision only shows up once the service has every account's list.
		$this->given_page(
			'accounts/1/locations',
			'',
			[
				'locations'     => [ self::location( '100', 'place-shared' ) ],
				'nextPageToken' => 'l2',
			]
		);
		$this->given_page( 'accounts/1/locations', 'l2', [ 'locations' => [ self::location( '101', 'place-own' ) ] ] );
		$this->given_page(
			'accounts/2/locations',
			'',
			[ 'locations' => [ self::location( '200', 'place-other' ), self::location( '100', 'place-shared' ) ] ]
		);

		$locations = $this->service->list_locations();

		$this->assertSame( [ 'place-shared', 'place-own', 'place-other' ], array_column( $locations, 'placeId' ) );
		$this->assertSame( '1', $locations[0]['accountId'] );
	}

	public function test_list_locations_matches_on_place_id_even_when_location_ids_differ() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ self::account( '1' ), self::account( '2' ) ] ] );
		$this->given_page( 'accounts/1/locations', '', [ 'locations' => [ self::location( '100', 'place-shared' ) ] ] );
		$this->given_page( 'accounts/2/locations', '', [ 'locations' => [ self::location( '999', 'place-shared' ) ] ] );

		$locations = $this->service->list_locations();

		$this->assertCount( 1, $locations );
		$this->assertSame( '100', $locations[0]['id'] );
	}

	public function test_list_locations_keeps_locations_without_a_place_id_apart() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ self::account( '1' ), self::account( '2' ) ] ] );
		$this->given_page(
			'accounts/1/locations',
			'',
			[ 'locations' => [ self::location( '100', '' ), self::location( '101', '' ) ] ]
		);
		$this->given_page( 'accounts/2/locations', '', [ 'locations' => [ self::location( '100', '' ) ] ] );

		$locations = $this->service->list_locations();

		$this->assertSame( [ '100', '101' ], array_column( $locations, 'id' ) );
	}

	public function test_list_locations_reads_the_personal_account_first() {
		$this->given_page(
			'accounts',
			'',
			[
				'accounts' => [
					self::account( '1', 'ORGANIZATION' ),
					self::account( '2', 'LOCATION_GROUP' ),
					self::account( '3', 'PERSONAL' ),
				],
			]
		);
		$this->given_page( 'accounts/1/locations', '', [ 'locations' => [ self::location( '100', 'place-shared' ) ] ] );
		$this->given_page( 'accounts/3/locations', '', [ 'locations' => [ self::location( '100', 'place-shared' ) ] ] );

		$locations = $this->service->list_locations();

		$this->assertSame(
			[ 'accounts', 'accounts/3/locations', 'accounts/1/locations', 'accounts/2/locations' ],
			array_column( $this->requests, 0 )
		);
		$this->assertSame( '3', $locations[0]['accountId'] );
	}

	public function test_list_locations_formats_each_location() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ self::account( '106234255840114990952' ) ] ] );
		$this->given_page(
			'accounts/106234255840114990952/locations',
			'',
			[
				'locations' => [
					[
						'name'              => 'locations/1111',
						'title'             => "Jane's Bakery - Downtown",
						'storefrontAddress' => [
							'regionCode'         => 'US',
							'postalCode'         => '94103',
							'administrativeArea' => 'CA',
							'locality'           => 'San Francisco',
							'addressLines'       => [ '123 Market St', 'Suite 4' ],
						],
						'metadata'          => [
							'placeId' => 'ChIJd8BlQ2BZwokRAFUEcm_qrcA',
							'mapsUri' => 'https://maps.google.com/?cid=1111',
						],
					],
				],
			]
		);

		$this->assertSame(
			[
				[
					'id'        => '1111',
					'accountId' => '106234255840114990952',
					'title'     => "Jane's Bakery - Downtown",
					'address'   => '123 Market St, Suite 4, San Francisco, CA, 94103',
					'placeId'   => 'ChIJd8BlQ2BZwokRAFUEcm_qrcA',
					'mapsUri'   => 'https://maps.google.com/?cid=1111',
				],
			],
			$this->service->list_locations()
		);
	}

	public function test_list_locations_formats_a_location_with_no_address() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ self::account( '1' ) ] ] );
		$this->given_page( 'accounts/1/locations', '', [ 'locations' => [ [ 'name' => 'locations/1111' ] ] ] );

		$this->assertSame(
			[
				[
					'id'        => '1111',
					'accountId' => '1',
					'title'     => '',
					'address'   => '',
					'placeId'   => '',
					'mapsUri'   => '',
				],
			],
			$this->service->list_locations()
		);
	}

	public function test_list_locations_skips_locations_without_a_name() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ self::account( '1' ) ] ] );
		$this->given_page(
			'accounts/1/locations',
			'',
			[
				'locations' => [
					[ 'title' => 'No name A' ],
					self::location( '100', '' ),
					[
						'name'  => '',
						'title' => 'No name B',
					],
				],
			]
		);

		$this->assertSame( [ '100' ], array_column( $this->service->list_locations(), 'id' ) );
	}

	public function test_list_locations_returns_empty_list_without_accounts() {
		$this->given_page( 'accounts', '', [] );

		$this->assertSame( [], $this->service->list_locations() );
		$this->assertCount( 1, $this->requests );
	}

	public function test_list_locations_skips_accounts_whose_id_is_not_numeric() {
		$this->given_page(
			'accounts',
			'',
			[
				'accounts' => [
					[ 'name' => 'accounts/..' ],
					[ 'name' => 'accounts/abc' ],
					[ 'name' => 'accounts/' ],
					[ 'name' => 'accounts/12 3' ],
					self::account( '2' ),
				],
			]
		);
		$this->given_page( 'accounts/2/locations', '', [ 'locations' => [ self::location( '22', 'place-22' ) ] ] );

		$this->assertSame( [ '22' ], array_column( $this->service->list_locations(), 'id' ) );
		$this->assertSame( [ 'accounts', 'accounts/2/locations' ], array_column( $this->requests, 0 ) );
	}

	public function test_list_locations_skips_an_account_without_a_name() {
		$this->given_page( 'accounts', '', [ 'accounts' => [ [ 'type' => 'PERSONAL' ], self::account( '2' ) ] ] );
		$this->given_page( 'accounts/2/locations', '', [ 'locations' => [ self::location( '22', 'place-22' ) ] ] );

		$this->assertSame( [ '22' ], array_column( $this->service->list_locations(), 'id' ) );
	}

	public function test_list_locations_stops_when_a_page_token_repeats() {
		$this->given_page(
			'accounts',
			'',
			[
				'accounts'      => [ self::account( '1' ) ],
				'nextPageToken' => 'a2',
			]
		);
		$this->given_page(
			'accounts',
			'a2',
			[
				'accounts'      => [ self::account( '2' ) ],
				'nextPageToken' => 'a2',
			]
		);

		try {
			$this->service->list_locations();
			$this->fail( 'Expected BusinessProfileApiException' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( 502, $e->get_http_status() );
			$this->assertSame( 'Google Business Profile returned the same page twice.', $e->getMessage() );
		}

		$this->assertCount( 2, $this->requests );
	}

	public function test_list_locations_stops_after_the_most_pages_one_list_may_have() {
		$this->client = $this->createMock( BusinessProfileApiClient::class );
		$calls        = 0;
		$this->client->method( 'get' )->willReturnCallback(
			function () use ( &$calls ) {
				++$calls;

				return [
					'accounts'      => [],
					'nextPageToken' => "page-{$calls}",
				];
			}
		);
		$this->service = new LocationService( $this->client );

		try {
			$this->service->list_locations();
			$this->fail( 'Expected BusinessProfileApiException' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( 502, $e->get_http_status() );
			$this->assertSame( 'Google Business Profile returned more pages than expected.', $e->getMessage() );
		}

		$this->assertSame( 50, $calls );
	}

	public function test_list_locations_passes_on_an_api_error() {
		$this->client = $this->createMock( BusinessProfileApiClient::class );
		$this->client->method( 'get' )->willThrowException(
			new BusinessProfileApiException( 403, [ 'error' => [ 'message' => 'Forbidden' ] ], __METHOD__ )
		);
		$this->service = new LocationService( $this->client );

		$this->expectException( BusinessProfileApiException::class );
		$this->expectExceptionCode( 403 );

		$this->service->list_locations();
	}

	public function test_select_location_stores_only_the_chosen_location() {
		$this->given_two_accounts_sharing_a_location();
		$this->options->method( 'get' )->willReturn( [] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::BUSINESS_PROFILE,
				[
					'account_id'       => '2',
					'location_id'      => '200',
					'location_title'   => 'Location 200',
					'location_address' => '',
					'place_id'         => 'place-other',
					'maps_uri'         => '',
				]
			)
			->willReturn( true );

		$location = $this->service->select_location( '200' );

		$this->assertSame( '200', $location['id'] );
	}

	public function test_select_location_replaces_stored_data_instead_of_adding_to_it() {
		$this->given_two_accounts_sharing_a_location();
		// Leftover data with no connected location, for example from an earlier version.
		$this->options->method( 'get' )->willReturn(
			[
				'account_id' => 'old-account',
				'unexpected' => 'leftover',
			]
		);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::BUSINESS_PROFILE,
				$this->callback(
					function ( array $value ) {
						return '100' === $value['location_id']
							&& '1' === $value['account_id']
							&& ! array_key_exists( 'unexpected', $value );
					}
				)
			)
			->willReturn( true );

		$this->service->select_location( '100' );
	}

	public function test_select_location_stores_a_shared_location_under_the_personal_account() {
		$this->given_two_accounts_sharing_a_location();
		$this->options->method( 'get' )->willReturn( [] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::BUSINESS_PROFILE,
				$this->callback(
					function ( array $value ) {
						return '1' === $value['account_id'];
					}
				)
			)
			->willReturn( true );

		$this->service->select_location( '100' );
	}

	public function test_select_location_refuses_while_a_location_is_connected() {
		$this->options->method( 'get' )->willReturn( self::stored_location( '1111', '1' ) );
		$this->options->expects( $this->never() )->method( 'update' );

		try {
			$this->service->select_location( '200' );
			$this->fail( 'Expected an exception' );
		} catch ( Exception $e ) {
			$this->assertSame( 409, $e->getCode() );
		}

		$this->assertSame( [], $this->requests );
	}

	public function test_select_location_refuses_a_location_the_user_cannot_reach() {
		$this->given_two_accounts_sharing_a_location();
		$this->options->method( 'get' )->willReturn( [] );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 404 );

		$this->service->select_location( '999' );
	}

	public function test_select_location_refuses_an_empty_id_without_calling_google() {
		$this->options->method( 'get' )->willReturn( [] );
		$this->options->expects( $this->never() )->method( 'update' );

		try {
			$this->service->select_location( '' );
			$this->fail( 'Expected an exception' );
		} catch ( Exception $e ) {
			$this->assertSame( 404, $e->getCode() );
		}

		$this->assertSame( [], $this->requests );
	}

	public function test_select_location_reports_a_failed_save() {
		$this->given_two_accounts_sharing_a_location();
		$this->options->method( 'get' )->willReturn( [] );
		$this->options->method( 'update' )->willReturn( false );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 500 );

		$this->service->select_location( '100' );
	}

	public function test_select_location_passes_on_an_api_error() {
		$this->client = $this->createMock( BusinessProfileApiClient::class );
		$this->client->method( 'get' )->willThrowException(
			new BusinessProfileApiException( 503, [], __METHOD__ )
		);
		$this->service = new LocationService( $this->client );
		$this->service->set_options_object( $this->options );
		$this->options->method( 'get' )->willReturn( [] );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->expectException( BusinessProfileApiException::class );
		$this->expectExceptionCode( 503 );

		$this->service->select_location( '100' );
	}

	protected function given_two_accounts_sharing_a_location(): void {
		$this->given_page(
			'accounts',
			'',
			[ 'accounts' => [ self::account( '1', 'PERSONAL' ), self::account( '2' ) ] ]
		);
		$this->given_page( 'accounts/1/locations', '', [ 'locations' => [ self::location( '100', 'place-shared' ) ] ] );
		$this->given_page(
			'accounts/2/locations',
			'',
			[ 'locations' => [ self::location( '200', 'place-other' ), self::location( '100', 'place-shared' ) ] ]
		);
	}

	/**
	 * @param string $path       Request path.
	 * @param string $page_token Page token the request carries.
	 * @param array  $response   Response to return.
	 */
	protected function given_page( string $path, string $page_token, array $response ): void {
		$this->responses[ "{$path}|{$page_token}" ] = $response;
	}

	/**
	 * @param string $path Request path.
	 *
	 * @return array The query of every request sent to the path.
	 */
	protected function requests_for( string $path ): array {
		$queries = [];

		foreach ( $this->requests as [ $request_path, $query ] ) {
			if ( $path === $request_path ) {
				$queries[] = $query;
			}
		}

		return $queries;
	}

	protected static function account( string $id, string $type = 'ORGANIZATION' ): array {
		return [
			'name' => "accounts/{$id}",
			'type' => $type,
		];
	}

	protected static function location( string $id, string $place_id ): array {
		return [
			'name'     => "locations/{$id}",
			'title'    => "Location {$id}",
			'metadata' => [ 'placeId' => $place_id ],
		];
	}

	protected static function stored_location( string $location_id, string $account_id ): array {
		return [
			'account_id'       => $account_id,
			'location_id'      => $location_id,
			'location_title'   => "Location {$location_id}",
			'location_address' => '123 Market St, San Francisco',
			'place_id'         => "place-{$location_id}",
			'maps_uri'         => "https://maps.google.com/?cid={$location_id}",
		];
	}
}
