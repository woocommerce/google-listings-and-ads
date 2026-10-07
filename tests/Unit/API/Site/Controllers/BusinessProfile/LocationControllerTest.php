<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile\LocationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile
 */
class LocationControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var LocationController $controller */
	protected $controller;

	protected const ROUTE_LOCATIONS = '/wc/gla/google-business-profile/locations';

	public function setUp(): void {
		parent::setUp();

		$this->location_service = $this->createMock( LocationService::class );
		$this->controller       = new LocationController( $this->server, $this->location_service );
		$this->controller->register();
	}

	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_LOCATIONS, $this->server->get_routes() );
	}

	public function test_get_locations() {
		$locations = [
			[
				'id'        => '1111',
				'accountId' => '1',
				'title'     => "Jane's Bakery - Downtown",
				'address'   => '123 Market St, San Francisco, 94103',
				'placeId'   => 'ChIJd8BlQ2BZwokRAFUEcm_qrcA',
				'mapsUri'   => 'https://maps.google.com/?cid=1111',
			],
		];

		$this->location_service->expects( $this->once() )
			->method( 'list_locations' )
			->willReturn( $locations );

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'GET' );

		$this->assertEquals( $locations, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_locations_with_no_locations() {
		$this->location_service->expects( $this->once() )
			->method( 'list_locations' )
			->willReturn( [] );

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'GET' );

		$this->assertEquals( [], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_locations_with_business_profile_api_error() {
		$google_error = [
			'code'    => 403,
			'message' => 'Not authorized',
			'status'  => 'PERMISSION_DENIED',
			'errors'  => [ [ 'reason' => 'forbidden' ] ],
		];

		$this->location_service->expects( $this->once() )
			->method( 'list_locations' )
			->willThrowException(
				new BusinessProfileApiException( 403, [ 'error' => $google_error ], __METHOD__ )
			);

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'GET' );

		$this->assertSame(
			[
				'message' => 'Not authorized',
				'code'    => 'API_ERROR',
				'data'    => $google_error,
			],
			$response->get_data()
		);
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_get_locations_with_error() {
		$this->location_service->expects( $this->once() )
			->method( 'list_locations' )
			->willThrowException( new Exception( 'error', 401 ) );

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_select_location() {
		$this->location_service->expects( $this->once() )
			->method( 'select_location' )
			->with( '1111' );

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'POST', [ 'id' => '1111' ] );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully connected Google Business Profile location.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_select_location_requires_id() {
		$this->location_service->expects( $this->never() )->method( 'select_location' );

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'POST', [] );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_select_location_while_a_location_is_connected() {
		$this->location_service->expects( $this->once() )
			->method( 'select_location' )
			->willThrowException( new Exception( 'Already connected', 409 ) );

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'POST', [ 'id' => '1111' ] );

		$this->assertEquals( [ 'message' => 'Already connected' ], $response->get_data() );
		$this->assertEquals( 409, $response->get_status() );
	}

	public function test_select_location_with_business_profile_api_error() {
		$this->location_service->expects( $this->once() )
			->method( 'select_location' )
			->willThrowException(
				new BusinessProfileApiException( 503, [ 'message' => 'Unavailable' ], __METHOD__ )
			);

		$response = $this->do_request( self::ROUTE_LOCATIONS, 'POST', [ 'id' => '1111' ] );

		$this->assertSame(
			[
				'message' => 'Unavailable',
				'code'    => 'API_ERROR',
				'data'    => [],
			],
			$response->get_data()
		);
		$this->assertEquals( 503, $response->get_status() );
	}

	public function test_locations_route_requires_manage_woocommerce() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->location_service->expects( $this->never() )->method( 'list_locations' );
		$this->location_service->expects( $this->never() )->method( 'select_location' );

		$this->assertEquals( 403, $this->do_request( self::ROUTE_LOCATIONS, 'GET' )->get_status() );
		$this->assertEquals( 403, $this->do_request( self::ROUTE_LOCATIONS, 'POST', [ 'id' => '1111' ] )->get_status() );
	}
}
