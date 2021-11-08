<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PhoneVerificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerificationException;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class PhoneNumberControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @property RESTServer                   $rest_server
 * @property PhoneVerification|MockObject $phone_verification
 */
class PhoneVerificationControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_REQUEST_VERIFICATION     = '/wc/gla/mc/phone-verification/request';
	protected const ROUTE_VERIFY_PHONE             = '/wc/gla/mc/phone-verification/verify';
	protected const TEST_PHONE_NUMBER              = '0412 123 123';
	protected const TEST_VERIFICATION_ID           = 'test_verification_id';
	protected const TEST_VERIFICATION_CODE         = '123346';
	protected const TEST_REQUEST_VERIFICATION_ARGS = [
		'phone_region_code'   => 'AU',
		'phone_number'        => self::TEST_PHONE_NUMBER,
		'verification_method' => 'SMS',
	];
	protected const TEST_VERIFY_PHONE_ARGS         = [
		'verification_id'     => self::TEST_VERIFICATION_ID,
		'verification_code'   => self::TEST_VERIFICATION_CODE,
		'verification_method' => 'SMS',
	];

	public function setUp() {
		parent::setUp();

		$this->phone_verification = $this->createMock( PhoneVerification::class );
		$this->controller         = new PhoneVerificationController( $this->server, $this->phone_verification );
		$this->controller->register();
	}

	public function test_request_phone_verification() {
		$this->phone_verification->expects( $this->once() )
		                         ->method( 'request_phone_verification' )
		                         ->with( 'AU', new PhoneNumber( self::TEST_PHONE_NUMBER ), 'SMS' )
		                         ->willReturn( self::TEST_VERIFICATION_ID );

		$response = $this->do_request( self::ROUTE_REQUEST_VERIFICATION, 'POST', self::TEST_REQUEST_VERIFICATION_ARGS );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'verification_id' => self::TEST_VERIFICATION_ID ], $response->get_data() );
	}

	public function test_request_phone_verification_missing_args() {
		$response = $this->do_request( self::ROUTE_REQUEST_VERIFICATION, 'POST' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
		$this->assertCount( 3, $response->get_data()['data']['params'], 'All 3 params should be required.' );
	}

	public function test_request_phone_verification_with_invalid_value_exception() {
		$this->phone_verification->expects( $this->once() )
		                         ->method( 'request_phone_verification' )
		                         ->willThrowException( new InvalidValue( 'oops' ) );

		$response = $this->do_request( self::ROUTE_REQUEST_VERIFICATION, 'POST', self::TEST_REQUEST_VERIFICATION_ARGS );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'oops', $response->get_data()['message'] );
	}

	public function test_request_phone_verification_with_phone_verification_exception() {
		$this->phone_verification->expects( $this->once() )
		                         ->method( 'request_phone_verification' )
		                         ->willThrowException(
			                         new PhoneVerificationException( 'oops', 429, null, [ 'reason' => 'rateLimitExceeded' ] )
		                         );

		$response = $this->do_request( self::ROUTE_REQUEST_VERIFICATION, 'POST', self::TEST_REQUEST_VERIFICATION_ARGS );

		$this->assertEquals( 429, $response->get_status() );
		$this->assertEquals( 'rateLimitExceeded', $response->get_data()['reason'] );
		$this->assertEquals( 'oops', $response->get_data()['message'] );
	}

	public function test_verify_phone() {
		$this->phone_verification->expects( $this->once() )
		                         ->method( 'verify_phone_number' )
		                         ->with(
			                         self::TEST_VERIFICATION_ID,
			                         self::TEST_VERIFICATION_CODE,
			                         'SMS'
		                         );

		$response = $this->do_request( self::ROUTE_VERIFY_PHONE, 'POST', self::TEST_VERIFY_PHONE_ARGS );

		$this->assertEquals( 204, $response->get_status() );
		$this->assertNull( $response->get_data() );
	}

	public function test_verify_phone_missing_args() {
		$response = $this->do_request( self::ROUTE_VERIFY_PHONE, 'POST' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
		$this->assertCount( 3, $response->get_data()['data']['params'], 'All 3 params should be required.' );
	}

	public function test_verify_phone_with_phone_verification_exception() {
		$this->phone_verification->expects( $this->once() )
		                         ->method( 'verify_phone_number' )
		                         ->willThrowException(
			                         new PhoneVerificationException(
				                         'Wrong code.',
				                         400,
				                         null,
				                         [ 'reason' => 'badRequest' ]
			                         )
		                         );

		$response = $this->do_request( self::ROUTE_VERIFY_PHONE, 'POST', self::TEST_VERIFY_PHONE_ARGS );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals(
			[
				'message' => 'Wrong code.',
				'reason'  => 'badRequest'
			],
			$response->get_data()
		);
	}

}
