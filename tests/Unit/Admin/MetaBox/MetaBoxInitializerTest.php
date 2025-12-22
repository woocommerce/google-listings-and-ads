<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class MetaBoxInitializerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox
 */
class MetaBoxInitializerTest extends UnitTest {

	/** @var \PHPUnit\Framework\MockObject\MockObject|Admin $admin */
	protected $admin;

	/** @var \PHPUnit\Framework\MockObject\Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var \PHPUnit\Framework\MockObject\Stub|ChannelVisibilityMetaBox $channel_visibility_meta_box */
	protected $channel_visibility_meta_box;

	/** @var \PHPUnit\Framework\MockObject\Stub|MetaBoxInterface $other_meta_box */
	protected $other_meta_box;

	public function setUp(): void {
		parent::setUp();

		$this->admin                       = $this->createMock( Admin::class );
		$this->merchant_center             = $this->createStub( MerchantCenterService::class );
		$this->channel_visibility_meta_box = $this->createStub( ChannelVisibilityMetaBox::class );
		$this->other_meta_box              = $this->createStub( MetaBoxInterface::class );
	}

	public function test_registers_channel_visibility_meta_box_when_connected() {
		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( true );

		$meta_boxes = [ $this->channel_visibility_meta_box, $this->other_meta_box ];

		// Expect both meta boxes to be registered.
		$this->admin
			->expects( $this->exactly( 2 ) )
			->method( 'add_meta_box' );

		$initializer = new MetaBoxInitializer( $this->admin, $meta_boxes, $this->merchant_center );
		$initializer->register_meta_boxes();
	}

	public function test_skips_channel_visibility_meta_box_when_not_connected() {
		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( false );

		$meta_boxes = [ $this->channel_visibility_meta_box, $this->other_meta_box ];

		// Expect only the other meta box to be registered (not channel visibility).
		$this->admin
			->expects( $this->once() )
			->method( 'add_meta_box' )
			->with( $this->other_meta_box );

		$initializer = new MetaBoxInitializer( $this->admin, $meta_boxes, $this->merchant_center );
		$initializer->register_meta_boxes();
	}

	public function test_always_registers_other_meta_boxes() {
		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( false );

		$meta_boxes = [ $this->other_meta_box ];

		// Expect the other meta box to always be registered regardless of connection status.
		$this->admin
			->expects( $this->once() )
			->method( 'add_meta_box' )
			->with( $this->other_meta_box );

		$initializer = new MetaBoxInitializer( $this->admin, $meta_boxes, $this->merchant_center );
		$initializer->register_meta_boxes();
	}
}
