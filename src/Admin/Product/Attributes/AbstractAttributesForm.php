<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithValueOptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractAttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
abstract class AbstractAttributesForm extends Form {

	use ValidateInterface;

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
	 * @param string $input_type     An input class extending InputInterface
	 *
	 * @return AbstractAttributesForm
	 */
	protected function add_attribute( string $attribute_type, string $input_type ): AbstractAttributesForm {
		$this->validate_interface( $attribute_type, AttributeInterface::class );
		$this->validate_interface( $input_type, InputInterface::class );

		$attribute_input = $this->init_input( new $input_type(), new $attribute_type() );
		$attribute_id    = call_user_func( [ $attribute_type, 'get_id' ] );
		$this->add( $attribute_input, $attribute_id );

		return $this;
	}
}
