<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class VariationsFormInitializer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class VariationsFormInitializer extends TabInitializer {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_product_after_variable_attributes',
			function ( int $loop, array $variation_data, WP_Post $variation ) {
				$this->render_attributes_form( $loop, $variation_data, $variation );
			},
			90,
			3
		);
		add_action(
			'woocommerce_save_product_variation',
			function ( int $variation_id, int $idx ) {
				$this->handle_save_variation( $variation_id, $idx );
			},
			10,
			2
		);
	}

	/**
	 * Render the attributes form for variations.
	 *
	 * @param int     $loop           Position in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Post data.
	 */
	private function render_attributes_form( int $loop, array $variation_data, WP_Post $variation ) {
		$product_id = $variation->ID;

		$data = [
			'form'         => $this->get_form( $product_id ),
			'variation_id' => $variation->ID,
			'loop_index'   => $loop,
		];
		echo wp_kses( $this->admin->get_view( 'attributes/variations-form', $data ), $this->get_allowed_html_form_tags() );
	}

	/**
	 * Handle form submission and update the product attributes.
	 *
	 * @param int $variation_id
	 * @param int $idx
	 */
	private function handle_save_variation( int $variation_id, int $idx ) {
		$form = $this->get_form( $variation_id );

		// phpcs:disable WordPress.Security.NonceVerification
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted_data = ! empty( $_POST[ $form->get_name() ][ $idx ] ) ? (array) wc_clean( wp_unslash( $_POST[ $form->get_name() ][ $idx ] ) ) : [];

		$this->submit_form( $form, $variation_id, $submitted_data );
	}

}
