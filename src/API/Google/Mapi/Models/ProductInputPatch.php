<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductInputPatch
 *
 * Pairs a ProductInput with the update mask describing which fields to
 * write.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models
 */
class ProductInputPatch {

	/** @var ProductInput */
	protected $input;

	/** @var string[] */
	protected $update_mask;

	/**
	 * ProductInputPatch constructor.
	 *
	 * @param ProductInput $input       The product and the changed attributes.
	 * @param string[]     $update_mask Field names to update (e.g. productAttributes.title).
	 */
	public function __construct( ProductInput $input, array $update_mask ) {
		$this->input       = $input;
		$this->update_mask = $update_mask;
	}

	/**
	 * @return ProductInput
	 */
	public function get_input(): ProductInput {
		return $this->input;
	}

	/**
	 * @return string[]
	 */
	public function get_update_mask(): array {
		return $this->update_mask;
	}
}
