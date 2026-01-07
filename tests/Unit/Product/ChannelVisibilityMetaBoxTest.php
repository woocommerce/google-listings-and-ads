<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ChannelVisibilityMetaBox;
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
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
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

	/**
	 * @dataProvider data_provider_is_connected
	 *
	 * @param bool $is_connected
	 */
	public function test_get_view_context_includes_is_connected( bool $is_connected ) {
		$post = $this->createMock( WP_Post::class );

		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( $is_connected );

		// Use Reflection to access protected method.
		$reflection = new \ReflectionClass( $this->channel_visibility_meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$context = $method->invoke( $this->channel_visibility_meta_box, $post, [] );

		$this->assertIsArray( $context );
		$this->assertArrayHasKey( 'is_connected', $context );
		$this->assertSame( $is_connected, $context['is_connected'] );
	}

	/**
	 * Data provider for test_get_view_context_includes_is_connected.
	 *
	 * @return array
	 */
	public function data_provider_is_connected(): array {
		return [
			'connected'     => [ true ],
			'not_connected' => [ false ],
		];
	}

	/**
	 * @dataProvider data_provider_is_connected
	 *
	 * @param bool $is_connected
	 */
	public function test_can_register_returns_merchant_center_connection_status( bool $is_connected ) {
		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( $is_connected );

		$this->assertSame( $is_connected, $this->channel_visibility_meta_box->can_register() );
	}
}
