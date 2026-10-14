<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class ConnectionTest extends UnitTest {

	/** @var Container */
	protected $container;

	/** @var Connection */
	protected $connection;

	/** @var array Guzzle requests the connection made. */
	protected $requests = [];

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', 'https://wcs.example.com/' );

		$stack = HandlerStack::create(
			new MockHandler( [ new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.example.com/auth' ] ) ) ] )
		);
		$stack->push( Middleware::history( $this->requests ) );
		$this->container->add( Client::class, new Client( [ 'handler' => $stack ] ) );

		$this->connection = new Connection();
		$this->connection->set_container( $this->container );
		$this->connection->set_options_object( $this->createMock( OptionsInterface::class ) );
	}

	/**
	 * Register a Tag Manager connection holding the given data.
	 *
	 * @param array $data
	 *
	 * @return MockObject|TagManagerConnection
	 */
	protected function register_tag_manager_connection( array $data ) {
		$tag_manager = $this->createMock( TagManagerConnection::class );
		$tag_manager->method( 'get_connection_data' )->willReturn( $data );
		$this->container->add( TagManagerConnection::class, $tag_manager );

		return $tag_manager;
	}

	/**
	 * @return array The decoded body of the request the connection sent.
	 */
	protected function get_sent_body(): array {
		return json_decode( (string) $this->requests[0]['request']->getBody(), true );
	}

	public function test_disconnect_disconnects_tag_manager() {
		$tag_manager = $this->register_tag_manager_connection( [ 'account_id' => '123' ] );
		$tag_manager->expects( $this->once() )->method( 'disconnect' );

		$this->connection->disconnect();
	}

	public function test_disconnect_works_when_tag_manager_is_not_registered() {
		$this->assertNotEmpty( $this->connection->disconnect() );
	}

	public function test_connect_keeps_the_tag_manager_scope_while_a_container_is_connected() {
		$tag_manager = $this->register_tag_manager_connection(
			[
				'account_id'   => '123',
				'container_id' => '456',
			]
		);
		$tag_manager->expects( $this->never() )->method( 'connect' );

		$this->connection->connect( 'https://example.com/return' );

		$this->assertSame( [ TagManagerConnection::SCOPE_TAG_MANAGER ], $this->get_sent_body()['additionalScopes'] );
	}

	public function test_connect_goes_through_the_tag_manager_connection_while_an_account_has_no_container() {
		$tag_manager = $this->register_tag_manager_connection( [ 'account_id' => '123' ] );
		$tag_manager->expects( $this->once() )
			->method( 'connect' )
			->with( 'https://example.com/return', 'merchant@example.com' )
			->willReturn( 'https://accounts.example.com/tag-manager' );

		$url = $this->connection->connect( 'https://example.com/return', 'merchant@example.com' );

		$this->assertSame( 'https://accounts.example.com/tag-manager', $url );
		$this->assertEmpty( $this->requests );
	}

	public function test_connect_requests_no_extra_scope_without_a_tag_manager_account() {
		$this->register_tag_manager_connection( [ 'account_id' => null ] );

		$this->connection->connect( 'https://example.com/return' );

		$this->assertArrayNotHasKey( 'additionalScopes', $this->get_sent_body() );
	}

	public function test_connect_requests_no_extra_scope_when_tag_manager_is_not_registered() {
		$this->connection->connect( 'https://example.com/return' );

		$this->assertArrayNotHasKey( 'additionalScopes', $this->get_sent_body() );
	}
}
