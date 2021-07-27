<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
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

	use MerchantTrait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->merchant              = $this->createMock( Merchant::class );
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

		$this->assertEquals(
			$this->valid_account_phone_number,
			$this->merchant_verification->get_phone_number()
		);
	}

	public function test_update_phone_number() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willReturn( $this->get_valid_account() );

		$this->merchant_verification->update_phone_number( $this->valid_account_phone_number );
		$this->assertEquals(
			$this->valid_account_phone_number,
			$this->merchant_verification->get_phone_number()
		);

		// Test exception for bad incorrect values.
		$this->expectException( InvalidValue::class );
		$this->merchant_verification->update_phone_number( 'The quick brown fox' );
	}

	public function test_get_account_exception() {
		$this->merchant->expects( $this->any() )
					   ->method( 'get_account' )
					   ->willThrowException( $this->get_account_exception() );

		$this->expectExceptionObject( $this->get_account_exception() );
		$this->merchant_verification->get_phone_number();
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
