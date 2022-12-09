<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

/**
 * A class for dealing with Dimensions.
 *
 * @since x.x.x
 */
class DimensionUtility {

	/**
	 * Width.
	 *
	 * @var int
	 */
	public int $x;
	/**
	 * Height.
	 *
	 * @var int
	 */
	public int $y;

	/**
	 * DimensionUtility constructor.
	 *
	 * @param int $x Width.
	 * @param int $y Height.
	 */
	public function __construct( int $x, int $y ) {
		$this->x = $x;
		$this->y = $y;
	}
}

