<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerificationException;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;
use Google\Service\Exception as GoogleServiceException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class PhoneVerificationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @property MockObject|Merchant   $merchant
 * @property MockObject|WP         $wp
 * @property MockObject|ISOUtility $iso_utility
 * @property PhoneVerification     $phone_verification
 */
class PhoneVerificationTest extends UnitTest {

	public function test_request_phone_verification() {
		$this->iso_utility->expects( $this->any() )
						  ->method( 'wp_locale_to_bcp47' )
						  ->willReturn( 'fr-CA' );
		$this->merchant->expects( $this->any() )
					   ->method( 'request_phone_verification' )
					   ->with( $this->anything(), $this->anything(), $this->anything(), $this->equalTo( 'fr-CA' ) )
					   ->willReturn( 'some_verification_id' );
		$this->iso_utility->expects( $this->any() )
						  ->method( 'is_iso3166_alpha2_country_code' )
						  ->willReturn( true );

		$this->assertEquals(
			'some_verification_id',
			$this->phone_verification->request_phone_verification( 'US', new PhoneNumber( '8772733049' ), PhoneVerification::VERIFICATION_METHOD_SMS )
		);
	}

	public function test_request_phone_verification_invalid_verification_method() {
		$this->iso_utility->expects( $this->any() )
						  ->method( 'is_iso3166_alpha2_country_code' )
						  ->willReturn( true );

		$this->expectException( InvalidValue::class );

		$this->phone_verification->request_phone_verification( 'US', new PhoneNumber( '8772733049' ), 'SOME_NON_EXISTING_METHOD' );
	}

	public function test_request_phone_verification_invalid_phone_region() {
		$this->iso_utility->expects( $this->any() )
						  ->method( 'is_iso3166_alpha2_country_code' )
						  ->willReturn( false );

		$this->expectException( InvalidValue::class );

		$this->phone_verification->request_phone_verification( 'NaN', new PhoneNumber( '8772733049' ), PhoneVerification::VERIFICATION_METHOD_SMS );
	}

	public function test_map_google_exception() {
		$this->iso_utility->expects( $this->any() )
						  ->method( 'is_iso3166_alpha2_country_code' )
						  ->willReturn( true );

		$google_exception = new GoogleServiceException(
			'Retries quota exceeded.',
			429,
			null,
			[
				[
					'message' => 'Retries quota exceeded.',
					'domain'  => 'global',
					'reason'  => 'rateLimitExceeded',
				],
			]
		);
		$this->merchant->expects( $this->any() )
					   ->method( 'request_phone_verification' )
					   ->willThrowException( $google_exception );

		$this->expectException( PhoneVerificationException::class );
		$this->expectExceptionMessage( 'Retries quota exceeded.' );
		$this->expectExceptionCode( 429 );

		try {
			$this->phone_verification->request_phone_verification( 'US', new PhoneNumber( '8772733049' ), PhoneVerification::VERIFICATION_METHOD_SMS );
		} catch ( PhoneVerificationException $exception ) {
			$this->assertEquals( 'rateLimitExceeded', $exception->get_response_data()['reason'] );
			throw $exception;
		}
	}

	public function test_verify_phone_number() {
		$this->expectNotToPerformAssertions();

		$this->phone_verification->verify_phone_number( 'some_verification_id', '123456', PhoneVerification::VERIFICATION_METHOD_PHONE_CALL );
	}

	public function test_verify_phone_number_invalid_verification_method() {
		$this->expectException( InvalidValue::class );

		$this->phone_verification->verify_phone_number( 'some_verification_id', '123456', 'SOME_NON_EXISTING_METHOD' );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant           = $this->createMock( Merchant::class );
		$this->wp                 = $this->createMock( WP::class );
		$this->iso_utility        = $this->createMock( ISOUtility::class );
		$this->phone_verification = new PhoneVerification( $this->merchant, $this->wp, $this->iso_utility );
	}
}
