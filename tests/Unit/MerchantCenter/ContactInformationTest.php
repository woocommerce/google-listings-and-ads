<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ContactInformationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @property  MockObject|Merchant $merchant
 * @property  MockObject|Settings $google_settings
 * @property  ContactInformation  $contact_information
 */
class ContactInformationTest extends ContainerAwareUnitTest {

	use MerchantTrait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant            = $this->createMock( Merchant::class );
		$this->google_settings     = $this->createMock( Settings::class );
		$this->contact_information = new ContactInformation( $this->merchant, $this->google_settings );
	}

	public function test_get_empty_contact_information() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_empty_account() );

		$this->assertNull( $this->contact_information->get_contact_information() );
	}

	public function test_get_valid_contact_information() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$contact_information = $this->contact_information->get_contact_information();

		$this->assertEquals(
			$this->valid_account_phone_number,
			$contact_information->getPhoneNumber()
		);

		$this->assertEquals(
			$this->get_sample_address()->getPostalCode(),
			$contact_information->getAddress()->getPostalCode()
		);
		$this->assertEquals(
			$this->get_sample_address()->getStreetAddress(),
			$contact_information->getAddress()->getStreetAddress()
		);
		$this->assertEquals(
			$this->get_sample_address()->getLocality(),
			$contact_information->getAddress()->getLocality()
		);
		$this->assertEquals(
			$this->get_sample_address()->getRegion(),
			$contact_information->getAddress()->getRegion()
		);
		$this->assertEquals(
			$this->get_sample_address()->getCountry(),
			$contact_information->getAddress()->getCountry()
		);
	}

	public function test_update_address() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$this->google_settings->expects( $this->any() )
							  ->method( 'get_store_address' )
							  ->willReturn( $this->get_sample_address() );

		$results = $this->contact_information->update_address_based_on_store_settings();

		$this->assertEquals(
			$this->get_sample_address()->getPostalCode(),
			$results->getAddress()->getPostalCode()
		);
		$this->assertEquals(
			$this->get_sample_address()->getStreetAddress(),
			$results->getAddress()->getStreetAddress()
		);
		$this->assertEquals(
			$this->get_sample_address()->getLocality(),
			$results->getAddress()->getLocality()
		);
		$this->assertEquals(
			$this->get_sample_address()->getRegion(),
			$results->getAddress()->getRegion()
		);
		$this->assertEquals(
			$this->get_sample_address()->getCountry(),
			$results->getAddress()->getCountry()
		);
	}

	public function test_get_account_exception() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willThrowException( $this->get_account_exception() );

		$this->expectExceptionObject( $this->get_account_exception() );
		$this->contact_information->get_contact_information();
	}
}
