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

	public function test_find_country_code_by_id() {
		$this->assertEquals( 'US', $this->google_helper->find_country_code_by_id( 2840 ) );
	}

	public function test_find_subdivision_code_by_id() {
		$this->assertEquals( 'OR', $this->google_helper->find_subdivision_code_by_id(21170) );
	}

	public function test_find_location_id_by_country_code() {
		$this->assertEquals( 2840, $this->google_helper->find_country_id_by_code( 'US' ) );
	}

	public function test_find_location_id_by_subdivision_code() {
		$this->assertEquals( 21170, $this->google_helper->find_subdivision_id_by_code( 'OR', 'US' ) );
		$this->assertEquals( 20035, $this->google_helper->find_subdivision_id_by_code( 'NSW', 'AU' ) );

		// With an invalid country code provided.
		$this->assertNull( $this->google_helper->find_subdivision_id_by_code( 'NSW', 'US' ) );
		$this->assertNull( $this->google_helper->find_subdivision_id_by_code( 'NSW', 'XX' ) );

		// If there is no matching location code.
		$this->assertNull( $this->google_helper->find_subdivision_id_by_code( 'XX', 'US' ) );

		// If there are no subdivision codes for the country code.
		$this->assertNull( $this->google_helper->find_subdivision_id_by_code( 'OR', 'LB' ) );
	}

	public function test_returns_supported_countries_from_continent() {
		// Mock the WC_Countries class to return the list of countries for the EU continent.
		$this->wc->expects( $this->any() )
				 ->method( 'get_continents' )
				 ->willReturn( [
					 'EU' => [
						 'name'      => 'Europe',
						 'countries' => [
							 // A random country code, not supported by Merchant Center. This should be ignored.
							 'OO1',
							 // Another random country code, not supported by Merchant Center. This should be ignored.
							 'OO2',
							 // Countries supported by MC
							 'GB',
							 'FR',
							 'DE',
							 'DK',
							 // And many more ...
						 ],
					 ],
				 ] );

		$this->assertEqualSets(
			[
				'GB',
				'FR',
				'DE',
				'DK',
			],
			$this->google_helper->get_supported_countries_from_continent( 'EU' )
		);
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wc = $this->createMock( WC::class );
		$this->wc->expects( $this->any() )
				 ->method( 'get_woocommerce_currency' )
				 ->willReturn( 'USD' );

		$this->google_helper = new GoogleHelper( $this->wc );
	}
}
