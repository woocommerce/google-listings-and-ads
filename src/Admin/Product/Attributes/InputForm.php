<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\AbstractForm;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithValueOptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class InputForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class InputForm extends AbstractForm {

	/**
	 * InputForm constructor.
	 */
	public function __construct() {
		$inputs = [
			$this->init_input( new Text(), new GTIN( null ) ),
			$this->init_input( new Text(), new MPN( null ) ),
		];

		parent::__construct( $inputs );
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
			  ->set_description( $attribute::get_name() );

		$value_options = [];
		if ( $attribute instanceof WithValueOptionsInterface ) {
			$value_options = $attribute->get_value_options();
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
	 * Return the form name.
	 *
	 * This name is used as a prefix for the form's field names.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla_attributes';
	}
}
