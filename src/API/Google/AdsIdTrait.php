<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;

/**
 * Trait AdsIdTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait AdsIdTrait {

	/**
	 * The ads account ID.
	 *
	 * @var PositiveInteger
	 */
	protected $id;


	/**
	 * Get the ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id->get();
	}

	/**
	 * Set the ID.
	 *
	 * @param int $id
	 */
	public function set_id( int $id ): void {
		$this->id = new PositiveInteger( $id );
	}
}
