<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\ProductBlocksService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
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

	/** @var AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

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
		// compatibility-code "WC >= 8.4" -- The Block Template API used requires at least WooCommerce 8.4
		if ( ! version_compare( WC_VERSION, '8.4', '>=' ) ) {
			$this->markTestSkipped( 'This test suite requires WooCommerce version >= 8.4' );
		}

		parent::setUp();

		$this->assets_handler    = $this->createStub( AssetsHandlerInterface::class );
		$this->attribute_manager = $this->container->get( AttributeManager::class );
		$this->merchant_center   = $this->createStub( MerchantCenterService::class );

		$this->simple_anchor_group    = $this->createMock( BlockInterface::class );
		$this->variation_anchor_group = $this->createMock( BlockInterface::class );
		$this->mismatching_group      = $this->createMock( BlockInterface::class );

		$this->product_blocks_service = new ProductBlocksService( $this->assets_handler, $this->attribute_manager, $this->merchant_center );

		// Set up stubs and mocks
		$this->is_mc_setup_complete = true;
		$this->merchant_center
			->method( 'is_setup_complete' )
			->willReturnCallback(
				function () {
					return $this->is_mc_setup_complete;
				}
			);

		// Ref: https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce/src/Admin/PageController.php#L555-L562
		$_GET['page'] = PageController::PAGE_ROOT;

		$this->simple    = $this->setUpBlockMock( $this->simple_anchor_group, 'simple-product' );
		$this->variation = $this->setUpBlockMock( $this->variation_anchor_group, 'product-variation' );
		$this->setUpBlockMock( $this->mismatching_group, 'mismatching-template' );
	}

	private function setUpBlockMock( MockObject $anchor_group, string $template_id ) {
		$template = $this->createStub( ProductFormTemplateInterface::class );
		$group    = $this->createStub( GroupInterface::class );

		$attributes_section = $this->createMock( SectionInterface::class );
		$attributes_block   = $this->createMock( BlockInterface::class );

		$template->method( 'get_id' )->willReturn( $template_id );
		$template->method( 'add_group' )->willReturn( $group );

		$attributes_section->method( 'get_root_template' )->willReturn( $template );
		$attributes_section->method( 'add_block' )->willReturn( $attributes_block );

		$anchor_group->method( 'get_root_template' )->willReturn( $template );

		$group
			->method( 'add_section' )
			->willReturnCallback(
				function ( array $config ) use ( $attributes_section ) {
					if ( 'google-listings-and-ads-product-attributes-section' === $config['id'] ) {
						return $attributes_section;
					}
				}
			);

		return [
			'template'           => $template,
			'group'              => $group,
			'attributes_section' => $attributes_section,
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
			$this->product_blocks_service->get_hide_condition( Adult::class )
		);

		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variable'",
			$this->product_blocks_service->get_hide_condition( Brand::class )
		);

		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variation'",
			$this->product_blocks_service->get_hide_condition( Gender::class )
		);

		add_filter(
			'woocommerce_gla_attribute_hidden_product_types_gender',
			function ( array $applicable_types ) {
				$applicable_types[] = 'simple';
				return $applicable_types;
			}
		);

		$this->assertEquals(
			"editedProduct.type !== 'variation'",
			$this->product_blocks_service->get_hide_condition( Gender::class )
		);
	}

	public function test_register_merchant_center_setup_is_not_complete() {
		$this->is_mc_setup_complete = false;

		$this->simple_anchor_group->get_root_template()
			->expects( $this->exactly( 0 ) )
			->method( 'add_group' );

		$this->variation_anchor_group->get_root_template()
			->expects( $this->exactly( 0 ) )
			->method( 'add_group' );

		$this->product_blocks_service->register();

		do_action( self::GENERAL_GROUP_HOOK, $this->simple_anchor_group );
		do_action( self::GENERAL_GROUP_HOOK, $this->variation_anchor_group );
	}

	public function test_register_is_not_admin_page() {
		unset( $_GET['page'] );

		$this->simple_anchor_group->get_root_template()
			->expects( $this->exactly( 0 ) )
			->method( 'add_group' );

		$this->variation_anchor_group->get_root_template()
			->expects( $this->exactly( 0 ) )
			->method( 'add_group' );

		$this->product_blocks_service->register();

		do_action( self::GENERAL_GROUP_HOOK, $this->simple_anchor_group );
		do_action( self::GENERAL_GROUP_HOOK, $this->variation_anchor_group );
	}

	public function test_register_merchant_center_setup_is_complete_and_is_admin_page() {
		$this->simple_anchor_group->get_root_template()
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' );

		$this->variation_anchor_group->get_root_template()
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' );

		$this->product_blocks_service->register();

		do_action( self::GENERAL_GROUP_HOOK, $this->simple_anchor_group );
		do_action( self::GENERAL_GROUP_HOOK, $this->variation_anchor_group );
	}

	public function test_register_custom_blocks() {
		$custom_blocks  = [ 'existing-block', 'non-existent-block' ];
		$expected_asset = new AdminScriptWithBuiltDependenciesAsset(
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

		$this->assets_handler
			->expects( $this->exactly( 1 ) )
			->method( 'register' )
			->with( $expected_asset );

		$this->assets_handler
			->expects( $this->exactly( 1 ) )
			->method( 'enqueue' )
			->with( $expected_asset );

		$this->product_blocks_service->register_custom_blocks( GLA_TESTS_DATA_DIR, 'tests/data/blocks', $custom_blocks );
	}

	public function test_register_not_add_group_or_section() {
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

		$this->product_blocks_service->register();

		// Here it intentionally calls with a mismatched template for each
		do_action( self::GENERAL_GROUP_HOOK, $this->mismatching_group );
		do_action( self::GENERAL_GROUP_HOOK, $this->mismatching_group );
	}

	public function test_register_add_group_and_sections() {
		$this->simple['template']
			->expects( $this->exactly( 1 ) )
			->method( 'add_group' )->with(
				[
					'id'         => 'google-listings-and-ads-group',
					'order'      => 100,
					'attributes' => [
						'title' => 'Google Listings & Ads',
					],
				]
			);

		$this->simple['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_section' )
			->withConsecutive(
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
						'title' => 'Google Listings & Ads',
					],
				]
			);

		$this->variation['group']
			->expects( $this->exactly( 1 ) )
			->method( 'add_section' )
			->withConsecutive(
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

		$this->product_blocks_service->register();

		do_action( self::GENERAL_GROUP_HOOK, $this->simple_anchor_group );
		do_action( self::GENERAL_GROUP_HOOK, $this->variation_anchor_group );
	}

	/**
	 * Tests that assert the block configs passed to `add_block` are covered by
	 * `InputTest` and `AttributeInputCollectionTest`.
	 */
	public function test_register_add_blocks() {
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

		$this->product_blocks_service->register();

		do_action( self::GENERAL_GROUP_HOOK, $this->simple_anchor_group );
		do_action( self::GENERAL_GROUP_HOOK, $this->variation_anchor_group );
	}
}
