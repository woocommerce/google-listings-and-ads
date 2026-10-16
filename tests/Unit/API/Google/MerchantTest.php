<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountBusinessInfoService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountHomepageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountServicesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountUsersService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiIssueResolutionService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Account;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\RequestPhoneVerificationResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Accounts;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Accountstatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Products;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\VerifyPhoneNumberResponse;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class MerchantTest extends UnitTest {

	use MerchantTrait;

	/** @var MockObject|ShoppingContent $service */
	protected $service;

	/** @var MockObject|MapiAccountHomepageService $homepage_service */
	protected $homepage_service;

	/** @var MockObject|MapiAccountBusinessInfoService $business_info_service */
	protected $business_info_service;

	/** @var MockObject|MapiAccountUsersService $users_service */
	protected $users_service;

	/** @var MockObject|MapiAccountServicesService $services_service */
	protected $services_service;

	/** @var MockObject|MapiIssueResolutionService $issue_resolution_service */
	protected $issue_resolution_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var Merchant $merchant */
	protected $merchant;

	/** @var int $merchant_id */
	protected $merchant_id;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->service           = $this->createMock( ShoppingContent::class );
		$this->service->accounts = $this->createMock( Accounts::class );
		$this->service->products = $this->createMock( Products::class );

		$this->homepage_service         = $this->createMock( MapiAccountHomepageService::class );
		$this->business_info_service    = $this->createMock( MapiAccountBusinessInfoService::class );
		$this->users_service            = $this->createMock( MapiAccountUsersService::class );
		$this->services_service         = $this->createMock( MapiAccountServicesService::class );
		$this->issue_resolution_service = $this->createMock( MapiIssueResolutionService::class );
		$this->options                  = $this->createMock( OptionsInterface::class );
		$this->merchant                 = new Merchant( $this->service, $this->homepage_service, $this->business_info_service, $this->users_service, $this->services_service, $this->issue_resolution_service );
		$this->merchant->set_options_object( $this->options );

		$this->merchant_id = 12345;
		$this->options->method( 'get_merchant_id' )->willReturn( $this->merchant_id );
	}

	public function test_claim_website() {
		$this->homepage_service->expects( $this->once() )
			->method( 'claim' )
			->willReturn( [ 'claimed' => true ] );
		$this->assertTrue( $this->merchant->claimwebsite() );
	}

	public function test_claimwebsite_error() {
		$this->homepage_service->expects( $this->once() )
			->method( 'claim' )
			->willThrowException( $this->merchant_api_exception( 500 ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 500 );
		$this->merchant->claimwebsite();
	}

	public function test_website_already_claimed() {
		$this->homepage_service->expects( $this->once() )
			->method( 'claim' )
			->willThrowException( $this->merchant_api_exception( 403 ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );
		$this->merchant->claimwebsite();
	}

	public function test_website_claim_conflict() {
		$body = [
			'error' => [
				'code'    => 400,
				'status'  => 'FAILED_PRECONDITION',
				'message' => 'The homepage is already claimed by another account.',
			],
		];

		$this->homepage_service->expects( $this->once() )
			->method( 'claim' )
			->willThrowException( $this->merchant_api_exception( 400, $body ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );
		$this->expectExceptionMessage( 'Website already claimed, use overwrite to complete the process.' );
		$this->merchant->claimwebsite();
	}

	public function test_claimwebsite_non_conflict_400_error() {
		$body = [
			'error' => [
				'code'    => 400,
				'status'  => 'INVALID_ARGUMENT',
				'message' => 'Request contains an invalid argument.',
			],
		];

		$this->homepage_service->expects( $this->once() )
			->method( 'claim' )
			->willThrowException( $this->merchant_api_exception( 400, $body ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to claim website.' );
		$this->merchant->claimwebsite();
	}

	public function test_request_phone_verification() {
		$this->service->accounts->expects( $this->once() )
								->method( 'requestphoneverification' )
								->willReturn( new RequestPhoneVerificationResponse( [ 'verificationId' => 'some_verification_id' ] ) );
		$this->assertEquals(
			'some_verification_id',
			$this->merchant->request_phone_verification( 'US', '8772733049', 'SMS' )
		);
	}

	public function test_request_phone_verification_throws_exception() {
		$this->service->accounts->expects( $this->once() )
								->method( 'requestphoneverification' )
								->willThrowException( new GoogleServiceException( 'Internal error!' ) );
		$this->expectException( GoogleServiceException::class );
		$this->merchant->request_phone_verification( 'US', '8772733049', 'SMS' );
	}

	public function test_verify_phone_number() {
		$this->service->accounts->expects( $this->once() )
								->method( 'verifyphonenumber' )
								->willReturn( new VerifyPhoneNumberResponse( [ 'verifiedPhoneNumber' => '8772733049' ] ) );
		$this->assertEquals(
			'8772733049',
			$this->merchant->verify_phone_number( 'some_verification_id', '123456', 'SMS' )
		);
	}

	public function test_verify_phone_number_throws_exception() {
		$this->service->accounts->expects( $this->once() )
								->method( 'verifyphonenumber' )
								->willThrowException( new GoogleServiceException( 'Internal error!' ) );
		$this->expectException( GoogleServiceException::class );
		$this->merchant->verify_phone_number( 'some_verification_id', '123456', 'SMS' );
	}

	public function test_get_account() {
		$account = $this->createMock( Account::class );
		$this->mock_get_account( $account );

		$this->assertEquals( $account, $this->merchant->get_account() );
	}

	public function test_get_account_failure() {
		$this->mock_get_account_exception( $this->get_google_service_exception() );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to retrieve Merchant Center account: Invalid query' );
		$this->merchant->get_account();
	}

	public function test_get_account_failure_with_empty_or_null_errors_from_shopping_content_service() {
		$exception = new GoogleServiceException( 'response body', 500, null, [] );
		$this->mock_get_account_exception( $exception );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 500 );
		$this->expectExceptionMessage( 'Unable to retrieve Merchant Center account: An unknown error occurred in the Shopping Content Service.' );
		$this->merchant->get_account();

		$exception = new GoogleServiceException( 'response body', 500, null, null );
		$this->mock_get_account_exception( $exception );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 500 );
		$this->expectExceptionMessage( 'Unable to retrieve Merchant Center account: An unknown error occurred in the Shopping Content Service.' );
		$this->merchant->get_account();
	}

	public function test_get_account_failure_with_unexpected_error_data_structure_from_shopping_content_service() {
		// The `reason` field is not existing
		$error     = [
			'error_code' => 'invalid',
			'message'    => '12345',
		];
		$exception = new GoogleServiceException( 'response body', 400, null, [ $error ] );
		$this->mock_get_account_exception( $exception );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to retrieve Merchant Center account: 12345' );
		$this->merchant->get_account();

		// The `message` field is not existing
		$error     = [
			'reason' => 'invalid',
			'msg'    => '12345',
		];
		$exception = new GoogleServiceException( 'response body', 400, null, [ $error ] );
		$this->mock_get_account_exception( $exception );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to retrieve Merchant Center account: An unknown error occurred in the Shopping Content Service.' );
		$this->merchant->get_account();
	}

	public function test_get_claimed_url_hash_from_cache() {
		$url = 'https://site.test';
		$this->options->method( 'get' )
			->with( OptionsInterface::CLAIMED_URL_HASH )
			->willReturn( md5( $url ) );

		$this->assertEquals( md5( $url ), $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_not_claimed() {
		$url = 'https://site.test';
		$this->homepage_service->method( 'get_homepage' )
			->willReturn(
				[
					'uri'     => $url,
					'claimed' => false,
				]
			);

		$this->assertNull( $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_from_account() {
		$url = 'https://site.test';
		$this->homepage_service->method( 'get_homepage' )
			->willReturn(
				[
					'uri'     => $url,
					'claimed' => true,
				]
			);

		$this->assertEquals( md5( $url ), $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_with_trailing_slash() {
		$url = 'https://site.test';
		$this->homepage_service->method( 'get_homepage' )
			->willReturn(
				[
					'uri'     => trailingslashit( $url ),
					'claimed' => true,
				]
			);

		$this->assertEquals( md5( $url ), $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_from_account_failure() {
		$this->homepage_service->method( 'get_homepage' )
			->willThrowException( $this->merchant_api_exception( 500 ) );

		$this->assertNull( $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_accountstatuses() {
		$account_status = $this->createMock( AccountStatus::class );
		$this->mock_get_account_status( $account_status );

		$this->assertEquals( $account_status, $this->merchant->get_accountstatus() );
	}

	public function test_get_accountstatus_failure() {
		$this->mock_get_account_status_exception( new GoogleException( 'error', 400 ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->merchant->get_accountstatus();
	}

	public function test_update_account() {
		$account_id = uniqid();
		$account    = $this->createMock( Account::class );

		$account->method( 'getId' )->willReturn( $account_id );

		$this->service->accounts->expects( $this->once() )
			->method( 'update' )
			->with( $account_id, $account_id, $account )
			->willReturn( $account );

		$this->assertEquals(
			$account,
			$this->merchant->update_account( $account )
		);
	}

	public function test_update_account_failure() {
		$account_id = uniqid();
		$account    = $this->createMock( Account::class );

		$account->method( 'getId' )->willReturn( $account_id );

		$this->service->accounts->expects( $this->once() )
			->method( 'update' )
			->with( $account_id, $account_id, $account )
			->will(
				$this->throwException(
					$this->get_google_service_exception( 400, 'URL ends with an invalid top-level domain name' )
				)
			);
		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to update Merchant Center account: URL ends with an invalid top-level domain name' );
		$this->merchant->update_account( $account );
	}

	public function test_link_ads_id() {
		$ads_id = 12345;

		$this->services_service->expects( $this->once() )
			->method( 'get_google_ads_link' )
			->with( $ads_id )
			->willReturn( null );

		$this->services_service->expects( $this->once() )
			->method( 'propose_google_ads_link' )
			->with( $ads_id )
			->willReturn( [ 'handshake' => [ 'approvalState' => 'PENDING' ] ] );

		$this->assertTrue( $this->merchant->link_ads_id( $ads_id ) );
	}

	public function test_link_ads_id_propose_established() {
		$ads_id = 12345;

		$this->services_service->method( 'get_google_ads_link' )->willReturn( null );

		$this->services_service->expects( $this->once() )
			->method( 'propose_google_ads_link' )
			->with( $ads_id )
			->willReturn( [ 'handshake' => [ 'approvalState' => 'ESTABLISHED' ] ] );

		$this->assertFalse( $this->merchant->link_ads_id( $ads_id ) );
	}

	public function test_link_ads_id_exist_link_awaiting_approval() {
		$ads_id = 12345;

		$this->services_service->expects( $this->once() )
			->method( 'get_google_ads_link' )
			->with( $ads_id )
			->willReturn( [ 'handshake' => [ 'approvalState' => 'PENDING' ] ] );

		$this->services_service->expects( $this->never() )->method( 'propose_google_ads_link' );

		$this->assertTrue( $this->merchant->link_ads_id( $ads_id ) );
	}

	public function test_ads_id_already_linked() {
		$ads_id = 12345;

		$this->services_service->expects( $this->once() )
			->method( 'get_google_ads_link' )
			->with( $ads_id )
			->willReturn( [ 'handshake' => [ 'approvalState' => 'ESTABLISHED' ] ] );

		$this->services_service->expects( $this->never() )->method( 'propose_google_ads_link' );

		$this->assertFalse( $this->merchant->link_ads_id( $ads_id ) );
	}

	public function test_link_ads_id_translates_exception() {
		$body = [
			'error' => [
				'code'    => 500,
				'message' => 'Internal error',
				'status'  => 'INTERNAL',
			],
		];
		$this->services_service->method( 'get_google_ads_link' )
			->willThrowException( new MerchantApiException( 500, $body, __METHOD__ ) );

		try {
			$this->merchant->link_ads_id( 12345 );
			$this->fail( 'Expected ExceptionWithResponseData to be thrown.' );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertSame( 500, $e->getCode() );
			$this->assertSame( $body, $e->get_response_data() );
		}
	}

	public function test_get_business_info() {
		$business_info = [
			'name'    => 'accounts/12345/businessInfo',
			'address' => [ 'regionCode' => 'US' ],
		];

		$this->business_info_service->expects( $this->once() )
			->method( 'get_business_info' )
			->willReturn( $business_info );

		$this->assertSame( $business_info, $this->merchant->get_business_info() );
	}

	public function test_update_business_info() {
		$business_info = [ 'address' => [ 'regionCode' => 'US' ] ];
		$response      = [ 'name' => 'accounts/12345/businessInfo' ];

		$this->business_info_service->expects( $this->once() )
			->method( 'update_business_info' )
			->with( $business_info, 'address' )
			->willReturn( $response );

		$this->assertSame( $response, $this->merchant->update_business_info( $business_info, 'address' ) );
	}

	public function test_has_access_to_account() {
		$email = 'john@doe.email';

		$this->users_service->expects( $this->once() )
			->method( 'get_current_user' )
			->willReturn(
				[
					'name'         => 'accounts/12345/users/' . $email,
					'accessRights' => [ 'ADMIN', 'STANDARD' ],
				]
			);

		$this->assertTrue( $this->merchant->has_access( $email ) );
	}

	public function test_no_access_when_not_admin() {
		$email = 'john@doe.email';

		$this->users_service->expects( $this->once() )
			->method( 'get_current_user' )
			->willReturn(
				[
					'name'         => 'accounts/12345/users/' . $email,
					'accessRights' => [ 'STANDARD' ],
				]
			);

		$this->assertFalse( $this->merchant->has_access( $email ) );
	}

	public function test_no_access_when_email_mismatch() {
		$this->users_service->expects( $this->once() )
			->method( 'get_current_user' )
			->willReturn(
				[
					'name'         => 'accounts/12345/users/someone@else.email',
					'accessRights' => [ 'ADMIN' ],
				]
			);

		$this->assertFalse( $this->merchant->has_access( 'john@doe.email' ) );
	}

	public function test_no_access_to_account() {
		$this->users_service->expects( $this->once() )
			->method( 'get_current_user' )
			->willThrowException( $this->merchant_api_exception( 500 ) );

		$this->assertFalse( $this->merchant->has_access( 'john@doe.email' ) );
	}

	public function test_update_merchant_id() {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MERCHANT_ID, $this->merchant_id )
			->willReturn( true );
		$this->assertTrue( $this->merchant->update_merchant_id( $this->merchant_id ) );
	}

	public function test_update_merchant_id_clears_data_source_cache_when_id_changes() {
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::MAPI_DATA_SOURCES );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MERCHANT_ID, 999 )
			->willReturn( true );

		$this->assertTrue( $this->merchant->update_merchant_id( 999 ) );
	}

	public function test_update_merchant_id_keeps_data_source_cache_when_id_unchanged() {
		// Re-saving the same account (setUp stores 12345) must not throw away still-valid cached names.
		$this->options->expects( $this->never() )->method( 'delete' );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MERCHANT_ID, $this->merchant_id )
			->willReturn( true );

		$this->assertTrue( $this->merchant->update_merchant_id( $this->merchant_id ) );
	}

	public function test_get_account_review_status() {
		$response = [
			'renderedIssues' => [
				[
					'title'   => 'Account suspended',
					'impact'  => [ 'severity' => 'ERROR' ],
					'actions' => [],
				],
			],
		];

		$this->issue_resolution_service->expects( $this->once() )
			->method( 'render_account_issues' )
			->willReturn( $response );

		$this->assertSame( $response, $this->merchant->get_account_review_status() );
	}

	public function test_get_account_review_status_exception() {
		$body = [
			'error' => [
				'code'    => 400,
				'message' => 'Some exception',
				'status'  => 'INVALID_ARGUMENT',
			],
		];

		$this->issue_resolution_service->expects( $this->once() )
			->method( 'render_account_issues' )
			->willThrowException( $this->merchant_api_exception( 400, $body ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Some exception' );
		$this->expectExceptionCode( 400 );

		$this->merchant->get_account_review_status();
	}

	public function test_trigger_review_action() {
		$response = [ 'name' => 'accounts/12345/some-action' ];

		$this->issue_resolution_service->expects( $this->once() )
			->method( 'trigger_action' )
			->with( 'action-context-token', 'review-flow', [] )
			->willReturn( $response );

		$this->assertSame( $response, $this->merchant->trigger_review_action( 'action-context-token', 'review-flow' ) );
	}

	public function test_trigger_review_action_exception() {
		$body = [
			'error' => [
				'code'    => 403,
				'message' => 'Action not allowed',
				'status'  => 'PERMISSION_DENIED',
			],
		];

		$this->issue_resolution_service->expects( $this->once() )
			->method( 'trigger_action' )
			->willThrowException( $this->merchant_api_exception( 403, $body ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Action not allowed' );
		$this->expectExceptionCode( 403 );

		$this->merchant->trigger_review_action( 'action-context-token', 'review-flow' );
	}

	private function mock_get_account( Account $account ) {
		$this->service->accounts->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->willReturn( $account );
	}

	private function mock_get_account_exception( GoogleException $exception ) {
		$this->service->accounts->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->will( $this->throwException( $exception ) );
	}

	private function mock_get_account_status( AccountStatus $account_status ) {
		$this->service->accountstatuses = $this->createMock( Accountstatuses::class );
		$this->service->accountstatuses->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->willReturn( $account_status );
	}

	private function mock_get_account_status_exception( GoogleException $exception ) {
		$this->service->accountstatuses = $this->createMock( Accountstatuses::class );
		$this->service->accountstatuses->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->will( $this->throwException( $exception ) );
	}

	private function merchant_api_exception( int $http_status, array $response_body = [] ): MerchantApiException {
		return new MerchantApiException( $http_status, $response_body, __METHOD__ );
	}
}
