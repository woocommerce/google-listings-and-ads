<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;

defined( 'ABSPATH' ) || exit;

/**
 * Class VariationAttributesForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class VariationAttributesForm extends AttributesForm {
	/**
	 * VariationAttributesForm constructor.
	 *
	 * @param int   $variation_index
	 * @param array $data
	 */
	public function __construct( int $variation_index, array $data = [] ) {
		$this->set_name( 'variation_attributes' );

		$form = ( new AttributesForm() )
			->add_attribute( GTIN::class, Text::class )
			->add_attribute( MPN::class, Text::class )
			->add_attribute( Gender::class, Select::class );

		$this->add( $form, (string) $variation_index );

		parent::__construct( $data );
	}
}
