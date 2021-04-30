<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class VariationsAttributes
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class VariationsAttributes implements Service, Registerable, Conditional {

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
	 * VariationsAttributes constructor.
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
			'woocommerce_product_after_variable_attributes',
			function ( int $variation_index, array $variation_data, WP_Post $variation ) {
				$this->render_attributes_form( $variation_index, $variation_data, $variation );
			},
			90,
			3
		);
		add_action(
			'woocommerce_save_product_variation',
			function ( int $variation_id, int $variation_index ) {
				$this->handle_save_variation( $variation_id, $variation_index );
			},
			10,
			2
		);
	}

	/**
	 * Render the attributes form for variations.
	 *
	 * @param int     $variation_index Position in the loop.
	 * @param array   $variation_data  Variation data.
	 * @param WP_Post $variation       Post data.
	 */
	private function render_attributes_form( int $variation_index, array $variation_data, WP_Post $variation ) {
		$product_id = $variation->ID;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->admin->get_view( 'attributes/variations-form', $this->get_form( $product_id, $variation_index )->get_view_data() );
	}

	/**
	 * Handle form submission and update the product attributes.
	 *
	 * @param int $variation_id
	 * @param int $variation_index
	 */
	private function handle_save_variation( int $variation_id, int $variation_index ) {
		$form = $this->get_form( $variation_id, $variation_index );

		$form_view_data = $form->get_view_data();
		// phpcs:disable WordPress.Security.NonceVerification
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted_data = ! empty( $_POST[ $form_view_data['name'] ] ) ? (array) wc_clean( wp_unslash( $_POST[ $form_view_data['name'] ] ) ) : [];

		$form->submit( $submitted_data );
		$form_data = $form->get_data();

		if ( ! empty( $form_data[ $variation_index ] ) ) {
			$this->update_data( $form_data[ $variation_index ], $variation_id );
		}
	}

	/**
	 * @param int $variation_id
	 * @param int $variation_index
	 *
	 * @return VariationAttributesForm
	 */
	protected function get_form( int $variation_id, int $variation_index ): VariationAttributesForm {
		$form_data = [
			(string) $variation_index => [
				GTIN::get_id() => $this->attribute_manager->get_value( $variation_id, GTIN::get_id() ),
				MPN::get_id()  => $this->attribute_manager->get_value( $variation_id, MPN::get_id() ),
			],
		];

		return new VariationAttributesForm( $variation_index, $form_data );
	}

	/**
	 * @param array $data
	 * @param int   $variation_id
	 *
	 * @return void
	 */
	protected function update_data( array $data, int $variation_id ): void {
		// gtin
		if ( isset( $data[ GTIN::get_id() ] ) ) {
			$this->attribute_manager->update( $variation_id, new GTIN( $data[ GTIN::get_id() ] ) );
		}

		// mpn
		if ( isset( $data[ MPN::get_id() ] ) ) {
			$this->attribute_manager->update( $variation_id, new MPN( $data[ MPN::get_id() ] ) );
		}
	}

}
