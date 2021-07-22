<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Exception;
use Google\Model;
use Google\Service\ShoppingContent\Account;
use Google\Service\ShoppingContent\AccountBusinessInformation;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantVerificationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @since x.x.x
 *
 * @property  MockObject|Merchant $merchant
 * @property  MerchantVerification $merchant_verification
 */
class MerchantVerificationTest extends ContainerAwareUnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->merchant = $this->createMock( Merchant::class);
		$this->merchant_verification = new MerchantVerification( $this->merchant );
	}

	public function test_get_empty_phone_number() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_empty_account() );

		$this->assertEquals( '', $this->merchant_verification->get_phone_number() );
	}

	public function test_get_valid_phone_number() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$this->assertEquals( '+18675309', $this->merchant_verification->get_phone_number() );
	}

	public function test_update_phone_number() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$new_phone_number = '+34118110000';
		$this->merchant_verification->update_phone_number( $new_phone_number );
		$this->assertEquals( $new_phone_number, $this->merchant_verification->get_phone_number() );

		// Test that invalid numbers work too (by design)
		$new_phone_number = 'The quick brown fox';
		$this->merchant_verification->update_phone_number( $new_phone_number );
		$this->assertEquals( $new_phone_number, $this->merchant_verification->get_phone_number() );
	}

	public function test_get_account_exception() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willThrowException( $this->get_account_exception() );

		$this->expectExceptionObject( $this->get_account_exception() );
		$this->merchant_verification->get_phone_number();

	}

	public function get_account_exception() {
		return new Exception( __( 'Unable to retrieve Merchant Center account.', 'google-listings-and-ads' ) );
	}

	public function get_empty_account(): Account {
		return new Account();
	}

	public function get_valid_account(): Account {
		$account = new Account();
		$business_info = new AccountBusinessInformation();
		$business_info->setPhoneNumber( '+18675309' );
		$account->setBusinessInformation( $business_info );
		return $account;
	}
}
