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

	/**
	 * Return the data used for the form's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		return [
			'name'   => $this->get_form_view_name(),
			'inputs' => $this->get_inputs_view_data(),
		];
	}

	/**
	 * Return the form name to be used in form's view.
	 *
	 * @return string
	 */
	protected function get_form_view_name(): string {
		return sprintf( 'gla_%s', $this->get_name() );
	}

	/**
	 * @return array
	 */
	protected function get_inputs_view_data(): array {
		$inputs_view_data = [];
		foreach ( $this->inputs as $input ) {
			$inputs_view_data[ $input->get_id() ] = $this->prepare_input_view_data( $input->get_view_data() );
		}

		return $inputs_view_data;
	}

	/**
	 * @param array $view_data
	 *
	 * @return array
	 */
	protected function prepare_input_view_data( array $view_data ): array {
		if ( ! empty( $view_data['id'] ) ) {
			$view_data['id'] = sprintf( 'gla_%s_%s', $this->get_name(), $view_data['id'] );
		}
		if ( ! empty( $view_data['name'] ) ) {
			$view_data['name'] = sprintf( '%s[%s]', $this->get_form_view_name(), $view_data['name'] );
		}

		if ( ! empty( $view_data['children'] ) ) {
			foreach ( $view_data['children'] as $key => $child ) {
				$view_data['children'][ $key ] = $this->prepare_input_view_data( $child );
			}
		}

		return $view_data;
	}

}
