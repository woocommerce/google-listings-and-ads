<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Trait OptionsAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
trait OptionsAwareTrait {

	/**
	 * The Options object.
	 *
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * Set the Options object.
	 *
	 * @param OptionsInterface $options
	 */
	public function set_options_object( OptionsInterface $options ): void {
		$this->options = $options;
	}
}
