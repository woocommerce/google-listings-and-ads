<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GoogleConnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Pages\ConnectAccount;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Tracks as TracksProxy;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\EventTracking;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TrackerSnapshot;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Tracks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksInterface;

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

		// Interfaces with concrete mappings.
		AssetsHandlerInterface::class => [
			'concrete' => AssetsHandler::class,
		],
		TracksInterface::class        => [
			'concrete' => Tracks::class,
		],

		// Regular service classes.
		GoogleConnect::class          => [],
		ConnectAccount::class         => [
			'args' => [ AssetsHandlerInterface::class ],
		],
		TrackerSnapshot::class        => [],
		EventTracking::class          => [
			'args' => [ TracksInterface::class ],
		],
		Tracks::class                 => [
			'args' => [ TracksProxy::class ],
		],
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
