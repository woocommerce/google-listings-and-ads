<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;


/**
 * Class PluginHelperTraitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class PluginHelperTraitTest extends TestCase {


	/** @var PluginHelper $trait */
	protected $trait;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$this->trait = new class {
			use PluginHelper {
				convert_to_standard_decimal as public;
			}
		};
	}


	public function test_comma_decimals_gets_converted_to_dot_decimals() {
		$this->assertEquals( '10.5', $this->trait->convert_to_standard_decimal( '10,5' ) );
	}

	public function test_dot_decimals_remain_unchanged() {
		$this->assertEquals( '10.5', $this->trait->convert_to_standard_decimal( '10.5' ) );
	}

	public function test_invalid_numbers() {
		$this->assertEquals( 'no valid. number', $this->trait->convert_to_standard_decimal( 'no valid, number' ) );
	}

	public function test_with_thousands_separator() {
		$this->assertEquals( '1234.5', $this->trait->convert_to_standard_decimal( '1' . wc_get_price_thousand_separator() . '234,5' ) );
	}

	public function test_with_no_decimals() {
		$this->assertEquals( '12345', $this->trait->convert_to_standard_decimal( '12345' ) );
	}

	public function test_with_different_wc_decimal_separator() {
		add_filter(
			'wc_get_price_decimal_separator',
			[ $this, 'callback_filter_decimal_separator' ]
		);

		$this->assertEquals( '10.5', $this->trait->convert_to_standard_decimal( '10-5' ) );
	}

	public function callback_filter_decimal_separator() {
		return '-';
	}

	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'wc_get_price_decimal_separator', [ $this, 'callback_filter_decimal_separator' ] );
	}
}
