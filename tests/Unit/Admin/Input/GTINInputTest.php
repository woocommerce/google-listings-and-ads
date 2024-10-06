<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesForm;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\GTINInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class GTINInputTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Input
 */
class GTINInputTest extends UnitTest {
	/** @var GTINInput $input */
	protected $input;

	/** @var OptionsInterface $options */
	protected $options;

	public function setUp(): void {
		parent::setUp();
		$this->options = $this->createMock( OptionsInterface::class );
		$this->input   = new GTINInput();
		$this->input->set_options_object( $this->options );
	}

	public function test_gtin_is_not_hidden_before_2_8_5() {
		$this->options->method( 'get' )
			->with( OptionsInterface::INSTALL_VERSION )
			->willReturn( '2.8.4' );
		$form = new AttributesForm( [ GTIN::class ] );
		$this->assertTrue( isset( $form->get_view_data()['children']['gtin'] ) );
		$this->assertEquals( $form->get_view_data()['children']['gtin']['custom_attributes']['readonly'], 'readonly' );
	}

	public function test_gtin_is_hidden_from_2_8_5() {
		$this->options->method( 'get' )
			->with( OptionsInterface::INSTALL_VERSION )
			->willReturn( '2.8.5' );
		$form = new AttributesForm( [ GTIN::class ] );
		$this->assertFalse( isset( $form->get_view_data()['children']['gtin'] ) );
	}
}
