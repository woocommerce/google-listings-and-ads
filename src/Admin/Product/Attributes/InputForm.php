<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\AbstractForm;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithValueOptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class InputForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class InputForm extends AbstractForm implements Service, Conditional {

	use AdminConditional;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * InputForm constructor.
	 *
	 * @param AttributeManager $attribute_manager
	 */
	public function __construct( AttributeManager $attribute_manager ) {
		$this->attribute_manager = $attribute_manager;
	}

	/**
	 * Return a list of inputs provided by the form.
	 *
	 * @param array $args
	 *
	 * @return InputInterface[]
	 */
	public function get_inputs( array $args ): array {
		$product_id = ! empty( $args['product_id'] ) ? (int) $args['product_id'] : null;

		$inputs = [];

		if ( ! empty( $product_id ) ) {
			$gtin = $this->attribute_manager->get( $product_id, GTIN::get_id() );
			$mpn  = $this->attribute_manager->get( $product_id, MPN::get_id() );
		} else {
			$gtin = new GTIN( null );
			$mpn  = new MPN( null );
		}

		$inputs[] = $this->init_input( new Text(), $gtin );
		$inputs[] = $this->init_input( new Text(), $mpn );

		return $inputs;
	}

	/**
	 * Submit the form.
	 *
	 * @param array $args
	 *
	 * @throws InvalidArgument If product ID is not provided.
	 */
	public function submit( array $args ): void {
		if ( empty( $args['product_id'] ) ) {
			throw new InvalidArgument( '`product_id` not provided.' );
		}

		$product_id = $args['product_id'];

		$filled_inputs = $this->get_filled_inputs( [ 'product_id' => $product_id ] );

		// gtin
		if ( ! empty( $filled_inputs[ GTIN::get_id() ] ) ) {
			$gtin = new GTIN( $filled_inputs[ GTIN::get_id() ]->get_value() );
			$this->attribute_manager->update( $product_id, $gtin );
		}

		// mpn
		if ( ! empty( $filled_inputs[ MPN::get_id() ] ) ) {
			$mpn = new MPN( $filled_inputs[ MPN::get_id() ]->get_value() );
			$this->attribute_manager->update( $product_id, $mpn );
		}
	}

	/**
	 * @param InputInterface     $input
	 * @param AttributeInterface $attribute
	 *
	 * @return InputInterface|Select|SelectWithTextInput
	 */
	protected function init_input( InputInterface $input, AttributeInterface $attribute ) {
		$input->set_id( $attribute::get_id() )
			  ->set_name( $attribute::get_id() )
			  ->set_label( $attribute::get_name() )
			  ->set_description( $attribute::get_name() )
			  ->set_value( $attribute->get_value() );

		$value_options = [];
		if ( $attribute instanceof WithValueOptionsInterface ) {
			$value_options = $attribute->get_value_options();
		}
		$value_options = apply_filters( "gla_product_attribute_value_options_{$attribute::get_id()}", $value_options );

		if ( ! empty( $value_options ) ) {
			if ( ! $input instanceof Select && ! $input instanceof SelectWithTextInput ) {
				return $this->init_input( new SelectWithTextInput(), $attribute );
			}
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
