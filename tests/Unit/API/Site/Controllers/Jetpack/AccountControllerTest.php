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
 *
 * @property Manager|MockObject    $manager
 * @property Middleware|MockObject $middleware
 * @property Options|MockObject    $options
 * @property Tokens|MockObject     $tokens
 * @property AccountController     $controller
 */
class AccountControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_CONNECT   = '/wc/gla/jetpack/connect';
	protected const ROUTE_CONNECTED = '/wc/gla/jetpack/connected';

	public function setUp() {
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

		$this->tokens->method( 'get_access_token' )->willReturn( 'abcd' );
		$this->manager->method( 'is_active' )->willReturn( true );
		$this->manager->method( 'is_connection_owner' )->willReturn( true );
		$this->manager->method( 'get_tokens' )->willReturn( $this->tokens );

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

	public function test_connected_invalid_token() {
		$this->tokens->method( 'get_access_token' )->willReturn( new WP_Error( 'invalid token' ) );
		$this->manager->method( 'is_active' )->willReturn( true );
		$this->manager->method( 'is_connection_owner' )->willReturn( true );
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
		$this->manager->method( 'is_active' )->willReturn( false );
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
