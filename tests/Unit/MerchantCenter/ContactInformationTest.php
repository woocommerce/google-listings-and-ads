<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
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
 */
class ContactInformationTest extends ContainerAwareUnitTest {

	use MerchantTrait;

	/** @var MockObject|Merchant $merchant */
	protected $merchant;

	/** @var MockObject|Settings $google_settings */
	protected $google_settings;

	/** @var ContactInformation $contact_information */
	protected $contact_information;

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
			->method( 'get_business_info' )
			->willReturn( [] );

		$this->assertNull( $this->contact_information->get_contact_information() );
	}

	public function test_get_valid_contact_information() {
		$this->merchant->expects( $this->any() )
			->method( 'get_business_info' )
			->willReturn(
				[
					'name'    => 'accounts/12345/businessInfo',
					'address' => [
						'regionCode'         => 'US',
						'administrativeArea' => 'CA',
						'locality'           => 'San Francisco',
						'addressLines'       => [ '123 Main St.' ],
						'postalCode'         => '12345',
					],
				]
			);

		$this->google_settings->expects( $this->any() )
			->method( 'maybe_get_state_name' )
			->with( 'CA', 'US' )
			->willReturn( 'California' );

		$address = $this->contact_information->get_contact_information()->getAddress();

		$this->assertEquals( '12345', $address->getPostalCode() );
		$this->assertEquals( '123 Main St.', $address->getStreetAddress() );
		$this->assertEquals( 'San Francisco', $address->getLocality() );
		$this->assertEquals( 'California', $address->getRegion() );
		$this->assertEquals( 'US', $address->getCountry() );
	}

	public function test_region_code_maps_to_state_name() {
		// Use the real Settings so the actual code->name conversion (and WC state data) runs.
		$contact = new ContactInformation( $this->merchant, $this->container->get( Settings::class ) );

		$this->merchant->expects( $this->any() )
			->method( 'get_business_info' )
			->willReturn(
				[
					'name'    => 'accounts/12345/businessInfo',
					'address' => [
						'regionCode'         => 'US',
						'administrativeArea' => 'CA',
					],
				]
			);

		$this->assertEquals( 'California', $contact->get_contact_information()->getAddress()->getRegion() );
	}

	public function test_update_address() {
		$this->merchant->expects( $this->any() )
			->method( 'get_account' )
			->willReturn( $this->get_empty_account() );

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
			$this->get_sample_address()->getCountry(),
			$results->getAddress()->getCountry()
		);
	}

	public function test_maps_phone_and_verification_state() {
		$this->merchant->expects( $this->any() )
			->method( 'get_business_info' )
			->willReturn(
				[
					'name'                   => 'accounts/12345/businessInfo',
					'phone'                  => [ 'e164Number' => '+15551234567' ],
					'phoneVerificationState' => 'PHONE_VERIFICATION_STATE_VERIFIED',
				]
			);

		$contact_information = $this->contact_information->get_contact_information();

		$this->assertEquals( '+15551234567', $contact_information->getPhoneNumber() );
		$this->assertEquals( 'VERIFIED', $contact_information->getPhoneVerificationStatus() );
	}

	public function test_maps_unspecified_verification_state_to_unverified() {
		$this->merchant->expects( $this->any() )
			->method( 'get_business_info' )
			->willReturn(
				[
					'name'                   => 'accounts/12345/businessInfo',
					'phone'                  => [ 'e164Number' => '+15551234567' ],
					'phoneVerificationState' => 'PHONE_VERIFICATION_STATE_UNSPECIFIED',
				]
			);

		$this->assertEquals(
			'UNVERIFIED',
			$this->contact_information->get_contact_information()->getPhoneVerificationStatus()
		);
	}

	public function test_get_business_info_exception() {
		$this->merchant->expects( $this->any() )
			->method( 'get_business_info' )
			->willThrowException( new MerchantApiException( 500, [], __METHOD__ ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->contact_information->get_contact_information();
	}
}
