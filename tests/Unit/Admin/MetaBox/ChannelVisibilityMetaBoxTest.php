<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\ChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WC_Product;
use WP_Post;

/**
 * Class ChannelVisibilityMetaBoxTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox
 */
class ChannelVisibilityMetaBoxTest extends UnitTest {

	use ProductTrait;

	/** @var \PHPUnit\Framework\MockObject\Stub|Admin $admin */
	protected $admin;

	/** @var \PHPUnit\Framework\MockObject\MockObject|ProductMetaHandler $meta_handler */
	protected $meta_handler;

	/** @var \PHPUnit\Framework\MockObject\Stub|ProductHelper $product_helper */
	protected $product_helper;

	/** @var \PHPUnit\Framework\MockObject\Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var ChannelVisibilityMetaBox $channel_visibility_meta_box */
	protected $channel_visibility_meta_box;

	public function setUp(): void {
		parent::setUp();

		$this->admin           = $this->createStub( Admin::class );
		$this->meta_handler    = $this->createMock( ProductMetaHandler::class );
		$this->product_helper  = $this->createStub( ProductHelper::class );
		$this->merchant_center = $this->createStub( MerchantCenterService::class );

		$this->channel_visibility_meta_box = new ChannelVisibilityMetaBox(
			$this->admin,
			$this->meta_handler,
			$this->product_helper,
			$this->merchant_center
		);
	}

	public function test_get_view_context_includes_is_connected_when_connected() {
		$product_id = 123;
		$product    = $this->generate_simple_product_mock( $product_id );
		$post       = $this->createMock( WP_Post::class );
		$post->ID   = $product_id;

		$this->product_helper
			->method( 'get_wc_product' )
			->with( $product_id )
			->willReturn( $product );

		$this->product_helper
			->method( 'get_channel_visibility' )
			->with( $product )
			->willReturn( ChannelVisibility::SYNC_AND_SHOW );

		$this->meta_handler
			->method( '__call' )
			->with( 'get_sync_status', [ $product ] )
			->willReturn( 'synced' );

		$this->product_helper
			->method( 'get_validation_errors' )
			->with( $product )
			->willReturn( [] );

		$this->merchant_center
			->method( 'is_setup_complete' )
			->willReturn( true );

		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( true );

		// Use Reflection to access protected method
		$reflection = new \ReflectionClass( $this->channel_visibility_meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$context = $method->invoke( $this->channel_visibility_meta_box, $post, [] );

		$this->assertIsArray( $context );
		$this->assertArrayHasKey( 'is_connected', $context );
		$this->assertTrue( $context['is_connected'] );
	}

	public function test_get_view_context_includes_is_connected_when_not_connected() {
		$product_id = 456;
		$product    = $this->generate_simple_product_mock( $product_id );
		$post       = $this->createMock( WP_Post::class );
		$post->ID   = $product_id;

		$this->product_helper
			->method( 'get_wc_product' )
			->with( $product_id )
			->willReturn( $product );

		$this->product_helper
			->method( 'get_channel_visibility' )
			->with( $product )
			->willReturn( ChannelVisibility::SYNC_AND_SHOW );

		$this->meta_handler
			->method( '__call' )
			->with( 'get_sync_status', [ $product ] )
			->willReturn( 'synced' );

		$this->product_helper
			->method( 'get_validation_errors' )
			->with( $product )
			->willReturn( [] );

		$this->merchant_center
			->method( 'is_setup_complete' )
			->willReturn( false );

		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( false );

		// Use Reflection to access protected method
		$reflection = new \ReflectionClass( $this->channel_visibility_meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$context = $method->invoke( $this->channel_visibility_meta_box, $post, [] );

		$this->assertIsArray( $context );
		$this->assertArrayHasKey( 'is_connected', $context );
		$this->assertFalse( $context['is_connected'] );
	}
}
