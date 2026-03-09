<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\CouponChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use ReflectionClass;
use WP_Post;

/**
 * Class CouponChannelVisibilityMetaBoxTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox
 */
class CouponChannelVisibilityMetaBoxTest extends UnitTest {

	/** @var \PHPUnit\Framework\MockObject\Stub|Admin $admin */
	protected $admin;

	/** @var \PHPUnit\Framework\MockObject\Stub|CouponMetaHandler $meta_handler */
	protected $meta_handler;

	/** @var \PHPUnit\Framework\MockObject\Stub|CouponHelper $coupon_helper */
	protected $coupon_helper;

	/** @var \PHPUnit\Framework\MockObject\Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var \PHPUnit\Framework\MockObject\Stub|TargetAudience $target_audience */
	protected $target_audience;

	/** @var CouponChannelVisibilityMetaBox $coupon_channel_visibility_meta_box */
	protected $coupon_channel_visibility_meta_box;

	public function setUp(): void {
		parent::setUp();

		$this->admin           = $this->createStub( Admin::class );
		$this->meta_handler    = $this->createStub( CouponMetaHandler::class );
		$this->coupon_helper   = $this->createStub( CouponHelper::class );
		$this->merchant_center = $this->createStub( MerchantCenterService::class );
		$this->target_audience = $this->createStub( TargetAudience::class );

		$this->coupon_channel_visibility_meta_box = new CouponChannelVisibilityMetaBox(
			$this->admin,
			$this->meta_handler,
			$this->coupon_helper,
			$this->merchant_center,
			$this->target_audience
		);
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

		$this->assertSame( $is_connected, $this->coupon_channel_visibility_meta_box->can_register() );
	}

	/**
	 * Data provider for test_can_register_returns_merchant_center_connection_status.
	 *
	 * @return array
	 */
	public function data_provider_is_connected(): array {
		return [
			'connected'     => [ true ],
			'not_connected' => [ false ],
		];
	}

	public function test_get_view_context_returns_expected_keys(): void {
		$post = $this->createMock( WP_Post::class );

		// Use Reflection to access protected method.
		$reflection = new ReflectionClass( $this->coupon_channel_visibility_meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$context = $method->invoke( $this->coupon_channel_visibility_meta_box, $post, [] );

		$this->assertIsArray( $context );
		$this->assertEqualsCanonicalizing(
			[ 'field_id', 'coupon_id', 'coupon', 'channel_visibility', 'sync_status', 'issues', 'is_channel_supported', 'get_started_url' ],
			array_keys( $context )
		);
	}

	/**
	 * @dataProvider data_provider_is_channel_supported
	 *
	 * @param bool $is_supported
	 */
	public function test_get_view_context_includes_is_channel_supported( bool $is_supported ): void {
		$post = $this->createMock( WP_Post::class );

		$this->merchant_center
			->method( 'is_promotion_supported_country' )
			->willReturn( $is_supported );

		// Use Reflection to access protected method.
		$reflection = new ReflectionClass( $this->coupon_channel_visibility_meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$context = $method->invoke( $this->coupon_channel_visibility_meta_box, $post, [] );

		$this->assertIsArray( $context );
		$this->assertArrayHasKey( 'is_channel_supported', $context );
		$this->assertSame( $is_supported, $context['is_channel_supported'] );
	}

	/**
	 * Data provider for test_get_view_context_includes_is_channel_supported.
	 *
	 * @return array
	 */
	public function data_provider_is_channel_supported(): array {
		return [
			'supported'     => [ true ],
			'not_supported' => [ false ],
		];
	}
}
