<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\FormInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\ViewHelperTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;

defined( 'ABSPATH' ) || exit;

/**
 * Class TabInitializer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class TabInitializer implements Service, Registerable, Conditional {

	use AdminConditional;
	use ViewHelperTrait;

	/**
	 * @var Admin
	 */
	protected $admin;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * TabInitializer constructor.
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
		$view_data  = [
			'form'       => $this->get_form( $product_id ),
			'product_id' => $product_id,
		];
		echo wp_kses( $this->admin->get_view( 'attributes/tab-panel', $view_data ), $this->get_allowed_html_form_tags() );
	}

	/**
	 * Handle form submission and update the product attributes.
	 *
	 * @param int $product_id
	 */
	private function handle_update_product( int $product_id ) {
		$form = $this->get_form( $product_id );

		// phpcs:disable WordPress.Security.NonceVerification
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted_data = ! empty( $_POST[ $form->get_name() ] ) ? (array) wc_clean( wp_unslash( $_POST[ $form->get_name() ] ) ) : [];

		$this->submit_form( $form, $product_id, $submitted_data );
	}

	/**
	 * @param int $product_id
	 *
	 * @return InputForm
	 */
	protected function get_form( int $product_id ): InputForm {
		$form_data = [
			GTIN::get_id() => $this->attribute_manager->get_value( $product_id, GTIN::get_id() ),
			MPN::get_id()  => $this->attribute_manager->get_value( $product_id, MPN::get_id() ),
		];

		$form = new InputForm();
		$form->set_data( $form_data );

		return $form;
	}

	/**
	 * @param FormInterface $form
	 * @param int           $product_id
	 * @param array         $submitted_data
	 *
	 * @return void
	 */
	protected function submit_form( FormInterface $form, int $product_id, array $submitted_data ): void {
		$form->submit( $submitted_data );
		$form_data = $form->get_data();

		// gtin
		if ( ! empty( $form_data[ GTIN::get_id() ] ) ) {
			$this->attribute_manager->update( $product_id, new GTIN( $form_data[ GTIN::get_id() ] ) );
		}

		// mpn
		if ( ! empty( $form_data[ MPN::get_id() ] ) ) {
			$this->attribute_manager->update( $product_id, new MPN( $form_data[ MPN::get_id() ] ) );
		}
	}

}
