<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MobileTrait;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

/**
 * Class MobileTraitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class MobileTraitTest extends TestCase {

	/** @var MobileTrait $trait */
	protected $trait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$this->trait = new class {
			use MobileTrait;
		};
	}

	/**
	 * Test is a WooCommerce iOS app.
	 */
	public function test_is_wc_ios() {
		$request = new WP_REST_Request();
		$request->set_header( 'User-Agent', 'Mozilla/5.0 AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wc-ios/19.7.1' );
		$this->assertTrue( $this->trait->is_wc_ios( $request ) );
		$this->assertFalse( $this->trait->is_wc_android( $request ) );
		$this->assertTrue( $this->trait->is_wc_mobile_app( $request ) );
	}

	/**
	 * Test is a WooCommerce Android app.
	 */
	public function test_is_wc_android() {
		$request = new WP_REST_Request();
		$request->set_header( 'User-Agent', 'Mozilla/5.0 (Linux; Android 10; K) Chrome/114.0.0.0 Mobile Safari/537.36 wc-android/19.7.1' );
		$this->assertTrue( $this->trait->is_wc_android( $request ) );
		$this->assertFalse( $this->trait->is_wc_ios( $request ) );
		$this->assertTrue( $this->trait->is_wc_mobile_app( $request ) );
	}

	/**
	 * Test is not a WooCommerce mobile app.
	 */
	public function test_is_not_wc_app() {
		$request = new WP_REST_Request();
		$request->set_header( 'User-Agent', 'Mozilla/5.0 AppleWebKit/605.1.15 (KHTML, like Gecko)' );
		$this->assertFalse( $this->trait->is_wc_ios( $request ) );
		$this->assertFalse( $this->trait->is_wc_android( $request ) );
		$this->assertFalse( $this->trait->is_wc_mobile_app( $request ) );
	}
}
