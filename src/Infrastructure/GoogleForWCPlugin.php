<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Infrastructure;

use Psr\Container\ContainerInterface;

/**
 * Class GoogleForWCPlugin
 *
 * @package Automattic\WooCommerce\GoogleForWC\Infrastructure
 */
final class GoogleForWCPlugin implements Plugin {

	/**
	 * The hook for registering our plugin's services.
	 *
	 * @var string
	 */
	private $registration_hook = 'woocommerce_loaded';

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @var Service[]
	 */
	private $registered_services;

	/**
	 * GoogleForWCPlugin constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->register_services();

		foreach ( $this->registered_services as $service ) {
			if ( $service instanceof Activateable ) {
				$service->activate();
			}
		}

		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->register_services();

		foreach ( $this->registered_services as $service ) {
			if ( $service instanceof Deactivateable ) {
				$service->deactivate();
			}
		}

		flush_rewrite_rules();
	}

	/**
	 * Register the plugin with the WordPress system.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			$this->registration_hook,
			function() {
				$this->register_services();
			},
			20
		);
	}

	/**
	 * Register our services.
	 */
	protected function register_services(): void {
		static $registered = false;
		if ( $registered ) {
			return;
		}

		/** @var Service[] $services */
		$services = $this->container->get( Service::class );
		foreach ( $services as $service ) {
			if ( $service instanceof Registerable ) {
				$service->register();
			}
			$this->registered_services[ get_class( $service ) ] = $service;
		}

		$registered = true;
	}
}
