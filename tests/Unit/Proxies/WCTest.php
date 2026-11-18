<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WC_Countries;
use WC_Session;

/**
 * Class WCTest
 *
 * @group Proxies
 */
class WCTest extends UnitTest {

	/** @var WC_Session|null */
	private $original_session;

	/** @var WC */
	private $wc;

	public function setUp(): void {
		parent::setUp();
		$this->original_session = WC()->session;
		$this->wc               = new WC( $this->createMock( WC_Countries::class ) );
	}

	public function tearDown(): void {
		WC()->session = $this->original_session;
		parent::tearDown();
	}

	public function test_get_customer_details_returns_session_customer() {
		$customer = [ 'email' => 'customer@example.org' ];

		$session = $this->createMock( WC_Session::class );
		$session->expects( $this->once() )
			->method( 'get' )
			->with( 'customer', [] )
			->willReturn( $customer );
		WC()->session = $session;

		$this->assertSame( $customer, $this->wc->get_customer_details() );
	}

	public function test_get_customer_details_returns_empty_array_when_session_is_not_loaded() {
		WC()->session = null;

		$this->assertSame( [], $this->wc->get_customer_details() );
	}
}
