<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ContainerAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces
 */
interface ContainerAwareInterface {

	/**
	 * @param ContainerInterface $container
	 *
	 * @return void
	 */
	public function set_container( ContainerInterface $container ): void;
}
