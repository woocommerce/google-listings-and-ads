<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
abstract class AbstractForm implements FormInterface {

	use ValidateInterface;

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var InputInterface[]
	 */
	protected $inputs;

	/**
	 * AbstractForm constructor.
	 *
	 * @param InputInterface[] $inputs
	 */
	public function __construct( array $inputs ) {
		foreach ( $inputs as $input ) {
			$this->validate_instanceof( $input, InputInterface::class );
		}

		$this->inputs = $inputs;
	}

	/**
	 * Return the form's data.
	 *
	 * @return array
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Set the form's data.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function set_data( array $data = [] ): void {
		if ( empty( $data ) ) {
			$this->data = [];
			return;
		}

		$_data = [];
		foreach ( $this->inputs as $input ) {
			$data_key    = $input->get_name();
			$input_value = $data[ $data_key ] ?? null;
			if ( $input instanceof SelectWithTextInput && ! empty( $input_value ) && SelectWithTextInput::CUSTOM_VALUE_KEY === $input_value ) {
				$custom_data_key = sprintf( '%s_%s', $input->get_name(), SelectWithTextInput::CUSTOM_VALUE_KEY );
				$input_value     = $data[ $custom_data_key ] ?? null;
			}

			if ( null !== $input_value ) {
				$input->set_value( $input_value );
				$_data[ $data_key ] = $input_value;
			}
		}

		$this->data = $_data;
	}

	/**
	 * @return InputInterface[]
	 */
	public function get_inputs(): array {
		return $this->inputs;
	}

	/**
	 * Submit the form.
	 *
	 * @param array $submitted_data
	 */
	public function submit( array $submitted_data = [] ): void {
		// todo: add form validation
		$this->set_data( $submitted_data );
	}

}
