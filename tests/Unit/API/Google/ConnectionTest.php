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

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', 'https://wcs.example.com/' );
		$this->container->add(
			Client::class,
			new Client(
				[
					'handler' => HandlerStack::create(
						new MockHandler(
							[
								new Response( 200, [], wp_json_encode( [ 'oauthUrl' => 'https://accounts.example.com/auth' ] ) ),
							]
						)
					),
				]
			)
		);

		$this->connection = new Connection();
		$this->connection->set_container( $this->container );
		$this->connection->set_options_object( $this->createMock( OptionsInterface::class ) );
	}

	/**
	 * Register a Tag Manager connection that expects to be told to forget a refusal.
	 *
	 * @param int $times How many times it should be told.
	 */
	protected function expect_tag_manager_denial_forgotten( int $times ): void {
		/** @var MockObject|TagManagerConnection $tag_manager */
		$tag_manager = $this->createMock( TagManagerConnection::class );
		$tag_manager->expects( $this->exactly( $times ) )->method( 'forget_create_denial' );

		$this->container->add( TagManagerConnection::class, $tag_manager );
	}

	public function test_disconnect_forgets_a_remembered_tag_manager_refusal() {
		$this->expect_tag_manager_denial_forgotten( 1 );

		$this->connection->disconnect();
	}

	public function test_disconnect_works_when_tag_manager_is_not_registered() {
		$this->assertNotEmpty( $this->connection->disconnect() );
	}
}
