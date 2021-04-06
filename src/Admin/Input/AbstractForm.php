<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
abstract class AbstractForm implements FormInterface {

	/**
	 * Return the form's submitted data.
	 *
	 * @return array
	 */
	public function get_data(): array {
		// phpcs:disable WordPress.Security.NonceVerification
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = ! empty( $_POST[ $this->get_name() ] ) ? (array) wc_clean( wp_unslash( $_POST[ $this->get_name() ] ) ) : [];

		return wp_unslash( $data );
	}

	/**
	 * @param array $args
	 *
	 * @return InputInterface[]
	 */
	protected function get_filled_inputs( array $args ): array {
		$data = $this->get_data();
		if ( empty( $data ) ) {
			return [];
		}

		$inputs = [];
		foreach ( $this->get_inputs( $args ) as $input ) {
			$data_key = $input->get_name();
			if ( $input instanceof SelectWithTextInput && ! empty( $data[ $data_key ] ) && SelectWithTextInput::CUSTOM_VALUE_KEY === $data[ $data_key ] ) {
				$data_key = sprintf( '%s_%s', $input->get_name(), SelectWithTextInput::CUSTOM_VALUE_KEY );
			}

			if ( ! empty( $data[ $data_key ] ) ) {
				$input->set_value( $data[ $data_key ] );
				$inputs[ $input->get_name() ] = $input;
			}
		}

		return $inputs;
	}

}
