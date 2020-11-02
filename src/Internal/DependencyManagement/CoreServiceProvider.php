<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleForWC\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleForWC\Infrastructure\Service;
use Automattic\WooCommerce\GoogleForWC\Menu\GoogleConnect;

/**
 * Class CoreServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement
 */
class CoreServiceProvider extends AbstractServiceProvider {

	/**
	 * @var array
	 */
	protected $provides = [
		Service::class       => [],
		GoogleConnect::class => [],
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		foreach ( $this->provides as $class => $arguments ) {
			if ( interface_exists( $class ) ) {
				continue;
			}

			$implements = class_implements( $class );

			// Conditional objects need to be checked before being registered.
			if ( array_key_exists( Conditional::class, $implements ) ) {
				/** @var Conditional $class */
				if ( ! $class::is_needed() ) {
					continue;
				}
			}

			$definition = $this->share( $class );
			if ( ! empty( $arguments ) ) {
				$definition->addArguments( $arguments );
			}

			foreach ( $implements as $interface ) {
				$definition->addTag( $interface );
			}
		}
	}
}
