<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use PHPUnit\Framework\TestCase;

class AdminScriptAssetTest extends TestCase {

	public function test_invalid_path() {
		$this->expectException( InvalidAsset::class );
		$asset = new AdminScriptAsset( __FUNCTION__, 'foo' );
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
