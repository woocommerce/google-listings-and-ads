<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributesTab
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class AttributesTab implements Service, Registerable, Conditional {

	use AdminConditional;

	/**
	 * @var Admin
	 */
	protected $admin;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * AttributesTab constructor.
	 *
	 * @param Admin            $admin
	 * @param AttributeManager $attribute_manager
	 */
	public function __construct( Admin $admin, AttributeManager $attribute_manager ) {
		$this->admin             = $admin;
		$this->attribute_manager = $attribute_manager;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_new_product',
			function ( int $product_id, WC_Product $product ) {
				$this->handle_update_product( $product );
			},
			10,
			2
		);
		add_action(
			'woocommerce_update_product',
			function ( int $product_id, WC_Product $product ) {
				$this->handle_update_product( $product );
			},
			10,
			2
		);

		add_action(
			'woocommerce_product_data_tabs',
			function ( array $tabs ) {
				return $this->add_tab( $tabs );
			}
		);
		add_action(
			'woocommerce_product_data_panels',
			function () {
				$this->render_panel();
			}
		);
	}

	/**
	 * Adds the Google Listing & Ads tab to the WooCommerce product data box.
	 *
	 * @param array $tabs The current product data tabs.
	 *
	 * @return array An array with product tabs with the Yoast SEO tab added.
	 */
	private function add_tab( array $tabs ): array {
		$product_types   = array_keys( $this->attribute_manager->get_attribute_types_map() );
		$show_if_classes = ! empty( $product_types ) ? ' show_if_' . join( ' show_if_', $product_types ) : '';

		$tabs['gla_attributes'] = [
			'label'  => 'Google Listings and Ads',
			'class'  => 'gla' . $show_if_classes,
			'target' => 'gla_attributes',
		];

		return $tabs;
	}

	/**
	 * Render the product attributes tab.
	 */
	private function render_panel() {
		$product = wc_get_product( get_the_ID() );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->admin->get_view( 'attributes/tab-panel', [ 'form' => $this->get_form( $product )->get_view_data() ] );
	}

	/**
	 * Handle form submission and update the product attributes.
	 *
	 * @param WC_Product $product
	 */
	private function handle_update_product( WC_Product $product ) {
		$form = $this->get_form( $product );

		$form_view_data = $form->get_view_data();
		// phpcs:disable WordPress.Security.NonceVerification
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted_data = ! empty( $_POST[ $form_view_data['name'] ] ) ? (array) wc_clean( wp_unslash( $_POST[ $form_view_data['name'] ] ) ) : [];

		$form->submit( $submitted_data );
		$this->update_data( $product, $form->get_data() );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return AttributesForm
	 */
	protected function get_form( WC_Product $product ): AttributesForm {
		$attribute_types = $this->attribute_manager->get_attribute_types_for_product_type( 'simple' );
		$attribute_types = array_merge( $attribute_types, $this->attribute_manager->get_attribute_types_for_product_type( 'variable' ) );

		$form = new AttributesForm( $attribute_types, $this->attribute_manager->get_all_values( $product ) );
		$form->set_name( 'attributes' );

		return $form;
	}

	/**
	 * @param WC_Product $product
	 * @param array      $data
	 *
	 * @return void
	 */
	protected function update_data( WC_Product $product, array $data ): void {
		foreach ( $this->attribute_manager->get_attribute_types_for_product( $product ) as $attribute_id => $attribute_type ) {
			if ( isset( $data[ $attribute_id ] ) ) {
				$this->attribute_manager->update( $product, new $attribute_type( $data[ $attribute_id ] ) );
			}
		}
	}

}
