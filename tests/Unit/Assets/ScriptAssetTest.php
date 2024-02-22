<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\ScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use PHPUnit\Framework\TestCase;

class ScriptAssetTest extends TestCase {
	protected const URI = 'tests/data/foo';

	public function test_can_enqueue() {
		$want_enqueue = function () {
			return true;
		};

		$dont_enqueue = function () {
			return false;
		};

		$asset = new ScriptAsset( __FUNCTION__, self::URI, [], '', $want_enqueue );
		$this->assertTrue( $asset->can_enqueue() );

		$asset = new ScriptAsset( __FUNCTION__, self::URI, [], '', $dont_enqueue );
		$this->assertFalse( $asset->can_enqueue() );

		// Can enqueue when callback isn't set
		$asset = new ScriptAsset( __FUNCTION__, self::URI, [], '' );
		$this->assertTrue( $asset->can_enqueue() );
	}
}
