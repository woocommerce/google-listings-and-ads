<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithValueOptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class AttributesForm extends Form {

	use ValidateInterface;

	/**
	 * AttributesForm constructor.
	 *
	 * @param string[] $attribute_types
	 * @param array    $data
	 */
	public function __construct( array $attribute_types, array $data = [] ) {
		foreach ( $attribute_types as $attribute_type ) {
			$this->add_attribute( $attribute_type );
		}

		parent::__construct( $data );
	}

	/**
	 * @param InputInterface     $input
	 * @param AttributeInterface $attribute
	 *
	 * @return InputInterface
	 */
	protected function init_input( InputInterface $input, AttributeInterface $attribute ) {
		$input->set_id( $attribute::get_id() )
			  ->set_label( $attribute::get_name() )
			  ->set_description( $attribute::get_description() )
			  ->set_name( $attribute::get_id() );

		$value_options = [];
		if ( $attribute instanceof WithValueOptionsInterface ) {
			$value_options = $attribute::get_value_options();
		}
		$value_options = apply_filters( "gla_product_attribute_value_options_{$attribute::get_id()}", $value_options );

		if ( ! empty( $value_options ) ) {
			if ( ! $input instanceof Select && ! $input instanceof SelectWithTextInput ) {
				return $this->init_input( new SelectWithTextInput(), $attribute );
			}

			// add a 'default' value option
			$value_options = [ '' => 'Default' ] + $value_options;

			$input->set_options( $value_options );
		}

		return $input;
	}


	/**
	 * Add an attribute to the form
	 *
	 * @param string $attribute_type An attribute class extending AttributeInterface
	 *
	 * @return AttributesForm
	 *
	 * @throws InvalidValue If the attribute type is invalid or an invalid input type is specified for the attribute.
	 */
	protected function add_attribute( string $attribute_type ): AttributesForm {
		$this->validate_interface( $attribute_type, AttributeInterface::class );

		$input_type = call_user_func( [ $attribute_type, 'get_input_type' ] );
		$this->validate_interface( $input_type, InputInterface::class );

		$attribute_input = $this->init_input( new $input_type(), new $attribute_type() );
		$attribute_id    = call_user_func( [ $attribute_type, 'get_id' ] );
		$this->add( $attribute_input, $attribute_id );

		return $this;
	}
}
