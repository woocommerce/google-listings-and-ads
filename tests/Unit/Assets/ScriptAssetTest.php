<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\ScriptAsset;
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

	/**
	 * Confirm an exception is logged using the `woocommerce_gla_exception`
	 * action if an asset is enqueued before it is registered.
	 *
	 * @return void
	 */
	public function test_exception_logged_if_asset_enqueued_before_registration() {
		do_action( 'wp_enqueue_scripts' );

		$asset = new ScriptAsset( __FUNCTION__, self::URI, [], '', '__return_true' );
		$asset->enqueue();

		$this->assertEquals( 1, did_action( 'woocommerce_gla_exception' ) );
	}
}
