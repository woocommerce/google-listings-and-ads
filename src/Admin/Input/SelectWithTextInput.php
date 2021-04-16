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

	public const CUSTOM_VALUE_KEY = 'custom_value';

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
	 * @return bool
	 */
	public function is_custom_value(): bool {
		return empty( $this->get_value() ) || ! isset( $this->get_options()[ $this->get_value() ] );
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
			  ->set_value( $this->get_value() );

		return $input;
	}
}
