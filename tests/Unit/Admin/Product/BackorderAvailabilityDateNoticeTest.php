<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\BackorderAvailabilityDateNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AvailabilityDate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use WC_Product;

/**
 * Class BackorderAvailabilityDateNoticeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product
 */
class BackorderAvailabilityDateNoticeTest extends UnitTest {

	/** @var MockObject|AttributeManager */
	protected $attribute_manager;

	/** @var MockObject|MerchantCenterService */
	protected $merchant_center;

	/** @var BackorderAvailabilityDateNotice */
	protected $notice;

	public function setUp(): void {
		parent::setUp();
		$this->attribute_manager = $this->createMock( AttributeManager::class );
		$this->merchant_center   = $this->createMock( MerchantCenterService::class );
		$this->notice            = new BackorderAvailabilityDateNotice( $this->attribute_manager, $this->merchant_center );
	}

	public function test_register_adds_hook_when_merchant_center_setup_complete(): void {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->notice->register();

		$this->assertNotFalse(
			has_action(
				'woocommerce_product_options_inventory_product_data',
				[ $this->notice, 'render_notice' ]
			)
		);
	}

	public function test_register_does_not_add_hook_when_merchant_center_not_setup(): void {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );

		$this->notice->register();

		$this->assertFalse(
			has_action(
				'woocommerce_product_options_inventory_product_data',
				[ $this->notice, 'render_notice' ]
			)
		);
	}

	public function test_should_show_notice_returns_false_for_variable_product_type(): void {
		$product = $this->create_product_mock( 'variable', false );

		$this->assertFalse( $this->invoke_should_show_notice( $product ) );
	}

	public function test_should_show_notice_returns_false_when_not_on_backorder(): void {
		$product = $this->create_product_mock( 'simple', false );

		$this->assertFalse( $this->invoke_should_show_notice( $product ) );
	}

	public function test_should_show_notice_returns_true_when_on_backorder_and_no_availability_date(): void {
		$product = $this->create_product_mock( 'simple', true );
		$this->attribute_manager->method( 'get' )
			->with( $product, AvailabilityDate::get_id() )
			->willReturn( null );

		$this->assertTrue( $this->invoke_should_show_notice( $product ) );
	}

	public function test_should_show_notice_returns_true_when_on_backorder_and_availability_date_empty(): void {
		$product = $this->create_product_mock( 'simple', true );
		$attr    = new AvailabilityDate( '' );
		$this->attribute_manager->method( 'get' )
			->with( $product, AvailabilityDate::get_id() )
			->willReturn( $attr );

		$this->assertTrue( $this->invoke_should_show_notice( $product ) );
	}

	public function test_should_show_notice_returns_false_when_on_backorder_but_availability_date_set(): void {
		$product = $this->create_product_mock( 'simple', true );
		$attr    = new AvailabilityDate( '2025-03-01' );
		$this->attribute_manager->method( 'get' )
			->with( $product, AvailabilityDate::get_id() )
			->willReturn( $attr );

		$this->assertFalse( $this->invoke_should_show_notice( $product ) );
	}

	public function test_should_show_notice_returns_true_for_variation_on_backorder_without_date(): void {
		$product = $this->create_product_mock( 'variation', true );
		$this->attribute_manager->method( 'get' )
			->with( $product, AvailabilityDate::get_id() )
			->willReturn( null );

		$this->assertTrue( $this->invoke_should_show_notice( $product ) );
	}

	/**
	 * @param string $type Product type.
	 * @param bool   $on_backorder Whether product is on backorder.
	 * @return MockObject|WC_Product
	 */
	private function create_product_mock( string $type, bool $on_backorder ): WC_Product {
		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_type' )->willReturn( $type );
		$product->method( 'is_on_backorder' )->with( 1 )->willReturn( $on_backorder );

		return $product;
	}

	/**
	 * Invoke the protected should_show_notice method.
	 *
	 * @param WC_Product $product
	 * @return bool
	 */
	private function invoke_should_show_notice( WC_Product $product ): bool {
		$method = new ReflectionMethod( BackorderAvailabilityDateNotice::class, 'should_show_notice' );
		$method->setAccessible( true );

		return $method->invoke( $this->notice, $product );
	}
}
