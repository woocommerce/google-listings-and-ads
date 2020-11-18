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
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\Loaded;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\EventTracking;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TrackerSnapshot;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Tracks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksInterface;
use Psr\Container\ContainerInterface;

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
		Service::class                => true,
		GoogleConnect::class          => true,
		ConnectAccount::class         => true,
		TrackerSnapshot::class        => true,
		EventTracking::class          => true,
		Tracks::class                 => true,
		Loaded::class                 => true,
		AssetsHandlerInterface::class => true,
		TracksInterface::class        => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		// Share our interfaces, possibly with concrete objects.
		$this->share_interface( AssetsHandlerInterface::class, AssetsHandler::class );
		$this->share_interface(
			TracksInterface::class,
			$this->share_with_tags( Tracks::class, TracksProxy::class )
		);

		// Share our regular service classes.
		$this->conditionally_share_with_tags( GoogleConnect::class );
		$this->conditionally_share_with_tags( ConnectAccount::class, AssetsHandlerInterface::class );
		$this->conditionally_share_with_tags( TrackerSnapshot::class );
		$this->conditionally_share_with_tags( EventTracking::class, ContainerInterface::class );

		// Set up inflector for tracks classes.
		$this->getLeagueContainer()
			 ->inflector( TracksAwareInterface::class )
			 ->invokeMethod( 'set_tracks', [ TracksInterface::class ] );

		// Share other classes.
		$this->conditionally_share_with_tags( Loaded::class );
	}

	/**
	 * Maybe share a class and add interfaces as tags.
	 *
	 * This will also check any classes that implement the Conditional interface and only add them if
	 * they are needed.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 */
	protected function conditionally_share_with_tags( string $class, ...$arguments ) {
		$implements = class_implements( $class );
		if ( array_key_exists( Conditional::class, $implements ) ) {
			/** @var Conditional $class */
			if ( ! $class::is_needed() ) {
				return;
			}
		}

		$this->share_with_tags( $class, ...$arguments );
	}
}
