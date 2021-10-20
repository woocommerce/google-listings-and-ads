<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_Test_Spy_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class RESTControllerUnitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework
 *
 * @property RESTServer $server
 */
abstract class RESTControllerUnitTest extends UnitTest {

	/**
	 * Controller we are testing.
	 *
	 * @var BaseController
	 */
	protected $controller;

	/**
	 * @var RESTServer
	 */
	protected $server;

	/**
	 * User variable.
	 *
	 * @var \WP_User
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
		$wp_rest_server = new WP_Test_Spy_REST_Server();
		$this->server   = new RESTServer( $wp_rest_server );
		wp_set_current_user( self::$user );
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
	 * Perform a request and return the status and returned data.
	 *
	 * @param string  $endpoint Endpoint to hit.
	 * @param string  $type     Type of request e.g GET or POST.
	 * @param array   $params   Request body or query.
	 *
	 * @return Response
	 */
	protected function do_request( string $endpoint, string $type = 'GET', array $params = [] ): object {
		$request = new Request( $type, $endpoint );
		'GET' === $type ? $request->set_query_params( $params ) : $request->set_body_params( $params );
		return $this->server->dispatch_request( $request );
	}

}
