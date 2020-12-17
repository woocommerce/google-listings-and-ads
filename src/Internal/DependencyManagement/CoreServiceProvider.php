<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\RESTControllers;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\ConnectionTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GetStarted;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\SetupMerchantCenter;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\SetupAds;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Analytics;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\ProductFeed;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GoogleConnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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
		Analytics::class              => true,
		AssetsHandlerInterface::class => true,
		ConnectAccount::class         => true,
		Dashboard::class              => true,
		EventTracking::class          => true,
		GetStarted::class             => true,
		Loaded::class                 => true,
		OptionsInterface::class       => true,
		ProductFeed::class            => true,
		RESTControllers::class        => true,
		Service::class                => true,
		Settings::class               => true,
		SetupAds::class               => true,
		SetupMerchantCenter::class    => true,
		TrackerSnapshot::class        => true,
		Tracks::class                 => true,
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
		$this->share_interface( OptionsInterface::class, Options::class );

		// Share our regular service classes.
		$this->conditionally_share_with_tags( GetStarted::class );
		$this->conditionally_share_with_tags( SetupMerchantCenter::class );
		$this->conditionally_share_with_tags( SetupAds::class );
		$this->conditionally_share_with_tags( Dashboard::class );
		$this->conditionally_share_with_tags( Analytics::class );
		$this->conditionally_share_with_tags( ProductFeed::class );
		$this->conditionally_share_with_tags( Settings::class );
		$this->conditionally_share_with_tags( ConnectAccount::class, AssetsHandlerInterface::class );
		$this->conditionally_share_with_tags( TrackerSnapshot::class );
		$this->conditionally_share_with_tags( EventTracking::class, ContainerInterface::class );
		$this->conditionally_share_with_tags( RESTControllers::class, ContainerInterface::class );
		$this->conditionally_share_with_tags( ConnectionTest::class, ContainerInterface::class );

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
