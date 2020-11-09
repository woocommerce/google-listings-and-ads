<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GoogleConnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Pages\ConnectAccount;
use Automattic\WooCommerce\GoogleListingsAndAds\Pages\EventTracking;
use Automattic\WooCommerce\GoogleListingsAndAds\Pages\TrackerSnapshot;

/**
 * Class CoreServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class CoreServiceProvider extends AbstractServiceProvider {

	/**
	 * @var array
	 */
	protected $provides = [
		Service::class                => [],
		AssetsHandlerInterface::class => [
			'concrete' => AssetsHandler::class,
		],
		GoogleConnect::class          => [],
		ConnectAccount::class         => [
			'args' => [ AssetsHandlerInterface::class ],
		],
		TrackerSnapshot::class        => [],
		EventTracking::class          => [],
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
			if ( interface_exists( $class ) && empty( $arguments ) ) {
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

			$definition = $this->share( $class, $arguments['concrete'] ?? null );
			$definition->addArguments( $arguments['args'] ?? [] );

			foreach ( $implements as $interface ) {
				$definition->addTag( $interface );
			}
		}
	}
}
