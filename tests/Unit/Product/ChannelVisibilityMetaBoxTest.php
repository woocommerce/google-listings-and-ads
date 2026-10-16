<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\ChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
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

	/** @var \PHPUnit\Framework\MockObject\Stub|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var ChannelVisibilityMetaBox $channel_visibility_meta_box */
	protected $channel_visibility_meta_box;

	public function setUp(): void {
		parent::setUp();

		$this->admin                        = $this->createStub( Admin::class );
		$this->meta_handler                 = $this->createMock( ProductMetaHandler::class );
		$this->product_helper               = $this->createStub( ProductHelper::class );
		$this->merchant_center              = $this->createStub( MerchantCenterService::class );
		$this->service_based_merchant_state = $this->createStub( ServiceBasedMerchantState::class );

		$this->channel_visibility_meta_box = new ChannelVisibilityMetaBox(
			$this->admin,
			$this->meta_handler,
			$this->product_helper,
			$this->merchant_center,
			$this->service_based_merchant_state
		);
	}

	/**
	 * @dataProvider data_provider_is_setup_complete
	 *
	 * @param bool $is_setup_complete
	 */
	public function test_get_view_context_includes_is_setup_complete( bool $is_setup_complete ) {
		$post = $this->createMock( WP_Post::class );

		$this->merchant_center
			->method( 'is_setup_complete' )
			->willReturn( $is_setup_complete );

		// Use Reflection to access protected method.
		$reflection = new \ReflectionClass( $this->channel_visibility_meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$context = $method->invoke( $this->channel_visibility_meta_box, $post, [] );

		$this->assertIsArray( $context );
		$this->assertArrayHasKey( 'is_setup_complete', $context );
		$this->assertSame( $is_setup_complete, $context['is_setup_complete'] );
	}

	/**
	 * Data provider for test_get_view_context_includes_is_setup_complete.
	 *
	 * @return array
	 */
	public function data_provider_is_setup_complete(): array {
		return [
			'setup_complete'     => [ true ],
			'setup_not_complete' => [ false ],
		];
	}

	public function test_can_register_returns_false_for_service_based_merchant() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );

		$this->assertFalse( $this->channel_visibility_meta_box->can_register() );
	}

	public function test_can_register_returns_true_for_non_service_based_merchant() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );

		$this->assertTrue( $this->channel_visibility_meta_box->can_register() );
	}
}
