<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager
 */
class ConnectionTest extends UnitTest {

	protected const CONNECT_SERVER_ROOT = 'https://wcs.example.com/';

	/** @var Container */
	protected $container;

	/** @var MockObject|TagManagerApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var Connection */
	protected $connection;

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', self::CONNECT_SERVER_ROOT );

		$this->client  = $this->createMock( TagManagerApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );

		$this->connection = new Connection( $this->client );
		$this->connection->set_container( $this->container );
		$this->connection->set_options_object( $this->options );
	}

	/**
	 * Queue a Guzzle response for the raw `Client::class` (connect/disconnect/status) calls.
	 *
	 * @param Response $response
	 */
	protected function queue_guzzle_response( Response $response ): void {
		$stack = HandlerStack::create( new MockHandler( [ $response ] ) );
		$this->container->add( Client::class, new Client( [ 'handler' => $stack ] ) );
	}

	public function test_connect_returns_oauth_url_on_success() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) )
		);

		$url = $this->connection->connect( 'https://example.com/return' );

		$this->assertSame( 'https://accounts.google.com/o/oauth2/auth', $url );
	}

	public function test_connect_throws_when_response_has_no_oauth_url() {
		$this->queue_guzzle_response( new Response( 200, [], wp_json_encode( [ 'status' => 'ok' ] ) ) );

		$this->expectException( Exception::class );
		$this->connection->connect( 'https://example.com/return' );
	}

	public function test_disconnect_is_purely_local() {
		// No Client::class registered in the container at all — if disconnect()
		// ever tried a remote call, resolving it would throw and fail this test.
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::TAG_MANAGER );

		$message = $this->connection->disconnect();

		$this->assertSame( 'Successfully disconnected.', $message );
	}

	public function test_get_status_returns_disconnected_when_scope_not_granted() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ 'https://www.googleapis.com/auth/content' ] ] ) )
		);

		$status = $this->connection->get_status();

		$this->assertSame( [ 'status' => Connection::STATUS_DISCONNECTED ], $status );
	}

	public function test_get_status_returns_incomplete_when_scope_granted_but_nothing_selected() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_TAG_MANAGER ] ] ) )
		);
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => null,
				'account_name'        => null,
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);

		$status = $this->connection->get_status();

		$this->assertSame( [ 'status' => Connection::STATUS_INCOMPLETE ], $status );
	}

	public function test_get_status_returns_connected_with_full_shape_when_account_and_container_selected() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_TAG_MANAGER ] ] ) )
		);
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => '123',
				'account_name'        => 'Example Store',
				'container_id'        => '456',
				'container_name'      => 'Example Store - Web',
				'container_public_id' => 'GTM-ABCDEFG',
			]
		);

		$status = $this->connection->get_status();

		$this->assertSame(
			[
				'status'            => Connection::STATUS_CONNECTED,
				'id'                => '123',
				'name'              => 'Example Store',
				'containerId'       => '456',
				'containerName'     => 'Example Store - Web',
				'containerPublicId' => 'GTM-ABCDEFG',
			],
			$status
		);
	}

	public function test_list_accounts_maps_response_to_id_name_shape() {
		$this->client->method( 'get' )->with( 'accounts' )->willReturn(
			[
				'account' => [
					[
						'accountId' => '123',
						'name'      => 'Example Store',
						'features'  => [ 'supportUserPermissions' => true ],
					],
				],
			]
		);

		$accounts = $this->connection->list_accounts();

		$this->assertSame(
			[
				[
					'id'   => '123',
					'name' => 'Example Store',
				],
			],
			$accounts
		);
	}

	public function test_list_accounts_returns_empty_array_when_no_accounts() {
		$this->client->method( 'get' )->willReturn( [] );

		$this->assertSame( [], $this->connection->list_accounts() );
	}

	public function test_select_account_stores_account_and_clears_container() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => null,
				'account_name'        => null,
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);
		$this->client->method( 'get' )
			->with( 'accounts/123' )
			->willReturn(
				[
					'accountId' => '123',
					'name'      => 'Example Store',
				]
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::TAG_MANAGER,
				[
					'account_id'          => '123',
					'account_name'        => 'Example Store',
					'container_id'        => null,
					'container_name'      => null,
					'container_public_id' => null,
				]
			)
			->willReturn( true );

		$this->connection->select_account( '123' );
	}

	public function test_list_containers_throws_when_no_account_selected() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => null,
				'account_name'        => null,
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);

		$this->expectException( Exception::class );
		$this->connection->list_containers();
	}

	public function test_list_containers_maps_response_to_id_publicid_name_shape() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => '123',
				'account_name'        => 'Example Store',
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);
		$this->client->method( 'get' )->with( 'accounts/123/containers' )->willReturn(
			[
				'container' => [
					[
						'containerId' => '456',
						'publicId'    => 'GTM-ABCDEFG',
						'name'        => 'Example Store - Web',
					],
				],
			]
		);

		$containers = $this->connection->list_containers();

		$this->assertSame(
			[
				[
					'id'       => '456',
					'publicId' => 'GTM-ABCDEFG',
					'name'     => 'Example Store - Web',
				],
			],
			$containers
		);
	}

	public function test_select_container_throws_when_no_account_selected() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => null,
				'account_name'        => null,
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);

		$this->expectException( Exception::class );
		$this->connection->select_container( '456' );
	}

	public function test_select_container_stores_container() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => '123',
				'account_name'        => 'Example Store',
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);
		$this->client->method( 'get' )
			->with( 'accounts/123/containers/456' )
			->willReturn(
				[
					'containerId' => '456',
					'publicId'    => 'GTM-ABCDEFG',
					'name'        => 'Example Store - Web',
				]
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::TAG_MANAGER,
				[
					'account_id'          => '123',
					'account_name'        => 'Example Store',
					'container_id'        => '456',
					'container_name'      => 'Example Store - Web',
					'container_public_id' => 'GTM-ABCDEFG',
				]
			)
			->willReturn( true );

		$this->connection->select_container( '456' );
	}
}
