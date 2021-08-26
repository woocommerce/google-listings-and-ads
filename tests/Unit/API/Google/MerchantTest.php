<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use Google\Exception as GoogleException;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Account;
use Google\Service\ShoppingContent\AccountAdsLink;
use Google\Service\ShoppingContent\AccountStatus;
use Google\Service\ShoppingContent\AccountUser;
use Google\Service\ShoppingContent\Product;
use Google\Service\ShoppingContent\ProductsListResponse;
use Google\Service\ShoppingContent\ProductstatusesCustomBatchRequest;
use Google\Service\ShoppingContent\ProductstatusesCustomBatchResponse;
use Google\Service\ShoppingContent\Resource\Accounts;
use Google\Service\ShoppingContent\Resource\Accountstatuses;
use Google\Service\ShoppingContent\Resource\Products;
use Google\Service\ShoppingContent\Resource\Productstatuses;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property  MockObject|ShoppingContent  $service
 * @property  MockObject|OptionsInterface $options
 * @property  Merchant                    $merchant
 */
class MerchantTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->service           = $this->createMock( ShoppingContent::class );
		$this->service->accounts = $this->createMock( Accounts::class );
		$this->service->products = $this->createMock( Products::class );

		$this->options  = $this->createMock( OptionsInterface::class );
		$this->merchant = new Merchant( $this->service );
		$this->merchant->set_options_object( $this->options );

		$this->merchant_id = 12345;
		$this->options->method( 'get_merchant_id' )->willReturn( $this->merchant_id );
	}

	public function test_get_products_empty_list() {
		$list_response = $this->createMock( ProductsListResponse::class );

		$this->service->products->expects( $this->any() )
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

		$this->service->products->expects( $this->any() )
			->method( 'listProducts' )
			->with( $this->merchant_id )
			->willReturn( $list_response );

		$products = $this->merchant->get_products();
		$this->assertCount( count( $product_list ), $products );
		foreach ( $products as $product ) {
			$this->assertInstanceOf( Product::class, $product );
		}
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

		$this->service->products->expects( $this->at( 0 ) )
			->method( 'listProducts' )
			->with( $this->merchant_id )
			->willReturn( $list_response );

		$this->service->products->expects( $this->at( 1 ) )
			->method( 'listProducts' )
			->with( $this->merchant_id, [ 'pageToken' => $token ] )
			->willReturn( $list_response );

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

	public function test_get_account() {
		$this->service->accounts->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->willReturn( $this->createMock( Account::class ) );

		$this->assertInstanceOf( Account::class, $this->merchant->get_account() );
	}

	public function test_get_account_failure() {
		$this->service->accounts->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->will(
				$this->throwException(
					new GoogleException( 'error', 400 )
				)
			);

		$this->expectException( MerchantApiException::class );
        $this->expectExceptionCode( 400 );
		$this->merchant->get_account();
	}

	public function test_get_accountstatuses() {
		$this->service->accountstatuses = $this->createMock( Accountstatuses::class );
		$this->service->accountstatuses->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->willReturn( $this->createMock( AccountStatus::class ) );

		$this->assertInstanceOf( AccountStatus::class, $this->merchant->get_accountstatus() );
	}

	public function test_get_accountstatus_failure() {
		$this->service->accountstatuses = $this->createMock( Accountstatuses::class );
		$this->service->accountstatuses->expects( $this->once() )
			->method( 'get' )
			->with( $this->merchant_id, $this->merchant_id )
			->will(
				$this->throwException(
					new GoogleException( 'error', 400 )
				)
			);

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
					function( ProductstatusesCustomBatchRequest $request ) {
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
					new GoogleException( 'error', 400 )
				)
			);

		$this->expectException( MerchantApiException::class );
        $this->expectExceptionCode( 400 );
		$this->merchant->update_account( $account );
	}

	public function test_link_ads_id() {
		$account = $this->createMock( Account::class );
		$ads_id  = 12345;

		$account->expects( $this->any() )
			->method( 'getAdsLinks' )
			->willReturn( [] );

		$this->service->accounts->expects( $this->any() )
			->method( 'get' )
			->willReturn( $account );

		$this->service->accounts->expects( $this->any() )
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

		$account->expects( $this->any() )
			->method( 'getAdsLinks' )
			->willReturn( [ $ads_link ] );

		$this->service->accounts->expects( $this->any() )
			->method( 'get' )
			->willReturn( $account );

		$this->assertFalse(
			$this->merchant->link_ads_id( $ads_id )
		);
	}

	public function test_has_access_to_account() {
		$account = $this->createMock( Account::class );
		$user    = $this->createMock( AccountUser::class );
		$email   = 'john@doe.email';

		$user->expects( $this->any() )
			->method( 'getEmailAddress' )
			->willReturn( $email );

		$user->expects( $this->any() )
			->method( 'getAdmin' )
			->willReturn( true );

		$account->expects( $this->any() )
			->method( 'getUsers' )
			->willReturn( [ $user ] );

		$this->service->accounts->expects( $this->any() )
			->method( 'get' )
			->willReturn( $account );

		$this->assertTrue(
			$this->merchant->has_access( $email )
		);
	}

	public function test_no_access_to_account() {
		$email = 'john@doe.email';

		$this->service->accounts->expects( $this->any() )
			->method( 'get' )
			->will(
				$this->throwException(
					new GoogleException( 'no access', 403 )
				)
			);

		$this->assertFalse(
			$this->merchant->has_access( $email )
		);
	}

}
