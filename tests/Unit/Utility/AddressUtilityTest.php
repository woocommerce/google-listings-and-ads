<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAddress;

/**
 * Class AddressUtilityTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility
 *
 * @property AddressUtility $address_utility
 */
class AddressUtilityTest extends UnitTest {

	public function test_returns_true_if_addresses_are_same() {
		$address_1 = new AccountAddress();
		$address_1->setCountry( 'US' );
		$address_1->setRegion( 'California' );
		$address_1->setLocality( 'San Fransisco' );
		$address_1->setStreetAddress( '123 Main St.' );
		$address_1->setPostalCode( '12345' );

		$address_2 = new AccountAddress();
		$address_2->setCountry( 'US' );
		$address_2->setRegion( 'California' );
		$address_2->setLocality( 'San Fransisco' );
		$address_2->setStreetAddress( '123 Main St.' );
		$address_2->setPostalCode( '12345' );

		$this->assertTrue( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	public function test_returns_true_if_addresses_are_both_empty() {
		$address_1 = new AccountAddress();
		$address_2 = new AccountAddress();

		$this->assertTrue( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	public function test_returns_false_if_only_country_not_the_same() {
		$address_1 = new AccountAddress();
		$address_1->setCountry( 'US' );
		$address_1->setRegion( 'California' );
		$address_1->setLocality( 'San Fransisco' );
		$address_1->setStreetAddress( '123 Main St.' );
		$address_1->setPostalCode( '12345' );

		$address_2 = new AccountAddress();
		$address_2->setCountry( 'CA' );
		$address_2->setRegion( 'California' );
		$address_2->setLocality( 'San Fransisco' );
		$address_2->setStreetAddress( '123 Main St.' );
		$address_2->setPostalCode( '12345' );

		$this->assertFalse( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	public function test_returns_false_if_only_region_not_the_same() {
		$address_1 = new AccountAddress();
		$address_1->setCountry( 'US' );
		$address_1->setRegion( 'California' );
		$address_1->setLocality( 'San Fransisco' );
		$address_1->setStreetAddress( '123 Main St.' );
		$address_1->setPostalCode( '12345' );

		$address_2 = new AccountAddress();
		$address_2->setCountry( 'US' );
		$address_2->setRegion( 'Washington' );
		$address_2->setLocality( 'San Fransisco' );
		$address_2->setStreetAddress( '123 Main St.' );
		$address_2->setPostalCode( '12345' );

		$this->assertFalse( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	public function test_returns_false_if_only_locality_not_the_same() {
		$address_1 = new AccountAddress();
		$address_1->setCountry( 'US' );
		$address_1->setRegion( 'California' );
		$address_1->setLocality( 'San Fransisco' );
		$address_1->setStreetAddress( '123 Main St.' );
		$address_1->setPostalCode( '12345' );

		$address_2 = new AccountAddress();
		$address_2->setCountry( 'US' );
		$address_2->setRegion( 'California' );
		$address_2->setLocality( 'Mountain View' );
		$address_2->setStreetAddress( '123 Main St.' );
		$address_2->setPostalCode( '12345' );

		$this->assertFalse( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	public function test_returns_false_if_only_street_address_not_the_same() {
		$address_1 = new AccountAddress();
		$address_1->setCountry( 'US' );
		$address_1->setRegion( 'California' );
		$address_1->setLocality( 'San Fransisco' );
		$address_1->setStreetAddress( '123 Main St.' );
		$address_1->setPostalCode( '12345' );

		$address_2 = new AccountAddress();
		$address_2->setCountry( 'US' );
		$address_2->setRegion( 'California' );
		$address_2->setLocality( 'San Fransisco' );
		$address_2->setStreetAddress( 'Route 123' );
		$address_2->setPostalCode( '12345' );

		$this->assertFalse( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	public function test_returns_false_if_only_postal_code_not_the_same() {
		$address_1 = new AccountAddress();
		$address_1->setCountry( 'US' );
		$address_1->setRegion( 'California' );
		$address_1->setLocality( 'San Fransisco' );
		$address_1->setStreetAddress( '123 Main St.' );
		$address_1->setPostalCode( '12345' );

		$address_2 = new AccountAddress();
		$address_2->setCountry( 'US' );
		$address_2->setRegion( 'California' );
		$address_2->setLocality( 'San Fransisco' );
		$address_2->setStreetAddress( '123 Main St.' );
		$address_2->setPostalCode( '67890' );

		$this->assertFalse( $this->address_utility->compare_addresses( $address_1, $address_2 ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->address_utility = new AddressUtility();
	}
}
