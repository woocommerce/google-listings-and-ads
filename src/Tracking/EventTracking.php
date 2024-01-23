<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\ActivatedEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\BaseEvent;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\GenericEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SiteClaimEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SiteVerificationEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

/**
 * Wire up the Google Listings and Ads events to Tracks.
 * Add all new events to `$events`.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class EventTracking implements Service, Registerable {

	use ValidateInterface;

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Individual events classes to load.
	 *
	 * @var string[]
	 */
	protected $events = [
		ActivatedEvents::class,
		GenericEvents::class,
		SiteClaimEvents::class,
		SiteVerificationEvents::class,
	];

	/**
	 * EventTracking constructor.
	 *
	 * @param ContainerInterface $container The tracks interface object.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public function register(): void {
		add_action(
			'init',
			function () {
				$this->register_events();
			},
			20 // After WC_Admin loads WC_Tracks class (init 10).
		);
	}

	/**
	 * Register all of our event tracking classes.
	 */
	protected function register_events() {
		foreach ( $this->events as $class ) {
			/** @var BaseEvent $instance */
			$instance = $this->container->get( $class );
			$this->validate_instanceof( $instance, BaseEvent::class );
			$instance->register();
		}
	}
}
