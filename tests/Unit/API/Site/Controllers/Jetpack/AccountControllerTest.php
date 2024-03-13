<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Jetpack;

use Automattic\Jetpack\Connection\Manager;
use Automattic\Jetpack\Connection\Tokens;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Error;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Jetpack
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var Manager|MockObject $manager */
	protected $manager;

	/** @var Middleware|MockObject $middleware */
	protected $middleware;

	/** @var Options|MockObject $options */
	protected $options;

	/** @var Tokens|MockObject $tokens */
	protected $tokens;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT   = '/wc/gla/jetpack/connect';
	protected const ROUTE_CONNECTED = '/wc/gla/jetpack/connected';

	public function setUp(): void {
		parent::setUp();

		$this->manager    = $this->createMock( Manager::class );
		$this->middleware = $this->createMock( Middleware::class );
		$this->options    = $this->createMock( OptionsInterface::class );
		$this->tokens     = $this->createMock( Tokens::class );

		$this->controller = new AccountController( $this->server, $this->manager, $this->middleware );
		$this->controller->register();
		$this->controller->set_options_object( $this->options );
	}

	public function test_connect() {
		$auth_url          = 'https://domain.test?auth=1';
		$expected_auth_url = $auth_url . '&from=google-listings-and-ads';

		$this->manager->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$this->manager->expects( $this->once() )
			->method( 'register' )
			->willReturn( true );

		$this->manager->expects( $this->once() )
			->method( 'get_authorization_url' )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals(
			[
				'url' => $expected_auth_url,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connect_with_error() {
		$this->manager->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$this->manager->expects( $this->once() )
			->method( 'register' )
			->willReturn( new WP_Error( 'error', 'Error message' ) );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'error',
				'message' => 'Error message',
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_reconnect() {
		$auth_url          = 'https://domain.test?auth=1';
		$expected_auth_url = $auth_url . '&from=google-listings-and-ads';

		$this->manager->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->manager->expects( $this->once() )
			->method( 'reconnect' )
			->willReturn( true );

		$this->manager->expects( $this->once() )
			->method( 'get_authorization_url' )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals(
			[
				'url' => $expected_auth_url,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnect() {
		$this->manager->expects( $this->once() )
			->method( 'remove_connection' );
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive(
				[ OptionsInterface::WP_TOS_ACCEPTED ],
				[ OptionsInterface::JETPACK_CONNECTED ]
			);

		$response = $this->do_request( self::ROUTE_CONNECT, 'DELETE' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully disconnected.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connected() {
		$name      = 'John Doe';
		$email     = 'john@doe.email';
		$user_data = [
			'display_name' => $name,
			'email'        => $email,
		];

		$this->manager->method( 'has_connected_owner' )->willReturn( true );
		$this->manager->method( 'is_connected' )->willReturn( true );
		$this->tokens->method( 'validate_blog_token' )->willReturn( true );
		$this->manager->method( 'get_tokens' )->willReturn( $this->tokens );
		$this->manager->method( 'is_connection_owner' )->willReturn( true );

		// Confirm the WP TOS is marked as accepted for the current user.
		$this->middleware->expects( $this->once() )
			->method( 'mark_tos_accepted' )
			->with( 'wp-com', wp_get_current_user()->user_email );

		$this->manager->expects( $this->once() )
			->method( 'get_connected_user_data' )
			->willReturn( $user_data );

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active'      => 'yes',
				'owner'       => 'yes',
				'displayName' => $name,
				'email'       => $email,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Tests an invalid token stored locally.
	 */
	public function test_connected_invalid_token() {
		$this->manager->method( 'has_connected_owner' )->willReturn( true );
		$this->manager->method( 'is_connected' )->willReturn( false );
		$this->manager->method( 'is_connection_owner' )->willReturn( true );

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active'      => 'no',
				'owner'       => 'yes',
				'displayName' => '',
				'email'       => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Tests a valid token stored locally, but invalid once checked externally.
	 * The Jetpack connection package handles the external check through `validate_blog_token`.
	 */
	public function test_connected_token_does_not_validate() {
		$this->manager->method( 'has_connected_owner' )->willReturn( true );
		$this->manager->method( 'is_connected' )->willReturn( true );
		$this->manager->method( 'is_connection_owner' )->willReturn( true );
		$this->tokens->method( 'validate_blog_token' )->willReturn( false );
		$this->manager->method( 'get_tokens' )->willReturn( $this->tokens );

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active'      => 'no',
				'owner'       => 'yes',
				'displayName' => '',
				'email'       => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnected() {
		$this->manager->method( 'has_connected_owner' )->willReturn( false );
		$this->manager->method( 'is_connection_owner' )->willReturn( false );

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active'      => 'no',
				'owner'       => 'no',
				'displayName' => '',
				'email'       => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}
}
