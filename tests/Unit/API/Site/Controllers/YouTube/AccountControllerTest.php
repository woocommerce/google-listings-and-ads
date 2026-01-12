<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\YouTube\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\StreamInterface;
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

	/** @var MockObject|Client $client */
	protected $client;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT        = '/wc/gla/youtube/connect';
	protected const ROUTE_CONNECTION     = '/wc/gla/youtube/connection';
	protected const ROUTE_SETUP_COMPLETE = '/wc/gla/youtube/setup/complete';

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->client     = $this->createMock( Client::class );
		$this->controller = new AccountController( $this->server, $this->connection, $this->client );
		$this->controller->set_options_object( $this->createMock( OptionsInterface::class ) );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url = 'https://domain.test?auth=1';

		$this->connection->expects( $this->once() )
			->method( 'connect' )
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

	public function test_connected() {
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

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'connected',
				'channel' => [
					'id'    => 1234,
					'label' => 'Channel 1',
				],
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
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_setup_complete_success() {
		// Mock WordPress functions.
		add_filter(
			'pre_option_blogname',
			function () {
				return 'Test Store';
			}
		);
		add_filter(
			'home_url',
			function () {
				return 'https://test-store.example';
			}
		);

		// Mock options to return merchant center data.
		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER )
			->willReturn( [ 'id' => 1234567890 ] );

		$this->controller->set_options_object( $options );

		// Mock successful WCS response.
		$stream = $this->createMock( StreamInterface::class );
		$stream->expects( $this->once() )
			->method( 'getContents' )
			->willReturn(
				wp_json_encode(
					[
						'kind'   => 'youtube#thirdPartyLink',
						'status' => [ 'linkStatus' => 'linked' ],
					]
				)
			);

		$response = $this->createMock( ResponseInterface::class );
		$response->expects( $this->once() )
			->method( 'getStatusCode' )
			->willReturn( 200 );
		$response->expects( $this->once() )
			->method( 'getBody' )
			->willReturn( $stream );

		$this->client->expects( $this->once() )
			->method( 'post' )
			->willReturn( $response );

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
		// Mock WordPress functions.
		add_filter(
			'pre_option_blogname',
			function () {
				return 'Test Store';
			}
		);
		add_filter(
			'home_url',
			function () {
				return 'https://test-store.example';
			}
		);

		// Mock options to return merchant center data.
		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER )
			->willReturn( [ 'id' => 1234567890 ] );

		$this->controller->set_options_object( $options );

		// Mock client exception.
		$client_exception = $this->createMock( ClientExceptionInterface::class );
		$client_exception->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( 'Client error occurred' );

		$this->client->expects( $this->once() )
			->method( 'post' )
			->willThrowException( $client_exception );

		$response = $this->do_request( self::ROUTE_SETUP_COMPLETE, 'POST' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_setup_complete_with_merchant_center_not_configured() {
		// Mock WordPress functions.
		add_filter(
			'pre_option_blogname',
			function () {
				return 'Test Store';
			}
		);
		add_filter(
			'home_url',
			function () {
				return 'https://test-store.example';
			}
		);

		// Mock options to return empty merchant center data.
		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER )
			->willReturn( [] );

		$this->controller->set_options_object( $options );

		$response = $this->do_request( self::ROUTE_SETUP_COMPLETE, 'POST' );

		$this->assertEquals(
			[ 'message' => 'Merchant Center account is not configured.' ],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}
}
