<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Account;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAdsLink;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountUser;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsListResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductstatusesCustomBatchRequest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductstatusesCustomBatchResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\RequestPhoneVerificationResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Accounts;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Accountstatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Products;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Productstatuses;
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
		$this->service                      = $this->createMock( ShoppingContent::class );
		$this->service->accounts            = $this->createMock( Accounts::class );
		$this->service->products            = $this->createMock( Products::class );
		$this->service->freelistingsprogram = $this->createMock( ShoppingContent\Resource\Freelistingsprogram::class );
		$this->service->shoppingadsprogram  = $this->createMock( ShoppingContent\Resource\Shoppingadsprogram::class );

		$this->options  = $this->createMock( OptionsInterface::class );
		$this->merchant = new Merchant( $this->service );
		$this->merchant->set_options_object( $this->options );

		$this->merchant_id = 12345;
		$this->options->method( 'get_merchant_id' )->willReturn( $this->merchant_id );
	}

	public function test_get_products_empty_list() {
		$list_response = $this->createMock( ProductsListResponse::class );

		$this->service->products->expects( $this->once() )
			->method( 'listProducts' )
			->with( $this->merchant_id )
			->willReturn( $list_response );

		$products = $this->merchant->get_products();
		$this->assertEquals( $products, [] );
	}

	public function test_get_products() {
		$list_response = $this->createMock( ProductsListResponse::class );

		$product_list = [
			$this->createMock( Product::class ),
			$this->createMock( Product::class ),
		];

		$list_response->expects( $this->any() )
			->method( 'getResources' )
			->willReturn( $product_list );

		$this->service->products->expects( $this->once() )
			->method( 'listProducts' )
			->with( $this->merchant_id )
			->willReturn( $list_response );

		$this->assertEquals(
			$product_list,
			$this->merchant->get_products()
		);
	}

	public function test_get_products_multiple_pages() {
		$list_response = $this->createMock( ProductsListResponse::class );

		$token        = uniqid();
		$product_list = [
			$this->createMock( Product::class ),
			$this->createMock( Product::class ),
		];

		$list_response->expects( $this->any() )
			->method( 'getResources' )
			->willReturn( $product_list );

		$list_response->expects( $this->any() )
			->method( 'getNextPageToken' )
			->will(
				$this->onConsecutiveCalls(
					$token,
					$token,
					null
				)
			);

		$this->service->products->expects( $this->exactly( 2 ) )
			->method( 'listProducts' )
			->withConsecutive(
				[ $this->merchant_id ],
				[ $this->merchant_id, [ 'pageToken' => $token ] ]
			)
			->willReturnOnConsecutiveCalls(
				$list_response,
				$list_response
			);

		$products = $this->merchant->get_products();
		$this->assertCount( count( $product_list ) * 2, $products );
	}

	public function test_claim_website() {
		$this->service->accounts->expects( $this->once() )
			->method( 'claimwebsite' )
			->with( $this->merchant_id, $this->merchant_id, [] );
		$this->assertTrue( $this->merchant->claimwebsite() );
	}

	public function test_claimwebsite_error() {
		$this->service->accounts->expects( $this->once() )
			->method( 'claimwebsite' )
			->with( $this->merchant_id, $this->merchant_id, [] )
			->will(
				$this->throwException(
					new GoogleException()
				)
			);

		$this->expectException( Exception::class );
		$this->merchant->claimwebsite();
	}

	public function test_website_already_claimed() {
		$this->service->accounts->expects( $this->once() )
			->method( 'claimwebsite' )
			->with( $this->merchant_id, $this->merchant_id, [] )
			->will(
				$this->throwException(
					new GoogleException( 'claimed', 403 )
				)
			);

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );
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
		$this->mock_get_account( $this->get_account_with_url( $url ) );
		$this->mock_get_account_status( $this->get_status_website_claimed( false ) );

		$this->assertNull( $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_from_account() {
		$url = 'https://site.test';
		$this->mock_get_account( $this->get_account_with_url( $url ) );
		$this->mock_get_account_status( $this->get_status_website_claimed() );

		$this->assertEquals( md5( $url ), $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_with_trailing_slash() {
		$url = 'https://site.test';
		$this->mock_get_account( $this->get_account_with_url( trailingslashit( $url ) ) );
		$this->mock_get_account_status( $this->get_status_website_claimed() );

		$this->assertEquals( md5( $url ), $this->merchant->get_claimed_url_hash() );
	}

	public function test_get_claimed_url_hash_from_account_failure() {
		$this->mock_get_account_exception( $this->get_google_service_exception() );

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

	public function test_get_productstatuses_batch() {
		$this->service->productstatuses = $this->createMock( Productstatuses::class );

		$this->service->productstatuses->expects( $this->once() )
			->method( 'custombatch' )
			->with(
				$this->callback(
					function ( ProductstatusesCustomBatchRequest $request ) {
						$this->assertEquals(
							[
								'batchId'    => 3,
								'productId'  => 3,
								'method'     => 'GET',
								'merchantId' => $this->merchant_id,
							],
							$request->getEntries()[2]
						);

						return true;
					}
				)
			)
			->willReturn( $this->createMock( ProductstatusesCustomBatchResponse::class ) );

		$this->assertInstanceOf(
			ProductstatusesCustomBatchResponse::class,
			$this->merchant->get_productstatuses_batch( [ 1, 2, 3 ] )
		);
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
		$account = $this->createMock( Account::class );
		$ads_id  = 12345;

		$account->expects( $this->once() )
			->method( 'getAdsLinks' )
			->willReturn( [] );

		$account->expects( $this->once() )
			->method( 'setAdsLinks' )
			->with(
				$this->callback(
					function ( array $links ) use ( $ads_id ) {
						$this->assertEquals( $ads_id, $links[0]->getAdsId() );
						$this->assertEquals( 'active', $links[0]->getStatus() );

						return true;
					}
				)
			);

		$this->mock_get_account( $account );

		$this->service->accounts->expects( $this->once() )
			->method( 'update' )
			->willReturn( $account );

		$this->assertTrue(
			$this->merchant->link_ads_id( $ads_id )
		);
	}

	public function test_ads_id_already_linked() {
		$account  = $this->createMock( Account::class );
		$ads_link = $this->createMock( AccountAdsLink::class );
		$ads_id   = 12345;

		$ads_link->expects( $this->any() )
			->method( 'getAdsId' )
			->willReturn( $ads_id );

		$account->expects( $this->once() )
			->method( 'getAdsLinks' )
			->willReturn( [ $ads_link ] );

		$this->mock_get_account( $account );

		$this->assertFalse(
			$this->merchant->link_ads_id( $ads_id )
		);
	}

	public function test_has_access_to_account() {
		$account = $this->createMock( Account::class );
		$user    = $this->createMock( AccountUser::class );
		$email   = 'john@doe.email';

		$user->expects( $this->once() )
			->method( 'getEmailAddress' )
			->willReturn( $email );

		$user->expects( $this->once() )
			->method( 'getAdmin' )
			->willReturn( true );

		$account->expects( $this->once() )
			->method( 'getUsers' )
			->willReturn( [ $user ] );

		$this->mock_get_account( $account );

		$this->assertTrue(
			$this->merchant->has_access( $email )
		);
	}

	public function test_no_access_to_account() {
		$email = 'john@doe.email';

		$this->mock_get_account_exception( $this->get_google_exception( 'No access' ) );

		$this->assertFalse(
			$this->merchant->has_access( $email )
		);
	}

	public function test_update_merchant_id() {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::MERCHANT_ID, $this->merchant_id )
			->willReturn( true );
		$this->assertTrue( $this->merchant->update_merchant_id( $this->merchant_id ) );
	}

	public function test_get_account_review_status() {
		$this->options->expects( $this->once() )->method( 'get_merchant_id' )->willReturn( $this->merchant_id );

		$review_status = [
			'freeListingsProgram' => 'freeListingsProgram',
			'shoppingAdsProgram'  => 'shoppingAdsProgram',
		];

		$this->service->freelistingsprogram->expects( $this->once() )
								->method( 'get' )
								->with( $this->merchant_id )
								->willReturn( 'freeListingsProgram' );

		$this->service->shoppingadsprogram->expects( $this->once() )
											->method( 'get' )
											->with( $this->merchant_id )
											->willReturn( 'shoppingAdsProgram' );

		$this->assertEquals( $this->merchant->get_account_review_status(), $review_status );
	}

	public function test_get_account_review_status_exception() {
		$this->options->expects( $this->once() )->method( 'get_merchant_id' )->willReturn( $this->merchant_id );

		$this->service->freelistingsprogram->expects( $this->once() )
											->method( 'get' )
											->with( $this->merchant_id )
											->willThrowException(
												new GoogleException( 'Some exception', 400 )
											);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Some exception' );
		$this->expectExceptionCode( 400 );

		$this->merchant->get_account_review_status();
	}

	public function test_request_review_freelistings() {
		$types    = [ 'freelistingsprogram', 'shoppingadsprogram' ];
		$response = [ 'statusCode' => 200 ];
		$this->service->freelistingsprogram->expects( $this->once() )
											->method( 'requestreview' )
											->willReturn( $response );

		$this->service->shoppingadsprogram->expects( $this->never() )
											->method( 'requestreview' );

		$this->assertEquals( $this->merchant->account_request_review( 'ES', $types ), $response );
	}

	public function test_request_review_shoppingads() {
		$types    = [ 'shoppingadsprogram' ];
		$response = [ 'statusCode' => 200 ];
		$this->service->shoppingadsprogram->expects( $this->once() )
											->method( 'requestreview' )
											->willReturn( $response );

		$this->service->freelistingsprogram->expects( $this->never() )
											->method( 'requestreview' );

		$this->assertEquals( $this->merchant->account_request_review( 'ES', $types ), $response );
	}

	public function test_request_review_no_valid_type() {
		$types = [ 'bad_dummy_type' ];
		$this->service->freelistingsprogram->expects( $this->never() )
											->method( 'requestreview' );

		$this->service->shoppingadsprogram->expects( $this->never() )
											->method( 'requestreview' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Program type not supported' );
		$this->expectExceptionCode( 400 );

		$this->merchant->account_request_review( 'ES', $types );
	}

	public function test_request_review_exception() {
		$types = [ 'freelistingsprogram' ];
		$this->service->freelistingsprogram->expects( $this->once() )
											->method( 'requestreview' )
											->willThrowException( new GoogleException( 'Some exception', 400 ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Some exception' );
		$this->expectExceptionCode( 400 );

		$this->merchant->account_request_review( 'ES', $types );
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
}
