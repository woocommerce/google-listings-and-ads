<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

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
		// Get string representation of the handler stack and confirm if `plugin_version_header` is contained within it.
		$this->assertStringContainsString( 'plugin_version_header', (string) $this->container->get( Client::class )->getConfig( 'handler' ) );
	}

	/**
	 * Confirm that `add_plugin_version_header` adds the correct headers to the request.
	 *
	 * @return void
	 */
	public function test_plugin_version_headers(): void {
		$service = new GoogleServiceProvider();
		$request = new Request( 'GET', 'https://testing.local' );

		$service->add_plugin_version_header()(
			function( $request, $options ) {
				$this->assertEquals( $this->get_client_name(), $request->getHeader( 'x-client-name' )[0] );
				$this->assertEquals( $this->get_version(), $request->getHeader( 'x-client-version' )[0] );
			}
		)( $request, [] );

	}
}
