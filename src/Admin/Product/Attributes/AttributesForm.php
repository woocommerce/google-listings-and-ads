<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Checkbox;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Number;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
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
	 * @var string[]
	 */
	protected $attribute_types = [];

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
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data = parent::get_view_data();

		// add classes to hide/display attributes based on product type
		foreach ( $view_data['children'] as $index => $input ) {
			if ( ! isset( $this->attribute_types[ $index ] ) ) {
				continue;
			}

			$attribute_id     = $index;
			$attribute_type   = $this->attribute_types[ $index ];
			$applicable_types = call_user_func( [ $attribute_type, 'get_applicable_product_types' ] );

			/**
			 * This filter is documented in AttributeManager::map_attribute_types
			 *
			 * @see AttributeManager::map_attribute_types
			 */
			$applicable_types = apply_filters( "gla_attribute_applicable_product_types_{$attribute_id}", $applicable_types, $attribute_type );

			if ( ! empty( $applicable_types ) ) {
				$input['gla_wrapper_class']  = $input['gla_wrapper_class'] ?? '';
				$input['gla_wrapper_class'] .= ' show_if_' . join( ' show_if_', $applicable_types );

				$view_data['children'][ $index ] = $input;
			}
		}

		return $view_data;
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
			$value_options = [ '' => __( 'Default', 'google-listings-and-ads' ) ] + $value_options;

			$input->set_options( $value_options );
		}

		return $input;
	}

	/**
	 * Guesses what kind of input does the attribute need based on its value type and returns the input class name.
	 *
	 * @param AttributeInterface $attribute
	 *
	 * @return string Input class name
	 */
	protected function guess_input_type( AttributeInterface $attribute ): string {
		if ( $attribute instanceof WithValueOptionsInterface ) {
			return Select::class;
		}

		switch ( $attribute::get_value_type() ) {
			case 'integer':
			case 'int':
			case 'float':
			case 'double':
				$input_type = Number::class;
				break;
			case 'bool':
			case 'boolean':
				$input_type = Checkbox::class;
				break;
			default:
				$input_type = Text::class;
		}

		return $input_type;
	}

	/**
	 * Add an attribute to the form
	 *
	 * @param string      $attribute_type An attribute class extending AttributeInterface
	 * @param string|null $input_type     An input class extending InputInterface to use for attribute input.
	 *
	 * @return AttributesForm
	 *
	 * @throws InvalidValue If the attribute type is invalid or an invalid input type is specified for the attribute.
	 */
	public function add_attribute( string $attribute_type, ?string $input_type = null ): AttributesForm {
		$this->validate_interface( $attribute_type, AttributeInterface::class );
		$attribute = new $attribute_type();

		// check if input type is provided or guess it for the attribute
		if ( ! empty( $input_type ) ) {
			$this->validate_interface( $input_type, InputInterface::class );
		} else {
			$input_type = $this->guess_input_type( $attribute );
		}

		$attribute_input = $this->init_input( new $input_type(), $attribute );

		$attribute_id = call_user_func( [ $attribute_type, 'get_id' ] );
		$this->add( $attribute_input, $attribute_id );

		$this->attribute_types[ $attribute_id ] = $attribute_type;

		return $this;
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
	public function remove_attribute( string $attribute_type ): AttributesForm {
		$this->validate_interface( $attribute_type, AttributeInterface::class );

		$attribute_id = call_user_func( [ $attribute_type, 'get_id' ] );
		if ( $this->contains_attribute( $attribute_type ) ) {
			unset( $this->attribute_types[ $attribute_id ] );
		}
		$this->remove( $attribute_id );

		return $this;
	}

	/**
	 * Sets the input type for the given attribute.
	 *
	 * @param string $attribute_type
	 * @param string $input_type
	 *
	 * @return $this
	 */
	public function set_attribute_input( string $attribute_type, string $input_type ): AttributesForm {
		$this->validate_interface( $attribute_type, AttributeInterface::class );
		$this->validate_interface( $input_type, InputInterface::class );

		if ( $this->contains_attribute( $attribute_type ) ) {
			$this->remove_attribute( $attribute_type );
			$this->add_attribute( $attribute_type, $input_type );
		}

		return $this;
	}

	/**
	 * Whether the form contains the given attribute type.
	 *
	 * @param string $attribute_type
	 *
	 * @return bool
	 */
	public function contains_attribute( string $attribute_type ): bool {
		$this->validate_interface( $attribute_type, AttributeInterface::class );
		$attribute_id = call_user_func( [ $attribute_type, 'get_id' ] );

		return isset( $this->attribute_types[ $attribute_id ] );
	}
}
