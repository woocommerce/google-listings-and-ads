<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesForm;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\AttributesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\ChannelVisibilityBlock;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Admin\PageController;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductBlocksService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class ProductBlocksService implements Service, Registerable, Conditional {

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
	 * @var ChannelVisibilityBlock
	 */
	protected $channel_visibility_block;

	/**
	 * @var string[]
	 */
	protected const CUSTOM_BLOCKS = [
		'product-onboarding-prompt',
		'product-channel-visibility',
		'product-date-time-field',
		'product-select-field',
		'product-select-with-text-field',
	];

	/**
	 * ProductBlocksService constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param ChannelVisibilityBlock $channel_visibility_block
	 * @param AttributeManager       $attribute_manager
	 * @param MerchantCenterService  $merchant_center
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, ChannelVisibilityBlock $channel_visibility_block, AttributeManager $attribute_manager, MerchantCenterService $merchant_center ) {
		$this->assets_handler           = $assets_handler;
		$this->attribute_manager        = $attribute_manager;
		$this->merchant_center          = $merchant_center;
		$this->channel_visibility_block = $channel_visibility_block;
	}

	/**
	 * Return whether this service is needed to be registered.
	 *
	 * @return bool Whether this service is needed to be registered.
	 */
	public static function is_needed(): bool {
		// compatibility-code "WC >= 8.6" -- The Block Template API used requires at least WooCommerce 8.6
		return version_compare( WC_VERSION, '8.6', '>=' );
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( PageController::is_admin_page() ) {
			add_action( 'init', [ $this, 'hook_init' ] );
		}

		// https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/src/Internal/Features/ProductBlockEditor/ProductTemplates/AbstractProductFormTemplate.php#L19
		$template_area = 'product-form';

		// https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/src/Internal/Features/ProductBlockEditor/ProductTemplates/SimpleProductTemplate.php#L19
		// https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/src/Internal/Features/ProductBlockEditor/ProductTemplates/ProductVariationTemplate.php#L19
		$block_id = 'general';

		add_action(
			"woocommerce_block_template_area_{$template_area}_after_add_block_{$block_id}",
			[ $this, 'hook_block_template' ]
		);
	}

	/**
	 * Action handler for the 'init' hook.
	 */
	public function hook_init(): void {
		$build_path = "{$this->get_root_dir()}/js/build";
		$uri        = 'js/build/blocks';

		$this->register_custom_blocks( BlockRegistry::get_instance(), $build_path, $uri, self::CUSTOM_BLOCKS );
	}

	/**
	 * Action handler for the "woocommerce_block_template_area_{$template_area}_after_add_block_{$block_id}" hook.
	 *
	 * @param BlockInterface $block The block just added to get its root template to add this extension's group and blocks.
	 */
	public function hook_block_template( BlockInterface $block ): void {
		/** @var Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface */
		$template = $block->get_root_template();

		$is_variation_template = $this->is_variation_template( $block );

		// Please note that the simple, variable, grouped, and external product types
		// use the same product block template 'simple-product'. Their dynamic hidden
		// conditions are added below.
		if ( 'simple-product' !== $template->get_id() && ! $is_variation_template ) {
			return;
		}

		/** @var Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface */
		$group = $template->add_group(
			[
				'id'         => 'google-listings-and-ads-group',
				'order'      => 100,
				'attributes' => [
					'title' => __( 'Google for WooCommerce', 'google-listings-and-ads' ),
				],
			]
		);

		$visible_product_types = ProductSyncer::get_supported_product_types();

		if ( $is_variation_template ) {
			// The property of `editedProduct.type` doesn't exist in the variation product.
			// The condition returned from `get_hide_condition` won't work, so it uses 'true' directly.
			if ( ! in_array( 'variation', $visible_product_types, true ) ) {
				$group->add_hide_condition( 'true' );
			}
		} else {
			$group->add_hide_condition( $this->get_hide_condition( $visible_product_types ) );
		}

		if ( ! $this->merchant_center->is_setup_complete() ) {
			$group->add_block(
				[
					'id'         => 'google-listings-and-ads-product-onboarding-prompt',
					'blockName'  => 'google-listings-and-ads/product-onboarding-prompt',
					'attributes' => [
						'startUrl' => $this->get_start_url(),
					],
				]
			);

			return;
		}

		/** @var SectionInterface */
		$channel_visibility_section = $group->add_section(
			[
				'id'         => 'google-listings-and-ads-channel-visibility-section',
				'order'      => 1,
				'attributes' => [
					'title' => __( 'Channel visibility', 'google-listings-and-ads' ),
				],
			]
		);

		if ( ! $is_variation_template ) {
			$this->add_channel_visibility_block( $channel_visibility_section );
		}

		// Add the hidden condition to the channel visibility section because it only has one block.
		$visible_product_types = $this->channel_visibility_block->get_visible_product_types();
		$channel_visibility_section->add_hide_condition( $this->get_hide_condition( $visible_product_types ) );

		/** @var SectionInterface */
		$product_attributes_section = $group->add_section(
			[
				'id'         => 'google-listings-and-ads-product-attributes-section',
				'order'      => 2,
				'attributes' => [
					'title' => __( 'Product attributes', 'google-listings-and-ads' ),
				],
			]
		);

		$this->add_product_attribute_blocks( $product_attributes_section );
	}

	/**
	 * Register the custom blocks and their assets.
	 *
	 * @param BlockRegistry $block_registry BlockRegistry instance getting from Woo Core for registering custom blocks.
	 * @param string        $build_path     The absolute path to the build directory of the assets.
	 * @param string        $uri            The script URI of the custom blocks.
	 * @param string[]      $custom_blocks  The directory names of each custom block under the build path.
	 */
	public function register_custom_blocks( BlockRegistry $block_registry, string $build_path, string $uri, array $custom_blocks ): void {
		foreach ( $custom_blocks as $custom_block ) {
			$block_json_file = "{$build_path}/{$custom_block}/block.json";

			if ( ! file_exists( $block_json_file ) ) {
				continue;
			}

			$block_registry->register_block_type_from_metadata( $block_json_file );
		}

		$assets[] = new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads-product-blocks',
			$uri,
			"{$build_path}/blocks.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$build_path}/blocks.js" ),
				]
			)
		);

		$assets[] = new AdminStyleAsset(
			'google-listings-and-ads-product-blocks-css',
			$uri,
			[],
			(string) filemtime( "{$build_path}/blocks.css" )
		);

		$this->assets_handler->register_many( $assets );
		$this->assets_handler->enqueue_many( $assets );
	}

	/**
	 * Add the channel visibility block to the given section block.
	 *
	 * @param SectionInterface $section The section block to add the channel visibility block
	 */
	private function add_channel_visibility_block( SectionInterface $section ): void {
		$section->add_block( $this->channel_visibility_block->get_block_config() );
	}

	/**
	 * Add product attribute blocks to the given section block.
	 *
	 * @param SectionInterface $section The section block to add product attribute blocks
	 */
	private function add_product_attribute_blocks( SectionInterface $section ): void {
		$is_variation_template = $this->is_variation_template( $section );

		$product_types   = $is_variation_template ? [ 'variation' ] : $this->get_applicable_product_types();
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_types( $product_types );

		foreach ( $attribute_types as $attribute_type ) {
			$input_type = call_user_func( [ $attribute_type, 'get_input_type' ] );
			$input      = AttributesForm::init_input( new $input_type(), new $attribute_type() );

			// Avoid to render Inputs that are defined as hidden in the Input.
			// i.e We don't render GTIN for new WC versions anymore.
			if ( $input->is_hidden() ) {
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
				$visible_product_types = AttributesForm::get_attribute_product_types( $attribute_type )['visible'];

				// When editing a simple, variable, grouped, or external product, its product type on the
				// frontend side can be changed dynamically. So, it needs to use the ProductTemplates API
				// `add_hide_condition` to conditionally hide attributes.
				/** @var BlockInterface */
				$block = $section->add_block( $input->get_block_config() );
				$block->add_hide_condition( $this->get_hide_condition( $visible_product_types ) );
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
	 * Get the expression of the hide condition to a block based on the visible product types.
	 * e.g. "editedProduct.type !== 'simple' && ! editedProduct.parent_id > 0"
	 *
	 * The hide condition is a JavaScript-like expression that will be evaluated on the client to determine if the block should be hidden.
	 * See [@woocommerce/expression-evaluation](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/expression-evaluation/README.md) for more details.
	 *
	 * @param array $visible_product_types The visible product types to be converted to a hidden condition
	 *
	 * @return string
	 */
	public function get_hide_condition( array $visible_product_types ): string {
		$conditions = array_map(
			function ( $type ) {
				return "editedProduct.type !== '{$type}'";
			},
			$visible_product_types
		);

		return implode( ' && ', $conditions ) ?: 'true';
	}
}
