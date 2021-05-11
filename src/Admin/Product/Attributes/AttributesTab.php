<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\FormInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;

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
			function ( int $product_id ) {
				$this->handle_update_product( $product_id );
			}
		);
		add_action(
			'woocommerce_update_product',
			function ( int $product_id ) {
				$this->handle_update_product( $product_id );
			}
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
		$tabs['gla_attributes'] = [
			'label'  => 'Google Listings and Ads',
			'class'  => 'gla',
			'target' => 'gla_attributes',
		];

		return $tabs;
	}

	/**
	 * Render the product attributes tab.
	 */
	private function render_panel() {
		$product_id = get_the_ID();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->admin->get_view( 'attributes/tab-panel', [ 'form' => $this->get_form( $product_id )->get_view_data() ] );
	}

	/**
	 * Handle form submission and update the product attributes.
	 *
	 * @param int $product_id
	 */
	private function handle_update_product( int $product_id ) {
		$form = $this->get_form( $product_id );

		$form_view_data = $form->get_view_data();
		// phpcs:disable WordPress.Security.NonceVerification
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted_data = ! empty( $_POST[ $form_view_data['name'] ] ) ? (array) wc_clean( wp_unslash( $_POST[ $form_view_data['name'] ] ) ) : [];

		$form->submit( $submitted_data );
		$this->update_data( $product_id, $form->get_data() );
	}

	/**
	 * @param int $product_id
	 *
	 * @return AttributesTabForm
	 */
	protected function get_form( int $product_id ): AttributesTabForm {
		$form_data = [
			Brand::get_id()  => $this->attribute_manager->get_value( $product_id, Brand::get_id() ),
			GTIN::get_id()   => $this->attribute_manager->get_value( $product_id, GTIN::get_id() ),
			MPN::get_id()    => $this->attribute_manager->get_value( $product_id, MPN::get_id() ),
			Gender::get_id() => $this->attribute_manager->get_value( $product_id, Gender::get_id() ),
		];

		return new AttributesTabForm( $form_data );
	}

	/**
	 * @param int   $product_id
	 * @param array $data
	 *
	 * @return void
	 */
	protected function update_data( int $product_id, array $data ): void {
		// gtin
		if ( isset( $data[ GTIN::get_id() ] ) ) {
			$this->attribute_manager->update( $product_id, new GTIN( $data[ GTIN::get_id() ] ) );
		}

		// mpn
		if ( isset( $data[ MPN::get_id() ] ) ) {
			$this->attribute_manager->update( $product_id, new MPN( $data[ MPN::get_id() ] ) );
		}

		// brand
		if ( isset( $data[ Brand::get_id() ] ) ) {
			$this->attribute_manager->update( $product_id, new Brand( $data[ Brand::get_id() ] ) );
		}

		// gender
		if ( isset( $data[ Gender::get_id() ] ) ) {
			$this->attribute_manager->update( $product_id, new Gender( $data[ Gender::get_id() ] ) );
		}
	}

}
