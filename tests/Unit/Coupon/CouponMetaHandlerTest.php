<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use BadMethodCallException;
use WC_Coupon;
use WP_UnitTestCase;

/**
 * Class ProductMetaHandlerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon
 *
 * @property CouponMetaHandler $coupon_meta_handler
 */
class CouponMetaHandlerTest extends WP_UnitTestCase {
    use CouponTrait;

	public function test_magic_call_throws_exception_invalid_method_name() {
		$this->expectException( BadMethodCallException::class);
		$this->coupon_meta_handler->in1va2lid_method();
	}

	public function test_magic_call_throws_exception_method_not_allowed() {
		$this->expectException( BadMethodCallException::class);
		$this->coupon_meta_handler->need_synced_at();
	}

	public function test_magic_update_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class);
		$this->coupon_meta_handler->update_invalid_meta_key_test( $this->generate_simple_coupon_mock(), 1 );
	}

	public function test_magic_delete_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class);
		$this->coupon_meta_handler->delete_invalid_meta_key_test( $this->generate_simple_coupon_mock() );
	}

	public function test_magic_get_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class);
		$this->coupon_meta_handler->get_invalid_meta_key_test( $this->generate_simple_coupon_mock() );
	}

	public function test_magic_call() {
		$coupon = $this->generate_simple_coupon_mock();
		$key     = 'synced_at';
		$value   = 12345;

		$coupon_meta_handler = $this->getMockBuilder( CouponMetaHandler::class )
									 ->setMethodsExcept( [ '__call' ] )
									 ->getMock();

		$coupon_meta_handler->expects( $this->once() )
							 ->method( 'update' )
							 ->with( $this->equalTo( $coupon ), $this->equalTo( $key ), $this->equalTo( $value ) );
		$coupon_meta_handler->update_synced_at( $coupon, $value );

		$coupon_meta_handler->expects( $this->once() )
							 ->method( 'get' )
							 ->with( $this->equalTo( $coupon ), $this->equalTo( $key ) );
		$coupon_meta_handler->get_synced_at( $coupon );

		$coupon_meta_handler->expects( $this->once() )
							 ->method( 'delete' )
							 ->with( $this->equalTo( $coupon ), $this->equalTo( $key ) );
		$coupon_meta_handler->delete_synced_at( $coupon );
	}

	public function test_update_type_cast() {
		$coupon = new WC_Coupon();
		$this->coupon_meta_handler->update( $coupon, CouponMetaHandler::KEY_SYNCED_AT, '12345' );
		$value = $coupon->get_meta( '_wc_gla_synced_at', true );
		$this->assertEquals( 12345, $value );
	}

	public function test_update() {
	    $coupon = new WC_Coupon();
	    $this->coupon_meta_handler->update( $coupon, CouponMetaHandler::KEY_SYNCED_AT, 12345 );
		$value = $coupon->get_meta( '_wc_gla_synced_at', true );
		$this->assertEquals( 12345, $value );
	}

	public function test_update_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class);
		$this->coupon_meta_handler->update( $this->generate_simple_coupon_mock(), 'invalid_meta_key_test', 1 );
	}

	public function test_delete() {
	    $coupon = new WC_Coupon();
		$coupon->update_meta_data( '_wc_gla_synced_at', 12345 );
		$coupon->save_meta_data();

		$this->coupon_meta_handler->delete( $coupon, CouponMetaHandler::KEY_SYNCED_AT );

		$value = $coupon->get_meta( '_wc_gla_synced_at', true );
		$this->assertEmpty( $value );
	}

	public function test_delete_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class);
		$this->coupon_meta_handler->delete( $this->generate_simple_coupon_mock(), 'invalid_meta_key_test' );
	}

	public function test_get_returns_null_if_no_value_exist() {
		$coupon = new WC_Coupon();

		$value = $this->coupon_meta_handler->get( $coupon, CouponMetaHandler::KEY_SYNCED_AT );
		$this->assertNull( $value );
	}

	public function test_get() {
		$coupon = new WC_Coupon();
		$coupon->update_meta_data('_wc_gla_synced_at', 12345);
		$coupon->save_meta_data();

		$value = $this->coupon_meta_handler->get( $coupon, CouponMetaHandler::KEY_SYNCED_AT );
		$this->assertEquals( 12345, $value );
	}

	public function test_get_throws_exception_invalid_meta_key() {
		$this->expectException( InvalidMeta::class);
		$this->coupon_meta_handler->get( $this->generate_simple_coupon_mock(), 'invalid_meta_key_test' );
	}

	public function test_is_meta_key_valid() {
		$this->assertFalse( CouponMetaHandler::is_meta_key_valid( 'invalid_meta_key_test' ) );
		$this->assertTrue( CouponMetaHandler::is_meta_key_valid( CouponMetaHandler::KEY_SYNCED_AT ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->coupon_meta_handler = new CouponMetaHandler();
	}
}
