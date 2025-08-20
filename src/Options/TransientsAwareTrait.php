<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Trait TransientsAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
trait TransientsAwareTrait {

	/**
	 * The Transients object.
	 *
	 * @var TransientsInterface
	 */
	protected $transients;

	/**
	 * Set the Transients object.
	 *
	 * @param TransientsInterface $transients
	 */
	public function set_transients_object( TransientsInterface $transients ): void {
		$this->transients = $transients;
	}
}
