<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
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

	/**
	 * Queue a Guzzle response for the raw `Client::class` calls, recording each request into
	 * the given `$history` array so the test can assert on what was actually sent.
	 *
	 * @param Response $response
	 * @param array    $history Populated with one entry per request as they're made.
	 */
	protected function queue_guzzle_response_with_history( Response $response, array &$history ): void {
		$stack = HandlerStack::create( new MockHandler( [ $response ] ) );
		$stack->push( Middleware::history( $history ) );
		$this->container->add( Client::class, new Client( [ 'handler' => $stack ] ) );
	}

	/**
	 * Queue a connection-level failure (no response at all) for the raw `Client::class` calls.
	 */
	protected function queue_guzzle_connection_failure(): void {
		$stack = HandlerStack::create(
			new MockHandler( [ new ConnectException( 'Connection timed out', new Request( 'GET', 'https://wcs.example.com/' ) ) ] )
		);
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

	public function test_connect_throws_when_the_request_itself_fails() {
		$this->queue_guzzle_connection_failure();

		$this->expectException( Exception::class );
		$this->connection->connect( 'https://example.com/return' );
	}

	public function test_connect_includes_login_hint_when_provided() {
		$history = [];
		$this->queue_guzzle_response_with_history(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) ),
			$history
		);

		$this->connection->connect( 'https://example.com/return', 'merchant@example.com' );

		$body = json_decode( (string) $history[0]['request']->getBody(), true );
		$this->assertEquals( 'merchant@example.com', $body['loginHint'] );
	}

	public function test_connect_omits_login_hint_when_not_provided() {
		$history = [];
		$this->queue_guzzle_response_with_history(
			new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.google.com/o/oauth2/auth' ] ) ),
			$history
		);

		$this->connection->connect( 'https://example.com/return' );

		$body = json_decode( (string) $history[0]['request']->getBody(), true );
		$this->assertArrayNotHasKey( 'loginHint', $body );
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

	public function test_get_status_throws_when_the_request_itself_fails() {
		$this->queue_guzzle_connection_failure();

		$this->expectException( Exception::class );
		$this->connection->get_status();
	}

	public function test_get_status_returns_disconnected_when_scope_not_granted() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ 'https://www.googleapis.com/auth/content' ] ] ) )
		);

		$status = $this->connection->get_status();

		$this->assertSame( [ 'status' => Connection::STATUS_DISCONNECTED ], $status );
	}

	public function test_get_status_returns_disconnected_when_scope_granted_but_no_account_selected() {
		// Not STATUS_INCOMPLETE — the account-card UI shown for that status assumes an
		// account is already chosen and only prompts for a container. Routing "nothing
		// chosen yet" there drops the merchant into container selection with no account
		// behind it, which is exactly the bug this test guards against.
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

		$this->assertSame( [ 'status' => Connection::STATUS_DISCONNECTED ], $status );
	}

	public function test_get_status_returns_incomplete_when_account_selected_but_container_is_not() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_TAG_MANAGER ] ] ) )
		);
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'          => '123',
				'account_name'        => 'Example Store',
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);

		$status = $this->connection->get_status();

		$this->assertSame(
			[
				'status'             => Connection::STATUS_INCOMPLETE,
				'canCreateContainer' => false,
				'id'                 => '123',
				'name'               => 'Example Store',
			],
			$status
		);
	}

	public function test_get_status_reports_that_a_container_can_be_created_when_the_edit_scope_is_granted() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_TAG_MANAGER, Connection::SCOPE_TAG_MANAGER_EDIT ] ] ) )
		);
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'   => '123',
				'account_name' => 'Example Store',
			]
		);

		$this->assertTrue( $this->connection->get_status()['canCreateContainer'] );
	}

	public function test_get_status_omits_the_create_signal_once_a_container_is_connected() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_TAG_MANAGER, Connection::SCOPE_TAG_MANAGER_EDIT ] ] ) )
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

		$this->assertArrayNotHasKey( 'canCreateContainer', $this->connection->get_status() );
	}

	/**
	 * @dataProvider can_create_containers_provider
	 *
	 * @param string[] $scopes   The scopes the shared Google connection carries.
	 * @param bool     $expected Whether creating a container can work.
	 */
	public function test_can_create_containers_depends_on_the_edit_scope( array $scopes, bool $expected ) {
		$this->queue_guzzle_response( new Response( 200, [], wp_json_encode( [ 'scope' => $scopes ] ) ) );

		$this->assertSame( $expected, $this->connection->can_create_containers() );
	}

	/**
	 * @return array
	 */
	public function can_create_containers_provider(): array {
		return [
			'edit scope granted'  => [ [ Connection::SCOPE_TAG_MANAGER, Connection::SCOPE_TAG_MANAGER_EDIT ], true ],
			'readonly scope only' => [ [ Connection::SCOPE_TAG_MANAGER ], false ],
			'no scopes'           => [ [], false ],
		];
	}

	public function test_can_create_containers_throws_when_the_request_itself_fails() {
		$this->queue_guzzle_connection_failure();

		$this->expectException( Exception::class );
		$this->connection->can_create_containers();
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
				'status'                => Connection::STATUS_CONNECTED,
				'id'                    => '123',
				'name'                  => 'Example Store',
				'containerId'           => '456',
				'containerName'         => 'Example Store - Web',
				'containerPublicId'     => 'GTM-ABCDEFG',
				'adsConversionConflict' => false,
			],
			$status
		);
	}

	public function test_get_status_reports_a_stored_ads_conversion_conflict() {
		$this->queue_guzzle_response(
			new Response( 200, [], wp_json_encode( [ 'scope' => [ Connection::SCOPE_TAG_MANAGER ] ] ) )
		);
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'              => '123',
				'account_name'            => 'Example Store',
				'container_id'            => '456',
				'container_name'          => 'Example Store - Web',
				'container_public_id'     => 'GTM-ABCDEFG',
				'ads_conversion_conflict' => true,
			]
		);
		$this->client->expects( $this->never() )->method( 'get' );

		$this->assertTrue( $this->connection->get_status()['adsConversionConflict'] );
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
					'account_id'              => '123',
					'account_name'            => 'Example Store',
					'container_id'            => null,
					'container_name'          => null,
					'container_public_id'     => null,
					'ads_conversion_conflict' => null,
					'pending_container_id'    => null,
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

		// The second write is the conflict check run after selecting; it's covered by its own tests.
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[
					OptionsInterface::TAG_MANAGER,
					[
						'account_id'           => '123',
						'account_name'         => 'Example Store',
						'container_id'         => '456',
						'container_name'       => 'Example Store - Web',
						'container_public_id'  => 'GTM-ABCDEFG',
						'pending_container_id' => null,
					],
				],
				[ OptionsInterface::TAG_MANAGER, $this->arrayHasKey( 'ads_conversion_conflict' ) ]
			)
			->willReturn( true );

		$this->connection->select_container( '456' );
	}

	/**
	 * Back the mocked options object with an in-memory value, so successive reads see earlier writes.
	 *
	 * @param array $initial The stored `tag_manager` option.
	 * @param array $stored  Reference to the stored value, for assertions.
	 */
	protected function use_stored_connection_data( array $initial, array &$stored ): void {
		$stored = $initial;

		$this->options->method( 'get' )->willReturnCallback(
			function () use ( &$stored ) {
				return $stored;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( string $name, array $value ) use ( &$stored ) {
				$stored = $value;

				return true;
			}
		);
	}

	public function test_create_container_throws_when_no_account_selected() {
		$stored = [];
		$this->use_stored_connection_data( [ 'account_id' => null ], $stored );
		$this->client->expects( $this->never() )->method( 'post' );

		$this->expectException( Exception::class );
		$this->connection->create_container( 'Example Store' );
	}

	public function test_create_container_creates_a_web_container_and_connects_it() {
		$stored = [];
		$this->use_stored_connection_data(
			[
				'account_id'   => '123',
				'account_name' => 'Example Store',
			],
			$stored
		);
		$container = [
			'containerId' => '456',
			'publicId'    => 'GTM-ABCDEFG',
			'name'        => 'Example Store',
		];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'accounts/123/containers',
				[
					'name'         => 'Example Store',
					'usageContext' => [ 'web' ],
				]
			)
			->willReturn( $container );
		$this->client->method( 'get' )->willReturnMap(
			[
				[ 'accounts/123/containers/456', $container ],
				[ 'accounts/123/containers/456/versions:live', [] ],
			]
		);

		$this->assertTrue( $this->connection->create_container( 'Example Store' ) );

		$this->assertSame( '456', $stored['container_id'] );
		$this->assertSame( 'GTM-ABCDEFG', $stored['container_public_id'] );
		$this->assertNull( $stored['pending_container_id'] );
	}

	public function test_create_container_without_a_public_id_is_reported_as_an_injection_failure() {
		$stored = [];
		$this->use_stored_connection_data( [ 'account_id' => '123' ], $stored );
		$container = [
			'containerId' => '456',
			'name'        => 'Example Store',
		];

		$this->client->method( 'post' )->willReturn( $container );
		$this->client->method( 'get' )->willReturnMap(
			[
				[ 'accounts/123/containers/456', $container ],
				[ 'accounts/123/containers/456/versions:live', [] ],
			]
		);

		$this->assertTrue( $this->connection->create_container( 'Example Store' ) );

		// The container stays connected rather than being rolled back, and the existing
		// injection check is what reports it from here on.
		$this->assertSame( '456', $stored['container_id'] );
		$this->assertNull( $stored['pending_container_id'] );

		$site_tag = new TagManagerSiteTag( $this->connection, $this->createMock( Settings::class ) );
		$this->assertTrue( $site_tag->has_injection_failed() );
	}

	public function test_create_container_keeps_the_created_container_when_connecting_fails() {
		$stored = [];
		$this->use_stored_connection_data( [ 'account_id' => '123' ], $stored );

		$this->client->method( 'post' )->willReturn( [ 'containerId' => '456' ] );
		$this->client->method( 'get' )->willThrowException( new TagManagerApiException( 503, [], __METHOD__ ) );

		try {
			$this->connection->create_container( 'Example Store' );
			$this->fail( 'Expected TagManagerApiException' );
		} catch ( TagManagerApiException $e ) {
			$this->assertSame( '456', $stored['pending_container_id'] );
			$this->assertEmpty( $stored['container_id'] ?? null );
		}
	}

	public function test_create_container_retry_connects_the_already_created_container_instead_of_creating_another() {
		$stored = [];
		$this->use_stored_connection_data(
			[
				'account_id'           => '123',
				'pending_container_id' => '456',
			],
			$stored
		);
		$container = [
			'containerId' => '456',
			'publicId'    => 'GTM-ABCDEFG',
			'name'        => 'Example Store',
		];

		$this->client->expects( $this->never() )->method( 'post' );
		$this->client->method( 'get' )->willReturnMap(
			[
				[ 'accounts/123/containers/456', $container ],
				[ 'accounts/123/containers/456/versions:live', [] ],
			]
		);

		$this->assertTrue( $this->connection->create_container( 'A different name' ) );

		$this->assertSame( '456', $stored['container_id'] );
		$this->assertNull( $stored['pending_container_id'] );
	}

	public function test_create_container_failure_before_a_container_exists_stores_nothing_pending() {
		$stored = [];
		$this->use_stored_connection_data( [ 'account_id' => '123' ], $stored );

		$this->client->method( 'post' )->willThrowException( new TagManagerApiException( 403, [], __METHOD__ ) );

		try {
			$this->connection->create_container( 'Example Store' );
			$this->fail( 'Expected TagManagerApiException' );
		} catch ( TagManagerApiException $e ) {
			$this->assertSame( 403, $e->get_http_status() );
			$this->assertEmpty( $stored['pending_container_id'] ?? null );
		}
	}

	public function test_selecting_a_container_clears_a_pending_created_container() {
		$stored = [];
		$this->use_stored_connection_data(
			[
				'account_id'           => '123',
				'pending_container_id' => '456',
			],
			$stored
		);
		$this->client->method( 'get' )->willReturnMap(
			[
				[
					'accounts/123/containers/789',
					[
						'containerId' => '789',
						'publicId'    => 'GTM-OTHER',
						'name'        => 'Other',
					],
				],
				[ 'accounts/123/containers/789/versions:live', [] ],
			]
		);

		$this->connection->select_container( '789' );

		$this->assertNull( $stored['pending_container_id'] );
	}

	public function test_has_ads_conversion_conflict_only_when_the_last_check_found_one() {
		$this->options->method( 'get' )->willReturnOnConsecutiveCalls(
			[ 'ads_conversion_conflict' => true ],
			[ 'ads_conversion_conflict' => false ],
			[ 'ads_conversion_conflict' => null ],
			[]
		);

		$this->assertTrue( $this->connection->has_ads_conversion_conflict() );
		$this->assertFalse( $this->connection->has_ads_conversion_conflict() );
		$this->assertFalse( $this->connection->has_ads_conversion_conflict() );
		$this->assertFalse( $this->connection->has_ads_conversion_conflict() );
	}

	public function test_refresh_ads_conversion_conflict_throws_without_a_container() {
		$this->options->method( 'get' )->willReturn( [ 'account_id' => '123' ] );
		$this->client->expects( $this->never() )->method( 'get' );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->expectException( Exception::class );
		$this->connection->refresh_ads_conversion_conflict();
	}

	/**
	 * @dataProvider live_version_tags_provider
	 *
	 * @param array $live_version The live container version response.
	 * @param bool  $expected     Whether a conflict should be found.
	 */
	public function test_refresh_ads_conversion_conflict_reads_the_live_version( array $live_version, bool $expected ) {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'   => '123',
				'container_id' => '456',
			]
		);
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'accounts/123/containers/456/versions:live' )
			->willReturn( $live_version );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::TAG_MANAGER,
				[
					'account_id'              => '123',
					'container_id'            => '456',
					'ads_conversion_conflict' => $expected,
				]
			)
			->willReturn( true );

		$this->assertSame( $expected, $this->connection->refresh_ads_conversion_conflict() );
	}

	/**
	 * @return array
	 */
	public function live_version_tags_provider(): array {
		return [
			'no tags'                         => [ [ 'containerVersionId' => '1' ], false ],
			'empty tag list'                  => [ [ 'tag' => [] ], false ],
			'active conversion tag'           => [ [ 'tag' => [ [ 'type' => 'awct' ] ] ], true ],
			'paused conversion tag'           => [
				[
					'tag' => [
						[
							'type'   => 'awct',
							'paused' => true,
						],
					],
				],
				false,
			],
			'Google tag with an Ads ID only'  => [ [ 'tag' => [ [ 'type' => 'googtag' ] ] ], false ],
			'conversion tag among other tags' => [ [ 'tag' => [ [ 'type' => 'googtag' ], [ 'type' => 'html' ], [ 'type' => 'awct' ] ] ], true ],
		];
	}

	public function test_refresh_ads_conversion_conflict_treats_a_never_published_container_as_no_conflict() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'   => '123',
				'container_id' => '456',
			]
		);
		$this->client->method( 'get' )->willThrowException(
			new TagManagerApiException( 404, [ 'error' => [ 'message' => 'Not found' ] ], __METHOD__ )
		);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::TAG_MANAGER, $this->callback( fn( $data ) => false === $data['ads_conversion_conflict'] ) )
			->willReturn( true );

		$this->assertFalse( $this->connection->refresh_ads_conversion_conflict() );
	}

	/**
	 * @dataProvider check_failure_provider
	 *
	 * @param Exception $exception The failure the API client throws.
	 */
	public function test_refresh_ads_conversion_conflict_keeps_the_stored_result_when_check_fails( Exception $exception ) {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'              => '123',
				'container_id'            => '456',
				'ads_conversion_conflict' => true,
			]
		);
		$this->client->method( 'get' )->willThrowException( $exception );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->expectExceptionObject( $exception );
		$this->connection->refresh_ads_conversion_conflict();
	}

	/**
	 * @return array
	 */
	public function check_failure_provider(): array {
		return [
			'API error'         => [ new TagManagerApiException( 403, [ 'error' => [ 'message' => 'Forbidden' ] ], __METHOD__ ) ],
			'transport failure' => [ new Exception( 'cURL error 28: Operation timed out' ) ],
		];
	}

	/**
	 * @dataProvider select_container_check_provider
	 *
	 * @param array|Exception $live_version The live version response, or the failure the API client throws.
	 * @param bool|null       $expected     The conflict result stored after selecting the container.
	 */
	public function test_select_container_checks_the_new_container_for_a_conflict( $live_version, ?bool $expected ) {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'   => '123',
				'container_id' => '456',
			]
		);
		$this->client->method( 'get' )->willReturnCallback(
			function ( string $path ) use ( $live_version ) {
				if ( 'accounts/123/containers/456' === $path ) {
					return [
						'containerId' => '456',
						'publicId'    => 'GTM-ABCDEFG',
						'name'        => 'Example Store - Web',
					];
				}

				if ( $live_version instanceof Exception ) {
					throw $live_version;
				}

				return $live_version;
			}
		);

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::TAG_MANAGER, $this->arrayHasKey( 'container_public_id' ) ],
				[ OptionsInterface::TAG_MANAGER, $this->callback( fn( $data ) => $expected === $data['ads_conversion_conflict'] ) ]
			)
			->willReturn( true );

		$this->assertTrue( $this->connection->select_container( '456' ) );
	}

	/**
	 * @return array
	 */
	public function select_container_check_provider(): array {
		return [
			'conflict found'             => [ [ 'tag' => [ [ 'type' => 'awct' ] ] ], true ],
			'no conflict'                => [ [ 'tag' => [] ], false ],
			'never published'            => [ new TagManagerApiException( 404, [], __METHOD__ ), false ],
			'check fails, so fails open' => [ new TagManagerApiException( 500, [], __METHOD__ ), null ],
		];
	}

	public function test_select_container_logs_a_failed_check() {
		$this->options->method( 'get' )->willReturn(
			[
				'account_id'   => '123',
				'container_id' => '456',
			]
		);
		$failure = new Exception( 'cURL error 28: Operation timed out' );
		$this->client->method( 'get' )->willReturnCallback(
			function ( string $path ) use ( $failure ) {
				if ( 'accounts/123/containers/456' === $path ) {
					return [
						'containerId' => '456',
						'publicId'    => 'GTM-ABCDEFG',
						'name'        => 'Example Store - Web',
					];
				}

				throw $failure;
			}
		);
		$this->options->method( 'update' )->willReturn( true );

		$logged = [];
		add_action(
			'woocommerce_gla_exception',
			function ( $exception ) use ( &$logged ) {
				$logged[] = $exception;
			}
		);

		$this->connection->select_container( '456' );

		$this->assertSame( [ $failure ], $logged );
	}
}
