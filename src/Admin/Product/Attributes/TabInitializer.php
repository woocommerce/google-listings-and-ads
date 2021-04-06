<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\ViewHelperTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Product;

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
	 * @var InputForm
	 */
	protected $input_form;

	/**
	 * TabInitializer constructor.
	 *
	 * @param Admin     $admin
	 * @param InputForm $input_form
	 */
	public function __construct( Admin $admin, InputForm $input_form ) {
		$this->admin      = $admin;
		$this->input_form = $input_form;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_product', [ $this, 'handle_update_product' ] );
		add_action( 'woocommerce_update_product', [ $this, 'handle_update_product' ] );

		add_action( 'woocommerce_product_data_tabs', [ $this, 'add_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'render_panel' ] );
	}

	/**
	 * Adds the Google Listing & Ads tab to the WooCommerce product data box.
	 *
	 * @param array $tabs The current product data tabs.
	 *
	 * @return array An array with product tabs with the Yoast SEO tab added.
	 */
	public function add_tab( array $tabs ): array {
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
	public function render_panel() {
		$data = [
			'form'       => $this->input_form,
			'product_id' => get_the_ID(),
		];
		echo wp_kses( $this->admin->get_view( 'attributes/tab-panel', $data ), $this->get_allowed_html_form_tags() );
	}

	/**
	 * Handle form submission and update the product attributes.
	 *
	 * @param int $product_id
	 */
	public function handle_update_product( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			// bail if it's not a WooCommerce product.
			return;
		}

		$this->input_form->submit( [ 'product_id' => $product_id ] );
	}

}
