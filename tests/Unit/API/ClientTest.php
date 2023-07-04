<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\RequestInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use ReflectionClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class ClientTest
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API
 */
class ClientTest extends ContainerAwareUnitTest {
	use PluginHelper;

	/**
	 * Confirm that the client handler stack includes the `plugin_version_header
	 *
	 * @return void
	 */
	public function test_plugin_version_header_in_handler_stack(): void {
		$client     = $this->container->get( Client::class );
		$handler    = $client->getConfig( 'handler' );
		$reflection = new ReflectionClass( $handler );

		$property = $reflection->getProperty( 'stack' );
		$property->setAccessible( true );

		$handler_stack = $property->getValue( $handler );

		$this->assertNotFalse( array_search( 'plugin_version_header', array_column( $handler_stack, 1 ), true ) );
	}

	/**
	 * Confirm that withHeader is called on RequestInterface in
	 * add_plugin_version_header to set the x-client-name header.
	 *
	 * @return void
	 */
	public function test_plugin_version_headers(): void {
		$service = new GoogleServiceProvider();

		$request = $this->createMock( RequestInterface::class );
		$request->expects( $this->once() )
				->method( 'withHeader' )
				->withConsecutive( [ 'x-client-name', $this->get_client_name() ] );

		$service->add_plugin_version_header()( function(){ } )( $request, [] );
	}
}
