<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product\Attributes;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesBlock;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesTrait;
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
 * Class AttributesBlockTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Product\Attributes
 */
class AttributesBlockTest extends ContainerAwareUnitTest {

	use AttributesTrait;

	/** @var AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var AttributeManager $attribute_manager */
	protected $attribute_manager;

	/** @var Stub|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|BlockInterface $simple_anchor_block */
	protected $simple_anchor_block;

	/** @var MockObject|BlockInterface $variation_anchor_block */
	protected $variation_anchor_block;

	/** @var MockObject|SectionInterface $simple_gla_section */
	protected $simple_gla_section;

	/** @var MockObject|SectionInterface $variation_gla_section */
	protected $variation_gla_section;

	/** @var AttributesBlock $attributes_block */
	protected $attributes_block;

	/** @var bool $is_mc_setup_complete */
	protected $is_mc_setup_complete;

	protected const SIMPLE_ATTRIBUTES_SECTION_HOOK = 'woocommerce_block_template_area_product-form_after_add_block_product-attributes-section';
	protected const VARIATION_IMAGES_SECTION_HOOK  = 'woocommerce_block_template_area_product-form_after_add_block_product-variation-images-section';

	public function setUp(): void {
		// compatibility-code "WC >= 8.3" -- The Block Template API used requires at least WooCommerce 8.3
		if ( ! version_compare( WC_VERSION, '8.3', '>=' ) ) {
			$this->markTestSkipped( 'This test suite requires WooCommerce version >= 8.3' );
		}

		parent::setUp();

		$this->assets_handler    = $this->createStub( AssetsHandlerInterface::class );
		$this->attribute_manager = $this->container->get( AttributeManager::class );
		$this->merchant_center   = $this->createStub( MerchantCenterService::class );

		$this->simple_anchor_block    = $this->createMock( BlockInterface::class );
		$this->variation_anchor_block = $this->createMock( BlockInterface::class );

		$this->attributes_block = new AttributesBlock( $this->assets_handler, $this->attribute_manager, $this->merchant_center );

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

		$this->simple_gla_section    = $this->setUpBlockMock( $this->simple_anchor_block, 'simple-product', 10 );
		$this->variation_gla_section = $this->setUpBlockMock( $this->variation_anchor_block, 'product-variation', 20 );
	}

	private function setUpBlockMock( MockObject $anchor_block, string $template_id, int $order ) {
		$root_template = $this->createStub( BlockTemplateInterface::class );
		$group_block   = $this->createStub( GroupInterface::class );
		$gla_section   = $this->createMock( SectionInterface::class );
		$gla_block     = $this->createMock( BlockInterface::class );

		$root_template->method( 'get_id' )->willReturn( $template_id );
		$group_block->method( 'add_section' )->willReturn( $gla_section );
		$gla_section->method( 'get_root_template' )->willReturn( $root_template );
		$gla_section->method( 'add_block' )->willReturn( $gla_block );
		$gla_section->method( 'get_block' )->willReturn( $gla_block );

		$anchor_block->method( 'get_root_template' )->willReturn( $root_template );
		$anchor_block->method( 'get_parent' )->willReturn( $group_block );
		$anchor_block->method( 'get_order' )->willReturn( $order );

		return $gla_section;
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
			$this->attributes_block->get_hide_condition( Adult::class )
		);

		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variable'",
			$this->attributes_block->get_hide_condition( Brand::class )
		);

		$this->assertEquals(
			"editedProduct.type !== 'simple' && editedProduct.type !== 'variation'",
			$this->attributes_block->get_hide_condition( Gender::class )
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
			$this->attributes_block->get_hide_condition( Gender::class )
		);
	}

	public function test_register_merchant_center_setup_is_not_complete() {
		$this->is_mc_setup_complete = false;

		$this->simple_anchor_block
			->expects( $this->exactly( 0 ) )
			->method( 'get_root_template' );

		$this->variation_anchor_block
			->expects( $this->exactly( 0 ) )
			->method( 'get_root_template' );

		$this->attributes_block->register();

		do_action( self::SIMPLE_ATTRIBUTES_SECTION_HOOK, $this->simple_anchor_block );
		do_action( self::VARIATION_IMAGES_SECTION_HOOK, $this->variation_anchor_block );
	}

	public function test_register_is_not_admin_page() {
		unset( $_GET['page'] );

		$this->simple_anchor_block
			->expects( $this->exactly( 0 ) )
			->method( 'get_root_template' );

		$this->variation_anchor_block
			->expects( $this->exactly( 0 ) )
			->method( 'get_root_template' );

		$this->attributes_block->register();

		do_action( self::SIMPLE_ATTRIBUTES_SECTION_HOOK, $this->simple_anchor_block );
		do_action( self::VARIATION_IMAGES_SECTION_HOOK, $this->variation_anchor_block );
	}

	public function test_register_merchant_center_setup_is_complete_and_is_admin_page() {
		$this->simple_anchor_block
			->expects( $this->exactly( 1 ) )
			->method( 'get_root_template' );

		$this->variation_anchor_block
			->expects( $this->exactly( 1 ) )
			->method( 'get_root_template' );

		$this->attributes_block->register();

		do_action( self::SIMPLE_ATTRIBUTES_SECTION_HOOK, $this->simple_anchor_block );
		do_action( self::VARIATION_IMAGES_SECTION_HOOK, $this->variation_anchor_block );
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

		$this->attributes_block->register_custom_blocks( GLA_TESTS_DATA_DIR, 'tests/data/blocks', $custom_blocks );
	}

	public function test_register_not_add_section() {
		$this->simple_anchor_block->get_parent()
			->expects( $this->exactly( 0 ) )
			->method( 'add_section' );

		$this->variation_anchor_block->get_parent()
			->expects( $this->exactly( 0 ) )
			->method( 'add_section' );

		$this->attributes_block->register();

		// Here it intentionally calls with a mismatched template for each
		do_action( self::SIMPLE_ATTRIBUTES_SECTION_HOOK, $this->variation_anchor_block );
		do_action( self::VARIATION_IMAGES_SECTION_HOOK, $this->simple_anchor_block );
	}

	public function test_register_add_section() {
		$this->simple_anchor_block->get_parent()
			->expects( $this->exactly( 1 ) )
			->method( 'add_section' )
			->with(
				[
					'id'         => 'google-listings-and-ads-product-block-section',
					'order'      => 11,
					'attributes' => [
						'title' => 'Google Listings & Ads',
					],
				]
			);

		$this->variation_anchor_block->get_parent()
			->expects( $this->exactly( 1 ) )
			->method( 'add_section' )
			->with(
				[
					'id'         => 'google-listings-and-ads-product-block-section',
					'order'      => 21,
					'attributes' => [
						'title' => 'Google Listings & Ads',
					],
				]
			);

		$this->attributes_block->register();

		do_action( self::SIMPLE_ATTRIBUTES_SECTION_HOOK, $this->simple_anchor_block );
		do_action( self::VARIATION_IMAGES_SECTION_HOOK, $this->variation_anchor_block );
	}

	/**
	 * Tests that assert the block configs passed to `add_block` are covered by
	 * `InputTest` and `AttributeInputCollectionTest`.
	 */
	public function test_register_add_blocks() {
		// The total number of blocks to be added to the simple product template is 16,
		// and the converted number so far is 8
		$this->simple_gla_section
			->expects( $this->exactly( 8 ) )
			->method( 'add_block' );

		$this->simple_gla_section->get_block( 'mocked-singleton' )
			->expects( $this->exactly( 8 ) )
			->method( 'add_hide_condition' );

		// The total number of visible blocks to be added to the variation product template is 15,
		// and the converted number so far is 7
		$this->variation_gla_section
			->expects( $this->exactly( 7 ) )
			->method( 'add_block' );

		$this->variation_gla_section->get_block( 'mocked-singleton' )
			->expects( $this->exactly( 0 ) )
			->method( 'add_hide_condition' );

		$this->attributes_block->register();

		do_action( self::SIMPLE_ATTRIBUTES_SECTION_HOOK, $this->simple_anchor_block );
		do_action( self::VARIATION_IMAGES_SECTION_HOOK, $this->variation_anchor_block );
	}
}
