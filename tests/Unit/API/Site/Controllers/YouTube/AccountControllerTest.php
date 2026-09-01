<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\YouTube\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;


/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\YouTube
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var AccountController $controller */
	protected $controller;

	/** @var OptionsInterface */
	protected $options;

	protected const ROUTE_CONNECT        = '/wc/gla/youtube/connect';
	protected const ROUTE_CONNECTION     = '/wc/gla/youtube/connection';
	protected const ROUTE_SETUP_COMPLETE = '/wc/gla/youtube/setup/complete';

	public function setUp(): void {
		parent::setUp();

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->connection = $this->createMock( Connection::class );
		$this->controller = new AccountController( $this->server, $this->connection );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url   = 'https://domain.test?auth=1';
		$return_url = admin_url(
			'admin.php?page=wc-admin&path=/google/settings&section=accounts'
		);

		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->with( $return_url )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals(
			[
				'url' => $auth_url,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connect_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_disconnect() {
		$this->connection->expects( $this->once() )
			->method( 'disconnect' );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'DELETE' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully disconnected.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connection() {
		// Mock status response.
		$status = [
			'status' => 'connected',
		];

		// Mock channels response.
		$channels = [
			'items' => [
				[
					'id'      => 1234,
					'snippet' => [
						'title' => 'Channel 1',
					],
				],
			],
		];

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( $status );

		$this->connection->expects( $this->once() )
			->method( 'get_channels' )
			->willReturn( $channels );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::YOUTUBE_THIRD_PARTY_LINK, false )
			->willReturn(
				[
					'status' => [
						'linkStatus' => 'linked',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'connected',
				'channel' => [
					'id'    => 1234,
					'label' => 'Channel 1',
				],
				'error'   => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connection_channel_lookup_fails() {
		// Status is confirmed connected, but the channel metadata lookup throws
		// (e.g. a 403 quota error from the Connect Server proxy).
		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn(
				[
					'status' => 'connected',
				]
			);

		$this->connection->expects( $this->once() )
			->method( 'get_channels' )
			->willThrowException( new Exception( 'Error retrieving channels', 403 ) );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::YOUTUBE_THIRD_PARTY_LINK, false )
			->willReturn(
				[
					'status' => [
						'linkStatus' => 'linked',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'connected',
				'channel' => [],
				'error'   => 'Error retrieving channels',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connection_channel_lookup_fails_and_store_not_linked() {
		// The store-link check is done before the channel lookup, so when the
		// store isn't linked, status falls back to 'incomplete' and channels
		// are never fetched.
		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn(
				[
					'status' => 'connected',
				]
			);

		$this->connection->expects( $this->never() )
			->method( 'get_channels' );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::YOUTUBE_THIRD_PARTY_LINK, false )
			->willReturn( false );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'incomplete',
				'channel' => [],
				'error'   => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_incomplete() {
		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn(
				[
					'status' => 'connected',
				]
			);

		// Store not linked, so channel metadata is never fetched.
		$this->connection->expects( $this->never() )
			->method( 'get_channels' );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::YOUTUBE_THIRD_PARTY_LINK, false )
			->willReturn( false );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'incomplete',
				'channel' => [],
				'error'   => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnected() {
		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'disconnected',
				'channel' => [],
				'error'   => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_setup_complete_success() {
		$this->connection->expects( $this->once() )
			->method( 'third_party_link' )
			->willReturn(
				[
					'status' => [
						'linkStatus' => 'linked',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_SETUP_COMPLETE, 'POST' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully completed YouTube setup.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_setup_complete_with_client_error() {
		$this->connection->expects( $this->once() )
			->method( 'third_party_link' )
			->willThrowException( new Exception( 'Unable to complete YouTube setup.' ) );

		$response = $this->do_request( self::ROUTE_SETUP_COMPLETE, 'POST' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_setup_complete_with_merchant_center_not_configured() {
		$this->connection->expects( $this->once() )
			->method( 'third_party_link' )
			->willThrowException( new Exception( 'Merchant Center account is not configured.' ) );

		$response = $this->do_request( self::ROUTE_SETUP_COMPLETE, 'POST' );

		$this->assertEquals(
			[ 'message' => 'Merchant Center account is not configured.' ],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}
}
