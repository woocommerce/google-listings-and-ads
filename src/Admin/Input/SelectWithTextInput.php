<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Select
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class SelectWithTextInput extends AbstractInput {

	public const CUSTOM_VALUE_KEY = '_gla_custom_value';

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * Select constructor.
	 */
	public function __construct() {
		parent::__construct( 'select-with-text-input' );
	}

	/**
	 * @return array
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * @param array $options
	 *
	 * @return $this
	 */
	public function set_options( array $options ): SelectWithTextInput {
		$this->options = $options;

		return $this;
	}

	/**
	 * Whether the set value is a custom value (i.e. not from the list of specified options).
	 *
	 * @return bool
	 */
	protected function is_custom_value(): bool {
		return ! empty( $this->get_value() ) && ! isset( $this->get_options()[ $this->get_value() ] );
	}

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data = parent::get_view_data();

		// get the custom input data
		$custom_input = $this->get_text_input()->get_view_data();

		// add custom classes
		$view_data['class']            = 'select short with-text-input';
		$custom_input['wrapper_class'] = 'custom-input';

		$view_data['options'] = $this->get_options();

		// add custom value option
		$view_data['options'][ self::CUSTOM_VALUE_KEY ] = __( 'Enter your value', 'google-listings-and-ads' );

		// select the custom option if it's a custom value
		if ( $this->is_custom_value() ) {
			$view_data['value'] = self::CUSTOM_VALUE_KEY;
		}

		$view_data['children'][0] = $custom_input;

		return $view_data;
	}

	/**
	 * @return Text
	 */
	public function get_text_input(): Text {
		$input = new Text();
		$id    = sprintf( '%s_%s', $this->get_id(), self::CUSTOM_VALUE_KEY );
		$name  = sprintf( '%s_%s', $this->get_name(), self::CUSTOM_VALUE_KEY );
		$input->set_id( $id )
			  ->set_name( $name )
			  ->set_label( $this->get_label() );

		if ( $this->is_custom_value() ) {
			$input->set_value( $this->get_value() );
		}

		return $input;
	}
}
