<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithValueOptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class AttributesForm extends Form {

	/**
	 * AttributesForm constructor.
	 *
	 * @param array $data
	 */
	public function __construct( array $data = [] ) {
		$this->set_name( 'attributes' );

		$this->add( $this->init_input( new Text(), new GTIN( null ) ), GTIN::get_id() )
			 ->add( $this->init_input( new Text(), new MPN( null ) ), MPN::get_id() )
			 ->add( $this->init_input( new Select(), new Gender( null ) ), Gender::get_id() );

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
			  ->set_name( $attribute::get_id() )
			  ->set_label( $attribute::get_name() )
			  ->set_description( $attribute::get_description() );

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
}
