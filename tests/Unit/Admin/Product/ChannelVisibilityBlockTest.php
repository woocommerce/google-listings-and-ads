<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\ChannelVisibilityBlock;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use WC_Order_Item_Product;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

/**
 * Class ChannelVisibilityBlockTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product
 */
class ChannelVisibilityBlockTest extends UnitTest {

	use ProductTrait;

	/** @var Stub|ProductHelper $product_helper */
	protected $product_helper;

	/** @var Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var ChannelVisibilityBlock $channel_visibility_block */
	protected $channel_visibility_block;

	public function setUp(): void {
		parent::setUp();

		$this->product_helper  = $this->createStub( ProductHelper::class );
		$this->merchant_center = $this->createStub( MerchantCenterService::class );

		$this->channel_visibility_block = new ChannelVisibilityBlock( $this->product_helper, $this->merchant_center );
	}

	public function test_register() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->channel_visibility_block->register();

		$this->assertEquals(
			10,
			has_filter(
				'woocommerce_rest_prepare_product_object',
				[ $this->channel_visibility_block, 'prepare_data' ]
			)
		);

		$this->assertEquals(
			10,
			has_action(
				'woocommerce_rest_insert_product_object',
				[ $this->channel_visibility_block, 'update_data' ]
			)
		);
	}

	public function test_register_merchant_center_setup_is_not_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );

		$this->channel_visibility_block->register();

		$this->assertFalse(
			has_filter(
				'woocommerce_rest_prepare_product_object',
				[ $this->channel_visibility_block, 'prepare_data' ]
			)
		);

		$this->assertFalse(
			has_action(
				'woocommerce_rest_insert_product_object',
				[ $this->channel_visibility_block, 'update_data' ]
			)
		);
	}

	public function test_prepare_data() {
		$product = $this->generate_simple_product_mock();
		$product->method( 'is_visible' )->willReturn( true );

		$issues = [ 'Problem #1', 'Problem #2' ];
		$this->product_helper->method( 'get_channel_visibility' )->willReturn( 'sync-and-show' );
		$this->product_helper->method( 'get_sync_status' )->willReturn( 'has-errors' );
		$this->product_helper->method( 'get_validation_errors' )->willReturn( $issues );

		$response = $this->channel_visibility_block->prepare_data( new Response(), $product );

		$this->assertInstanceOf( Response::class, $response );
		$this->assertEquals(
			[
				'is_visible'         => true,
				'channel_visibility' => 'sync-and-show',
				'sync_status'        => 'has-errors',
				'issues'             => $issues,
			],
			$response->data[ ChannelVisibilityBlock::PROPERTY ]
		);
	}

	public function test_prepare_data_incoming_unexpected_wc_data() {
		$product  = $this->createMock( WC_Order_Item_Product::class );
		$response = $this->channel_visibility_block->prepare_data( new Response( [] ), $product );

		$this->assertInstanceOf( Response::class, $response );
		$this->assertArrayNotHasKey( ChannelVisibilityBlock::PROPERTY, $response->data );
		$this->product_helper->expects( $this->exactly( 0 ) )->method( 'get_channel_visibility' );
	}

	public function test_update_data() {
		$product = $this->generate_simple_product_mock();
		$request = new Request( 'POST' );
		$data    = [ 'channel_visibility' => 'dont-sync-and-show' ];

		$request->set_param( ChannelVisibilityBlock::PROPERTY, $data );

		$this->product_helper
			->expects( $this->exactly( 1 ) )
			->method( 'update_channel_visibility' )
			->with( $product, $data['channel_visibility'] );

		$this->channel_visibility_block->update_data( $product, $request );
	}

	public function test_update_data_skip_update_if_same_value() {
		$product = $this->generate_simple_product_mock();
		$request = new Request( 'POST' );
		$data    = [ 'channel_visibility' => 'sync-and-show' ];

		$request->set_param( ChannelVisibilityBlock::PROPERTY, $data );

		$this->product_helper
			->method( 'get_channel_visibility' )
			->willReturn( $data['channel_visibility'] );

		$this->product_helper
			->expects( $this->exactly( 0 ) )
			->method( 'update_channel_visibility' );

		$this->channel_visibility_block->update_data( $product, $request );
	}

	public function test_update_data_incoming_unexpected_wc_data() {
		$product = $this->createMock( WC_Order_Item_Product::class );
		$request = $this->createMock( Request::class );

		$request
			->expects( $this->exactly( 0 ) )
			->method( 'get_params' );

		$this->product_helper
			->expects( $this->exactly( 0 ) )
			->method( 'update_channel_visibility' );

		$this->channel_visibility_block->update_data( $product, $request );
	}

	public function test_update_data_unsupported_product_type() {
		$product = $this->generate_variation_product_mock();
		$request = $this->createMock( Request::class );

		$request
			->expects( $this->exactly( 0 ) )
			->method( 'get_params' );

		$this->product_helper
			->expects( $this->exactly( 0 ) )
			->method( 'update_channel_visibility' );

		$this->channel_visibility_block->update_data( $product, $request );
	}

	public function test_update_data_unrelated_request() {
		$product = $this->generate_simple_product_mock();
		$request = $this->createMock( Request::class );

		$request
			->expects( $this->exactly( 1 ) )
			->method( 'get_params' );

		$this->product_helper
			->expects( $this->exactly( 0 ) )
			->method( 'update_channel_visibility' );

		$this->channel_visibility_block->update_data( $product, $request );
	}

	public function test_get_visible_product_types() {
		$this->assertEqualsCanonicalizing(
			[ 'simple', 'variable' ],
			$this->channel_visibility_block->get_visible_product_types()
		);

		add_filter(
			'woocommerce_gla_supported_product_types',
			function ( array $product_types ) {
				$product_types[] = 'bundle';
				return $product_types;
			}
		);

		$this->assertEqualsCanonicalizing(
			[ 'simple', 'variable', 'bundle' ],
			$this->channel_visibility_block->get_visible_product_types()
		);
	}

	public function test_get_block_config() {
		$this->assertEquals(
			[
				'id'         => 'google-listings-and-ads-product-channel-visibility',
				'blockName'  => 'google-listings-and-ads/product-channel-visibility',
				'attributes' => [
					'property'          => ChannelVisibilityBlock::PROPERTY,
					'options'           => [
						[
							'label' => 'Sync and show',
							'value' => 'sync-and-show',
						],
						[
							'label' => "Don't Sync and show",
							'value' => 'dont-sync-and-show',
						],
					],
					'valueOfSync'       => 'sync-and-show',
					'valueOfDontSync'   => 'dont-sync-and-show',
					'statusOfSynced'    => 'synced',
					'statusOfHasErrors' => 'has-errors',
				],
			],
			$this->channel_visibility_block->get_block_config()
		);
	}
}
