<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\BackorderAvailabilityDateNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class BackorderAvailabilityDateNoticeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product
 */
class BackorderAvailabilityDateNoticeTest extends UnitTest {

	/** @var MockObject|MerchantCenterService */
	protected $merchant_center;

	/** @var BackorderAvailabilityDateNotice */
	protected $notice;

	public function setUp(): void {
		parent::setUp();
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->notice          = new BackorderAvailabilityDateNotice( $this->merchant_center );
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
}
