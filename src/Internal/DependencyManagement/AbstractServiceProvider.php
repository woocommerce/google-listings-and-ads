<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider as WCAbstractServiceProvider;

/**
 * Class AbstractServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement
 */
abstract class AbstractServiceProvider extends WCAbstractServiceProvider {

	/**
	 * Array of classes provided by this container.
	 *
	 * @var array
	 */
	protected $provides = [];

	/**
	 * Returns a boolean if checking whether this provider provides a specific
	 * service or returns an array of provided services if no argument passed.
	 *
	 * @param string $service
	 *
	 * @return boolean
	 */
	public function provides( string $service ): bool {
		return array_key_exists( $service, $this->provides );
	}
}
