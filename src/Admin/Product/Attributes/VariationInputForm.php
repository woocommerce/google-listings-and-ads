<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class VariationInputForm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
class VariationInputForm extends InputForm {
	/**
	 * @var int
	 */
	protected $variation_index;

	/**
	 * InputForm constructor.
	 *
	 * @param int $variation_index
	 */
	public function __construct( int $variation_index ) {
		$this->variation_index = $variation_index;
		parent::__construct();
	}

	/**
	 * Return the form name to be used in form's view.
	 *
	 * @return string
	 */
	protected function get_form_view_name(): string {
		return sprintf( 'gla_%s[%s]', $this->get_name(), $this->variation_index );
	}


	/**
	 * Return the form name.
	 *
	 * This name is used as a prefix for the form's field names.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'variation_attributes';
	}
}
