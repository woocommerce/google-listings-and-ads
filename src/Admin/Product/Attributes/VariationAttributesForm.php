<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;

defined( 'ABSPATH' ) || exit;

/**
 * Class VariationAttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class VariationAttributesForm extends AbstractAttributesForm {
	/**
	 * VariationAttributesForm constructor.
	 *
	 * @param int   $variation_index
	 * @param array $data
	 */
	public function __construct( int $variation_index, array $data = [] ) {
		$this->set_name( 'variation_attributes' );

		$form = ( new Form() )
			->add( $this->init_input( new Text(), new GTIN( null ) ), GTIN::get_id() )
			->add( $this->init_input( new Text(), new MPN( null ) ), MPN::get_id() );

		$this->add( $form, (string) $variation_index );

		parent::__construct( $data );
	}
}
