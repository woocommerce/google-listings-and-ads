<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ContainerAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal
 */
trait ContainerAwareTrait {

	/** @var ContainerInterface */
	protected $container;

	/**
	 * @param ContainerInterface $container
	 *
	 * @return void
	 */
	public function set_container( ContainerInterface $container ): void {
		$this->container = $container;
	}
}
