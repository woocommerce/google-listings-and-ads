<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantVerificationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @since x.x.x
 *
 * @property  MockObject|Merchant  $merchant
 * @property  MockObject|Settings  $google_settings
 * @property  MerchantVerification $merchant_verification
 */
class MerchantVerificationTest extends ContainerAwareUnitTest {

	use MerchantTrait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->merchant              = $this->createMock( Merchant::class );
		$this->google_settings       = $this->createMock( Settings::class );
		$this->merchant_verification = new MerchantVerification( $this->merchant, $this->google_settings );
	}

	public function test_get_empty_contact_information() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_empty_account() );

		$this->assertNull( $this->merchant_verification->get_contact_information() );
	}

	public function test_get_valid_contact_information() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$contact_information = $this->merchant_verification->get_contact_information();

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

	public function test_update_contact_information() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$this->google_settings->expects( $this->any() )
							  ->method( 'get_store_address' )
							  ->willReturn( $this->get_sample_address() );

		$results = $this->merchant_verification->update_contact_information( $this->valid_account_phone_number );

		$this->assertEquals(
			$this->valid_account_phone_number,
			$results->getPhoneNumber()
		);

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

	public function test_update_contact_information_throws_exception_if_invalid_phone_number() {
		$this->expectException( InvalidValue::class );
		$this->merchant_verification->update_contact_information( 'The quick brown fox' );
	}

	public function test_get_account_exception() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willThrowException( $this->get_account_exception() );

		$this->expectExceptionObject( $this->get_account_exception() );
		$this->merchant_verification->get_contact_information();
	}

	public function test_sanitize_phone_number() {
		$this->assertEquals(
			$this->valid_account_phone_number,
			$this->merchant_verification->sanitize_phone_number( $this->valid_account_phone_number )
		);

		$this->assertEquals(
			'123456789',
			$this->merchant_verification->sanitize_phone_number( '(123) 45-6789' )
		);
	}

	public function test_get_phone_number_validate_callback() {
		$this->assertFalse( $this->merchant_verification->validate_phone_number( 'Bad number' ) );
		$this->assertFalse( $this->merchant_verification->validate_phone_number( '[192] 123 123' ) );
		$this->assertFalse( $this->merchant_verification->validate_phone_number( '' ) );
		$this->assertTrue( $this->merchant_verification->validate_phone_number( $this->valid_account_phone_number ) );
		$this->assertTrue( $this->merchant_verification->validate_phone_number( '197.123.5482' ) );
		$this->assertTrue( $this->merchant_verification->validate_phone_number( '+001 (197) 123-5482' ) );
	}
}
