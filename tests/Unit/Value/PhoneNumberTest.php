<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneNumberTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Value
 */
class PhoneNumberTest extends UnitTest {
	public function test_valid_phone_sanitized() {
		$this->assertEquals(
			'+18772733049',
			( new PhoneNumber( '+18772733049' ) )->get()
		);
		$this->assertEquals(
			'+18772733049',
			( new PhoneNumber( '+1 (877) 273 3049' ) )->get()
		);
		$this->assertEquals(
			'8772733049',
			( new PhoneNumber( '(877) 273 3049' ) )->get()
		);
		$this->assertEquals(
			'+18772733049',
			( new PhoneNumber( '(+1) 877-273-3049' ) )->get()
		);
	}

	public function test_valid_phone_to_string() {
		$this->assertEquals(
			'+18772733049',
			(string) ( new PhoneNumber( '(+1) 877-273-3049' ) )
		);
	}

	public function test_cast() {
		$this->assertEquals(
			'+18772733049',
			PhoneNumber::cast( '(+1) 877-273-3049' )->get()
		);
		$this->assertEquals(
			'8772733049',
			PhoneNumber::cast( 'Phone is (877) 273-3049' )->get()
		);
	}

	public function test_invalid_phone() {
		$this->expectException( InvalidValue::class );
		new PhoneNumber( 'Phone is 8772733049' );
	}
}
