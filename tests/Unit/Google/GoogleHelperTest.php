<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class GoogleHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|WC $wc
 * @property GoogleHelper  $google_helper
 */
class GoogleHelperTest extends UnitTest {

	public function test_get_mc_supported_countries_currencies() {
		$supported = $this->google_helper->get_mc_supported_countries_currencies();
		$this->assertNotEmpty( $supported );
		$this->assertArrayHasKey( 'US', $supported );
		$this->assertEquals( 'USD', $supported['US'] );

		// Verify South Korea is not included if the store currency is not KRW.
		$this->assertArrayNotHasKey( 'KR', $supported );
	}

	public function test_get_mc_supported_countries_currencies_includes_south_korea_if_currency_is_KRW() {
		$wc = $this->createMock( WC::class );
		$wc->expects( $this->any() )
		   ->method( 'get_woocommerce_currency' )
		   ->willReturn( 'KRW' );
		$google_helper = new GoogleHelper( $wc );

		$supported = $google_helper->get_mc_supported_countries_currencies();
		$this->assertArrayHasKey( 'KR', $supported );
		$this->assertEquals( 'KRW', $supported['KR'] );
	}

	public function test_get_mc_supported_countries() {
		$supported = $this->google_helper->get_mc_supported_countries();
		$this->assertNotEmpty( $supported );
		$this->assertContains( 'US', $supported );
	}

	public function test_get_mc_promotion_supported_countries() {
		$supported = $this->google_helper->get_mc_promotion_supported_countries();
		$this->assertNotEmpty( $supported );
		$this->assertContains( 'US', $supported );
	}

	public function test_get_mc_supported_languages() {
		$supported = $this->google_helper->get_mc_supported_languages();
		$this->assertNotEmpty( $supported );
		$this->assertArrayHasKey( 'en', $supported );
		$this->assertEquals( 'en', $supported['en'] );
	}

	public function test_is_country_supported() {
		$this->assertTrue( $this->google_helper->is_country_supported('US') );
		$this->assertFalse( $this->google_helper->is_country_supported('XX') );
	}

	public function test_find_location_code_by_id() {
		// Country code.
		$this->assertEquals( 'US', $this->google_helper->find_location_code_by_id( 2840 ) );

		// Subdivision location code.
		$this->assertEquals( 'OR', $this->google_helper->find_location_code_by_id(21170) );
	}

	public function test_find_location_id_by_country_code() {
		$this->assertEquals( 2840, $this->google_helper->find_location_id_by_country_code( 'US' ) );
	}

	public function test_find_location_id_by_subdivision_code() {
		// With no country code provided.
		$this->assertEquals( 21170, $this->google_helper->find_location_id_by_subdivision_code( 'OR' ) );
		$this->assertEquals( 20035, $this->google_helper->find_location_id_by_subdivision_code( 'NSW' ) );

		// With a country code provided.
		$this->assertEquals( 21170, $this->google_helper->find_location_id_by_subdivision_code( 'OR', 'US' ) );
		$this->assertEquals( 20035, $this->google_helper->find_location_id_by_subdivision_code( 'NSW', 'AU' ) );

		// With an invalid country code provided.
		$this->assertNull( $this->google_helper->find_location_id_by_subdivision_code( 'NSW', 'US' ) );
		$this->assertNull( $this->google_helper->find_location_id_by_subdivision_code( 'NSW', 'XX' ) );

		// If there is no matching location code.
		$this->assertNull( $this->google_helper->find_location_id_by_subdivision_code( 'XX' ) );
		$this->assertNull( $this->google_helper->find_location_id_by_subdivision_code( 'XX', 'US' ) );

		// If there are no subdivision codes for the country code.
		$this->assertNull( $this->google_helper->find_location_id_by_subdivision_code( 'OR', 'LB' ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->wc = $this->createMock( WC::class );
		$this->wc->expects( $this->any() )
				 ->method( 'get_woocommerce_currency' )
				 ->willReturn( 'USD' );

		$this->google_helper = new GoogleHelper( $this->wc );
	}
}
