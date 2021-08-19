<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class RESTControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework
 *
 * @property RESTServer $server
 */
abstract class RESTControllerUnitTest extends UnitTest {

	/**
	 * Controller we are testing.
	 *
	 * @var mixed
	 */
	protected $controller;

	/**
	 * Routes that this controller creates.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [];

	/**
	 * @var RESTServer
	 */
	protected $server;

	/**
	 * User variable.
	 *
	 * @var WP_User
	 */
	protected static $user;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Setup our test server.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new MockRESTServer();
		$this->server   = new RESTServer( $wp_rest_server );
		$wp_rest_server;
		wp_set_current_user( self::$user );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
	 	$actual_routes   = $this->server->get_routes();
	 	$expected_routes = $this->routes;

	 	foreach ( $expected_routes as $expected_route ) {
	 		$this->assertArrayHasKey( $expected_route, $actual_routes );
	 	}
	}

	/**
	 * Validate that the returned API schema matches what is expected.

	 */
	public function test_schema_properties() {
		$request    = new Request( 'OPTIONS', $this->routes[0] );
		$response   = $this->server->dispatch_request( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( count( array_keys( $this->properties ) ), count( $properties ), print_r( array_diff( array_keys( $properties ), array_keys( $this->properties ) ), true ) );

		foreach ( array_keys( $this->properties ) as $property ) {
			$this->assertArrayHasKey( $property, $properties );
			$this->assertEquals( $this->properties[ $property ], $properties[ $property ]['context'] );
		}
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
		unset( $this->server );
	}

	/**
	 * Register routes for the controller we are testing.
	 */
	protected function register_test_routes() {
		$this->controller->register_routes();
		do_action( 'rest_api_init' );
	}

	/**
	 * Perform a request and return the status and returned data.
	 *
	 * @param string  $endpoint Endpoint to hit.
	 * @param string  $type     Type of request e.g GET or POST.
	 * @param array   $params   Request body or query.
	 * @return object
	 */
	protected function do_request( string $endpoint, string $type = 'GET', array $params = [] ): object {
		$request = new Request( $type, untrailingslashit( $endpoint ) );
		'GET' === $type ? $request->set_query_params( $params ) : $request->set_body_params( $params );
		$response = $this->server->dispatch_request( $request );

		return (object) [
			'status' => $response->get_status(),
			'data'   => json_decode( wp_json_encode( $response->get_data() ), true ),
			'raw'    => $response->get_data(),
		];
	}

	/**
	 * Test the response matches the expected status code and contains data.
	 *
	 * @param object $response    Response object from do_request above.
	 * @param int    $status_code Expected status code.
	 */
	protected function assertExpectedResponse( object $response, int $status_code = 200 ) {
		$this->assertObjectHasAttribute( 'status', $response );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertEquals( $status_code, $response->status );
	}

}
