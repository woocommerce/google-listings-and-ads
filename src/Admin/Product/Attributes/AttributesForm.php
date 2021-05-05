<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class AttributesForm extends AbstractAttributesForm {
	/**
	 * AttributesForm constructor.
	 *
	 * @param array $data
	 */
	public function __construct( array $data = [] ) {
		$this->set_name( 'attributes' );

		$this->add( $this->init_input( new Text(), new Brand( null ) ), Brand::get_id() )
			 ->add( $this->init_input( new Text(), new GTIN( null ) ), GTIN::get_id() )
			 ->add( $this->init_input( new Text(), new MPN( null ) ), MPN::get_id() );

		parent::__construct( $data );
	}
}
