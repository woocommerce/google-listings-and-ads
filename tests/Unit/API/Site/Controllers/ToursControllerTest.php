<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TourController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for ToursController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 * @group Tours
 */
class ToursControllerTest extends RESTControllerUnitTest {

	protected const TEST_TOUR_ID = 'test';
	protected const ROUTE        = '/wc/gla/tours';

	protected const TEST_TOUR_CHECKED = [
		'id'      => 'test',
		'checked' => true,
	];

	protected const TEST_TOUR_NOT_CHECKED = [
		'id'      => 'test2',
		'checked' => false,
	];

	protected const TEST_TOURS = [
		'test'  => self::TEST_TOUR_CHECKED,
		'test2' => self::TEST_TOUR_NOT_CHECKED,
	];


	/**
	 * @var OptionsInterface
	 */
	private OptionsInterface $options;

	public function setUp(): void {
		parent::setUp();
		$this->options    = $this->createMock( OptionsInterface::class );
		$this->controller = new TourController( $this->server );
		$this->controller->register();
		$this->controller->set_options_object( $this->options );

	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	public function test_get_tour_route() {
		$this->options->expects( $this->once() )
			->method( 'get' )->willReturn( self::TEST_TOURS );

		$response = $this->do_request( self::ROUTE . '/' . self::TEST_TOUR_ID );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::TEST_TOUR_CHECKED, $response->get_data() );
	}

	public function test_post_tour_route() {
		$this->options->expects( $this->once() )
			->method( 'get' )->willReturn( self::TEST_TOURS );

		$this->options->expects( $this->once() )
			->method( 'update' )->willReturn( true );

		$response = $this->do_request( self::ROUTE, 'POST', self::TEST_TOUR_CHECKED );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully updated the tour.',
			],
			$response->get_data()
		);
	}

	public function test_post_tour_error() {
		$this->options->expects( $this->once() )
			->method( 'get' )->willReturn( self::TEST_TOURS );

		$this->options->expects( $this->once() )
			->method( 'update' )->willReturn( false );

		$response = $this->do_request( self::ROUTE, 'POST', self::TEST_TOUR_CHECKED );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( [ 'message' => 'Unable to updated the tour.' ], $response->get_data() );
	}

	public function test_post_tour_invalid() {
		$response = $this->do_request( self::ROUTE, 'POST', [] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_tour_not_found() {
		$response = $this->do_request( self::ROUTE . '/tour_not_exists' );
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'Tour not found', $response->get_data()['message'] );
	}

	public function test_get_tour_bad_pattern() {
		$response = $this->do_request( self::ROUTE . '/$$$' );
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'No route was found matching the URL and request method.', $response->get_data()['message'] );
	}
}
