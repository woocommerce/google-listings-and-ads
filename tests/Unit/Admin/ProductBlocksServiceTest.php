<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\ChannelVisibilityBlock;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\ProductBlocksService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ProductBlocksServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin
 */
class ProductBlocksServiceTest extends ContainerAwareUnitTest {

	use AttributesTrait;

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var ChannelVisibilityBlock $channel_visibility_block */
	protected $channel_visibility_block;

	/** @var AttributeManager $attribute_manager */
	protected $attribute_manager;

	/** @var Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|BlockInterface $simple_anchor_group */
	protected $simple_anchor_group;

	/** @var MockObject|BlockInterface $variation_anchor_group */
	protected $variation_anchor_group;

	/** @var MockObject|BlockInterface $mismatching_group */
	protected $mismatching_group;

	/** @var ProductBlocksService $product_blocks_service */
	protected $product_blocks_service;

	/** @var bool $is_mc_setup_complete */
	protected $is_mc_setup_complete;

	/** @var array $simple */
	protected $simple;

	/** @var array $variation */
	protected $variation;

	protected const GENERAL_GROUP_HOOK = 'woocommerce_block_template_area_product-form_after_add_block_general';

	public function setUp(): void {
		// compatibility-code "WC >= 8.6" -- The Block Template API used requires at least WooCommerce 8.6
		if ( ! ProductBlocksService::is_needed() ) {
			$this->markTestSkipped( 'This test suite requires WooCommerce version >= 8.6' );
		}

		parent::setUp();

		$this->assets_handler           = $this->createMock( AssetsHandlerInterface::class );
		$this->channel_visibility_block = $this->container->get( ChannelVisibilityBlock::class );
		$this->attribute_manager        = $this->container->get( AttributeManager::class );
		$this->merchant_center          = $this->createStub( MerchantCenterService::class );

		$this->simple_anchor_group    = $this->createMock( BlockInterface::class );
		$this->variation_anchor_group = $this->createMock( BlockInterface::class );
		$this->mismatching_group      = $this->createMock( BlockInterface::class );

		$this->product_blocks_service = new ProductBlocksService( $this->assets_handler, $this->channel_visibility_block, $this->attribute_manager, $this->merchant_center );

		// Set up stubs and mocks
		$this->is_mc_setup_complete = true;
		$this->merchant_center
			->method( 'is_setup_complete' )
			->willReturnCallback(
				function () {
					return $this->is_mc_setup_complete;
				}
			);

		// Ref: https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/src/Admin/PageController.php#L555-L562
		$_GET['page'] = PageController::PAGE_ROOT;

		$this->simple    = $this->setUpBlockMock( $this->simple_anchor_group, 'simple-product' );
		$this->variation = $this->setUpBlockMock( $this->variation_anchor_group, 'product-variation' );
		$this->setUpBlockMock( $this->mismatching_group, 'mismatching-template' );
	}

	private function setUpBlockMock( MockObject $anchor_group, string $template_id ) {
		$template = $this->createMock( ProductFormTemplateInterface::class );
		$group    = $this->createMock( GroupInterface::class );

		$visibility_section = $this->createMock( SectionInterface::class );
		$attributes_section = $this->createMock( SectionInterface::class );
		$visibility_block   = $this->createMock( BlockInterface::class );
		$attributes_block   = $this->createMock( BlockInterface::class );

		$template->method( 'get_id' )->willReturn( $template_id );
		$template->method( 'add_group' )->willReturn( $group );

		$visibility_section->method( 'get_root_template' )->willReturn( $template );
		$visibility_section->method( 'add_block' )->willReturn( $visibility_block );

		$attributes_section->method( 'get_root_template' )->willReturn( $template );
		$attributes_section->method( 'add_block' )->willReturn( $attributes_block );

		$anchor_group->method( 'get_root_template' )->willReturn( $template );

		$group
			->method( 'add_section' )
			->willReturnCallback(
				function ( array $config ) use ( $visibility_section, $attributes_section ) {
					if ( 'google-listings-and-ads-channel-visibility-section' === $config['id'] ) {
						return $visibility_section;
					}

					if ( 'google-listings-and-ads-product-attributes-section' === $config['id'] ) {
						return $attributes_section;
					}
				}
			);

		return [
			'template'           => $template,
			'group'              => $group,
			'visibility_section' => $visibility_section,
			'attributes_section' => $attributes_section,
			'visibility_block'   => $visibility_block,
			'attributes_block'   => $attributes_block,
		];
	}

	public function test_get_applicable_product_types() {
		$this->assertEquals( [ 'simple', 'variable' ], $this->get_applicable_product_types() );

		add_filter(
			'woocommerce_gla_attributes_tab_applicable_product_types',
			function ( array $product_types ) {
				$product_types[] = 'bundle';
				return $product_types;
			}
		);

		$this->assertEquals( [ 'simple', 'variable', 'bundle' ], $this->get_applicable_product_types() );
	}

	public function test_get_hide_condition() {
		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variable' && editedProduct.type !== 'variation'",
			$this->product_blocks_service->get_hide_condition( Adult::get_applicable_product_types() )
		);

		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variable'",
			$this->product_blocks_service->get_hide_condition( Brand::get_applicable_product_types() )
		);

		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variation'",
			$this->product_blocks_service->get_hide_condition( Gender::get_applicable_product_types() )
		);

		// Hide all product types
		$this->assertEquals( 'true', $this->product_blocks_service->get_hide_condition( [] ) );
	}

	public function test_register() {
		$this->assertFalse( has_filter( 'init', [ $this->product_blocks_service, 'hook_init' ] ) );
		$this->assertFalse( has_filter( self::GENERAL_GROUP_HOOK, [ $this->product_blocks_service, 'hook_block_template' ] ) );

		$this->product_blocks_service->register();

		$this->assertEquals( 10, has_filter( 'init', [ $this->product_blocks_service, 'hook_init' ] ) );
		$this->assertEquals( 10, has_filter( self::GENERAL_GROUP_HOOK, [ $this->product_blocks_service, 'hook_block_template' ] ) );
	}

	public function test_register_is_not_admin_page() {
		unset( $_GET['page'] );

		$this->product_blocks_service->register();

		$this->assertFalse( has_filter( 'init', [ $this->product_blocks_service, 'hook_init' ] ) );
	}

	public function test_hook_block_template() {
		$this->simple_anchor_group->get_root_template()
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' );

		$this->variation_anchor_group->get_root_template()
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' );

		$this->simple['template']
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' )->with(
				[
					'id'         => 'google-listings-and-ads-group',
					'order'      => 100,
					'attributes' => [
						'title' => 'Google for WooCommerce',
					],
				]
			);

		$this->simple['group']
			->expects( $this->exactly( 2 ) )
			->method( 'add_section' )
			->withConsecutive(
				[
					[
						'id'         => 'google-listings-and-ads-channel-visibility-section',
						'order'      => 1,
						'attributes' => [
							'title' => __( 'Channel visibility', 'google-listings-and-ads' ),
						],
					],
				],
				[
					[
						'id'         => 'google-listings-and-ads-product-attributes-section',
						'order'      => 2,
						'attributes' => [
							'title' => 'Product attributes',
						],
					],
				]
			);

		$this->variation['template']
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' )->with(
				[
					'id'         => 'google-listings-and-ads-group',
					'order'      => 100,
					'attributes' => [
						'title' => 'Google for WooCommerce',
					],
				]
			);

		$this->variation['group']
			->expects( $this->exactly( 2 ) )
			->method( 'add_section' )
			->withConsecutive(
				[
					[
						'id'         => 'google-listings-and-ads-channel-visibility-section',
						'order'      => 1,
						'attributes' => [
							'title' => __( 'Channel visibility', 'google-listings-and-ads' ),
						],
					],
				],
				[
					[
						'id'         => 'google-listings-and-ads-product-attributes-section',
						'order'      => 2,
						'attributes' => [
							'title' => 'Product attributes',
						],
					],
				]
			);

		$this->product_blocks_service->hook_block_template( $this->simple_anchor_group );
		$this->product_blocks_service->hook_block_template( $this->variation_anchor_group );
	}

	public function test_hook_block_template_group_hidden_condition() {
		$this->simple['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_hide_condition' )
			->with( "editedProduct.type !== 'simple' && editedProduct.type !== 'variable' && editedProduct.type !== 'variation'" );

		$this->variation['group']
			->expects( $this->exactly( 0 ) )
			->method( 'add_hide_condition' );

		$this->product_blocks_service->hook_block_template( $this->simple_anchor_group );
		$this->product_blocks_service->hook_block_template( $this->variation_anchor_group );
	}

	public function test_hook_block_template_group_hidden_condition_with_applying_filter() {
		$this->simple['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_hide_condition' )
			->with( "editedProduct.type !== 'simple'" );

		$this->variation['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_hide_condition' )
			->with( 'true' );

		add_filter(
			'woocommerce_gla_supported_product_types',
			function () {
				return [ 'simple' ];
			}
		);

		$this->product_blocks_service->hook_block_template( $this->simple_anchor_group );
		$this->product_blocks_service->hook_block_template( $this->variation_anchor_group );
	}

	public function test_hook_block_template_merchant_center_setup_is_not_complete() {
		$this->is_mc_setup_complete = false;

		$this->simple_anchor_group->get_root_template()
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' );

		$this->simple['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_block' )
			->with(
				[
					'id'         => 'google-listings-and-ads-product-onboarding-prompt',
					'blockName'  => 'google-listings-and-ads/product-onboarding-prompt',
					'attributes' => [ 'startUrl' => 'http://example.org/wp-admin/admin.php?page=wc-admin&path=/google/start' ],
				]
			);

		$this->variation_anchor_group->get_root_template()
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' );

		$this->variation['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_block' )
			->with(
				[
					'id'         => 'google-listings-and-ads-product-onboarding-prompt',
					'blockName'  => 'google-listings-and-ads/product-onboarding-prompt',
					'attributes' => [ 'startUrl' => 'http://example.org/wp-admin/admin.php?page=wc-admin&path=/google/start' ],
				]
			);

		$this->product_blocks_service->hook_block_template( $this->simple_anchor_group );
		$this->product_blocks_service->hook_block_template( $this->variation_anchor_group );
	}

	public function test_hook_block_template_not_add_group_or_section() {
		$this->simple['template']
			->expects( $this->exactly( 0 ) )
			->method( 'add_group' );

		$this->simple['group']
			->expects( $this->exactly( 0 ) )
			->method( 'add_section' );

		$this->variation['template']
			->expects( $this->exactly( 0 ) )
			->method( 'add_group' );

		$this->variation['group']
			->expects( $this->exactly( 0 ) )
			->method( 'add_section' );

		// Here it intentionally calls with a mismatched template for each
		$this->product_blocks_service->hook_block_template( $this->mismatching_group );
		$this->product_blocks_service->hook_block_template( $this->mismatching_group );
	}

	/**
	 * Tests that assert the block configs passed to `add_block` are covered by
	 * `ChannelVisibilityBlockTest`.
	 */
	public function test_hook_block_template_add_channel_visibility_blocks() {
		$this->simple['visibility_section']
			->expects( $this->exactly( 1 ) )
			->method( 'add_block' );

		$this->simple['visibility_section']
			->expects( $this->exactly( 1 ) )
			->method( 'add_hide_condition' )
			->with( "editedProduct.type !== 'simple' && editedProduct.type !== 'variable'" );

		$this->simple['visibility_block']
			->expects( $this->exactly( 0 ) )
			->method( 'add_hide_condition' );

		$this->variation['visibility_section']
			->expects( $this->exactly( 0 ) )
			->method( 'add_block' );

		$this->variation['visibility_section']
			->expects( $this->exactly( 1 ) )
			->method( 'add_hide_condition' )
			->with( "editedProduct.type !== 'simple' && editedProduct.type !== 'variable'" );

		$this->variation['visibility_block']
			->expects( $this->exactly( 0 ) )
			->method( 'add_hide_condition' );

		$this->product_blocks_service->hook_block_template( $this->simple_anchor_group );
		$this->product_blocks_service->hook_block_template( $this->variation_anchor_group );
	}

	/**
	 * Tests that assert the block configs passed to `add_block` are covered by
	 * `InputTest` and `AttributeInputCollectionTest`.
	 */
	public function test_hook_block_template_add_attribute_blocks() {
		// The total number of attribute blocks to be added to the simple product template is 16
		$this->simple['attributes_section']
			->expects( $this->exactly( 16 ) )
			->method( 'add_block' );

		$this->simple['attributes_block']
			->expects( $this->exactly( 16 ) )
			->method( 'add_hide_condition' );

		// The total number of visible attribute blocks to be added to the variation product template is 15
		$this->variation['attributes_section']
			->expects( $this->exactly( 15 ) )
			->method( 'add_block' );

		$this->variation['attributes_block']
			->expects( $this->exactly( 0 ) )
			->method( 'add_hide_condition' );

		$this->product_blocks_service->hook_block_template( $this->simple_anchor_group );
		$this->product_blocks_service->hook_block_template( $this->variation_anchor_group );
	}

	public function test_register_custom_blocks() {
		$custom_blocks         = [ 'existing-block', 'non-existent-block' ];
		$expected_script_asset = new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads-product-blocks',
			'tests/data/blocks',
			GLA_TESTS_DATA_DIR . '/blocks.asset.php',
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( GLA_TESTS_DATA_DIR . '/blocks.js' ),
				]
			)
		);
		$expected_style_asset  = new AdminStyleAsset(
			'google-listings-and-ads-product-blocks-css',
			'tests/data/blocks',
			[],
			(string) filemtime( GLA_TESTS_DATA_DIR . '/blocks.css' )
		);

		$block_registry = $this->createMock( BlockRegistry::class );
		$block_registry
			->expects( $this->exactly( 1 ) )
			->method( 'register_block_type_from_metadata' )
			->with( $this->stringContains( 'tests/data/existing-block/block.json' ) );

		$this->assets_handler
			->expects( $this->exactly( 1 ) )
			->method( 'register_many' )
			->with( [ $expected_script_asset, $expected_style_asset ] );

		$this->assets_handler
			->expects( $this->exactly( 1 ) )
			->method( 'enqueue_many' )
			->with( [ $expected_script_asset, $expected_style_asset ] );

		$this->product_blocks_service->register_custom_blocks( $block_registry, GLA_TESTS_DATA_DIR, 'tests/data/blocks', $custom_blocks );
	}
}
