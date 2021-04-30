<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Form;

defined( 'ABSPATH' ) || exit;

/**
 * Class VariationAttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class VariationAttributesForm extends Form {
	/**
	 * VariationAttributesForm constructor.
	 *
	 * @param int   $variation_index
	 * @param array $data
	 */
	public function __construct( int $variation_index, array $data = [] ) {
		$this->set_name( 'variation_attributes' );

		$this->add( new AttributesForm(), (string) $variation_index );
		parent::__construct( $data );
	}
}
