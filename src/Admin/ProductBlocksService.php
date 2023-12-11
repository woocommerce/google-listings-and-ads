<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesForm;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\PageController;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductBlocksService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class ProductBlocksService implements Service, Registerable, Conditional {

	use AdminConditional;
	use AttributesTrait;
	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var string[]
	 */
	protected const CUSTOM_BLOCKS = [
		'product-date-time-field',
		'product-select-field',
		'product-select-with-text-field',
	];

	/**
	 * ProductBlocksService constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param AttributeManager       $attribute_manager
	 * @param MerchantCenterService  $merchant_center
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, AttributeManager $attribute_manager, MerchantCenterService $merchant_center ) {
		$this->assets_handler    = $assets_handler;
		$this->attribute_manager = $attribute_manager;
		$this->merchant_center   = $merchant_center;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// compatibility-code "WC >= 8.4" -- The Block Template API used requires at least WooCommerce 8.4
		if ( ! version_compare( WC_VERSION, '8.4', '>=' ) ) {
			return;
		}

		if ( ! $this->merchant_center->is_setup_complete() || ! PageController::is_admin_page() ) {
			return;
		}

		add_action(
			'init',
			function () {
				$build_path = "{$this->get_root_dir()}/js/build";
				$uri        = 'js/build/blocks';
				$this->register_custom_blocks( $build_path, $uri, self::CUSTOM_BLOCKS );
			}
		);

		// https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/AbstractProductFormTemplate.php#L16
		$template_area = 'product-form';

		// https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/SimpleProductTemplate.php#L361
		$block_id = 'product-attributes-section';

		add_action(
			"woocommerce_block_template_area_{$template_area}_after_add_block_{$block_id}",
			function ( BlockInterface $attributes_section ) {
				// Please note that the simple and variable product types use the same product block template 'simple-product'.
				if ( 'simple-product' !== $attributes_section->get_root_template()->get_id() ) {
					return;
				}

				$section = $this->add_section( $attributes_section );
				$this->add_blocks( $section );
			}
		);

		// https://github.com/woocommerce/woocommerce/blob/8.3.0/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/ProductVariationTemplate.php#L161
		$block_id = 'product-variation-images-section';

		add_action(
			"woocommerce_block_template_area_{$template_area}_after_add_block_{$block_id}",
			function ( BlockInterface $images_section ) {
				if ( ! $this->is_variation_template( $images_section ) ) {
					return;
				}

				$section = $this->add_section( $images_section );
				$this->add_blocks( $section );
			}
		);
	}

	/**
	 * Register the custom blocks and their assets.
	 *
	 * @param string   $build_path    The absolute path to the build directory of the assets.
	 * @param string   $uri           The script URI of the custom blocks.
	 * @param string[] $custom_blocks The directory names of each custom block under the build path.
	 */
	public function register_custom_blocks( string $build_path, string $uri, array $custom_blocks ): void {
		foreach ( $custom_blocks as $custom_block ) {
			$block_json_file = "${build_path}/${custom_block}/block.json";

			if ( ! file_exists( $block_json_file ) ) {
				continue;
			}

			register_block_type( $block_json_file );
		}

		$asset = new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads-product-blocks',
			$uri,
			"${build_path}/blocks.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "${build_path}/blocks.js" ),
				]
			)
		);

		$this->assets_handler->register( $asset );
		$this->assets_handler->enqueue( $asset );
	}

	/**
	 * Add this extension's section block after the given reference section block.
	 *
	 * @param BlockInterface $reference_section The reference section block to add this extension's section block
	 */
	private function add_section( BlockInterface $reference_section ): BlockInterface {
		$group = $reference_section->get_parent();

		return $group->add_section(
			[
				'id'         => 'google-listings-and-ads-product-block-section',
				'order'      => $reference_section->get_order() + 1,
				'attributes' => [
					'title' => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
			]
		);
	}

	/**
	 * Add attribute blocks to the given section block.
	 *
	 * @param BlockInterface $section The section block to add attribute blocks
	 */
	private function add_blocks( BlockInterface $section ): void {
		$is_variation_template = $this->is_variation_template( $section );

		$product_types   = $is_variation_template ? [ 'variation' ] : $this->get_applicable_product_types();
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( $product_types );

		foreach ( $attribute_types as $attribute_type ) {
			$input_type = call_user_func( [ $attribute_type, 'get_input_type' ] );
			$input      = AttributesForm::init_input( new $input_type(), new $attribute_type() );

			// TODO: Remove this check after all attribute inputs have specified a block name
			if ( '' === $input->get_block_config()['blockName'] ) {
				continue;
			}

			if ( $is_variation_template ) {
				// When editing a variation, its product type on the frontend side won't be changed dynamically.
				// In addition, the property of `editedProduct.type` doesn't exist in the variation product.
				// Therefore, instead of using the ProductTemplates API `add_hide_condition` to conditionally
				// hide attributes, it doesn't add invisible attribute blocks from the beginning.
				if ( $this->is_visible_for_variation( $attribute_type ) ) {
					$section->add_block( $input->get_block_config() );
				}
			} else {
				// When editing a simple or variable product, its product type on the frontend side can be
				// changed dynamically. So, it needs to use the ProductTemplates API `add_hide_condition`
				// to conditionally hide attributes.
				$block = $section->add_block( $input->get_block_config() );
				$block->add_hide_condition( $this->get_hide_condition( $attribute_type ) );
			}
		}
	}

	/**
	 * Determine if the product block template of the given block is the variation template.
	 *
	 * @param BlockInterface $block The block to be checked
	 *
	 * @return boolean
	 */
	private function is_variation_template( BlockInterface $block ): bool {
		return 'product-variation' === $block->get_root_template()->get_id();
	}

	/**
	 * Determine if the given attribute is visible for variation product after applying related filters.
	 *
	 * @param string $attribute_type An attribute class extending AttributeInterface
	 *
	 * @return bool
	 */
	private function is_visible_for_variation( string $attribute_type ): bool {
		$attribute_product_types = AttributesForm::get_attribute_product_types( $attribute_type );

		return in_array( 'variation', $attribute_product_types['visible'], true );
	}

	/**
	 * Get the expression of the hide condition to an attribute's block based on its applicable product types.
	 * e.g. "editedProduct.type !== 'simple' && ! editedProduct.parent_id > 0"
	 *
	 * The hide condition is a JavaScript-like expression that will be evaluated on the client to determine if the block should be hidden.
	 * See [@woocommerce/expression-evaluation](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/expression-evaluation/README.md) for more details.
	 *
	 * @param string $attribute_type An attribute class extending AttributeInterface
	 *
	 * @return string
	 */
	public function get_hide_condition( string $attribute_type ): string {
		$attribute_product_types = AttributesForm::get_attribute_product_types( $attribute_type );

		$conditions = array_map(
			function ( $type ) {
				return "editedProduct.type !== '{$type}'";
			},
			$attribute_product_types['visible']
		);

		return implode( ' && ', $conditions );
	}
}
