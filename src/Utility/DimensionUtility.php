<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

/**
 * A class for dealing with Dimensions.
 *
 * @since 2.4.0
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

	/**
	 * Checks if the dimension fulfils the minimum size.
	 *
	 * @param DimensionUtility $minimum_size The minimum size.
	 *
	 * @return bool true if the dimension is bigger or equal than the the minimum size otherwise false.
	 */
	public function is_minimum_size( DimensionUtility $minimum_size ): bool {
		return $this->x >= $minimum_size->x && $this->y >= $minimum_size->y;
	}

	/**
	 * Checks if the dimension is equal to the other one with a specific precision.
	 *
	 * @param DimensionUtility $target The dimension to be compared.
	 * @param int|float        $precision The precision to use when comparing the two numbers.
	 *
	 * @return bool true if the dimension is equal than the other one otherwise false.
	 */
	public function equals( DimensionUtility $target, $precision = 1 ): bool {
		return wp_fuzzy_number_match( $this->x, $target->x, $precision ) && wp_fuzzy_number_match( $this->y, $target->y, $precision );
	}
}
