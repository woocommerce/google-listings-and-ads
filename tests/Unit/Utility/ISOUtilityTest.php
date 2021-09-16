<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;

/**
 * Class ISOUtilityTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility
 *
 * @property ISOUtility $iso_utility
 */
class ISOUtilityTest extends ContainerAwareUnitTest {

	public function test_is_iso3166_alpha2_country_code() {
		$this->assertTrue( $this->iso_utility->is_iso3166_alpha2_country_code( 'US' ) );
		$this->assertTrue( $this->iso_utility->is_iso3166_alpha2_country_code( 'us' ) );
		$this->assertTrue( $this->iso_utility->is_iso3166_alpha2_country_code( 'CA' ) );
		$this->assertTrue( $this->iso_utility->is_iso3166_alpha2_country_code( 'ca' ) );
		$this->assertFalse( $this->iso_utility->is_iso3166_alpha2_country_code( 'USA' ) );
		$this->assertFalse( $this->iso_utility->is_iso3166_alpha2_country_code( 'usa' ) );
		$this->assertFalse( $this->iso_utility->is_iso3166_alpha2_country_code( 'Canada' ) );
		$this->assertFalse( $this->iso_utility->is_iso3166_alpha2_country_code( 'Lorem ipsum!' ) );
	}

	public function test_wp_locale_to_bcp47() {
		$this->assertEquals( 'fr-CA', $this->iso_utility->wp_locale_to_bcp47( 'fr_CA' ) );
		$this->assertEquals( 'fr', $this->iso_utility->wp_locale_to_bcp47( 'fr' ) );
		$this->assertEquals( 'fr-FR', $this->iso_utility->wp_locale_to_bcp47( 'fr-FR' ) );
		$this->assertEquals( 'de-CH-informal', $this->iso_utility->wp_locale_to_bcp47( 'de_CH_informal' ) );
		$this->assertEquals( 'french', $this->iso_utility->wp_locale_to_bcp47( 'french' ) );
		$this->assertEquals( 'ceb', $this->iso_utility->wp_locale_to_bcp47( 'ceb' ), 'For Cebuano' );
		$this->assertEquals( 'pt-PT-ao90', $this->iso_utility->wp_locale_to_bcp47( 'pt_PT_ao90' ), 'Português (AO90)' );

		// Fall back to 'en-US' if invalid or unsupported locale provided.
		$this->assertEquals( 'en-US', $this->iso_utility->wp_locale_to_bcp47( 'Lorem ipsum!' ) );
		$this->assertEquals( 'en-US', $this->iso_utility->wp_locale_to_bcp47( 'e' ) );
		$this->assertEquals( 'en-US', $this->iso_utility->wp_locale_to_bcp47( '' ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->iso_utility = $this->container->get( ISOUtility::class );
	}
}
