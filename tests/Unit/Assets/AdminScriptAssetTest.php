<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use PHPUnit\Framework\TestCase;

class AdminScriptAssetTest extends TestCase {

	public function test_missing_file_is_reported_and_does_not_throw() {
		$exception = null;
		$listener  = static function ( $thrown ) use ( &$exception ) {
			$exception = $thrown;
		};
		add_action( 'woocommerce_gla_exception', $listener );

		$asset = new AdminScriptAsset( __FUNCTION__, 'foo' );

		remove_action( 'woocommerce_gla_exception', $listener );
		$this->assertInstanceOf( InvalidAsset::class, $exception );
		$this->assertStringEndsWith( '/foo.js', $asset->get_uri() );
	}

	public function test_asset_handle_and_uri() {
		$path  = 'tests/data/foo';
		$asset = new AdminScriptAsset( __FUNCTION__, $path );
		$this->assertEquals( __FUNCTION__, $asset->get_handle() );
		$this->assertEquals(
			plugins_url( "{$path}.min.js", dirname( __DIR__, 3 ) . '/google-listings-and-ads.php' ),
			$asset->get_uri()
		);
	}
}
