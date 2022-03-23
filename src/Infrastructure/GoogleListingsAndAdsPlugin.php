<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements\PluginValidator;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Psr\Container\ContainerInterface;

/**
 * Class GoogleListingsAndAdsPlugin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
final class GoogleListingsAndAdsPlugin implements Plugin {

	/**
	 * The hook for registering our plugin's services.
	 *
	 * @var string
	 */
	private const SERVICE_REGISTRATION_HOOK = 'plugins_loaded';

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @var Service[]
	 */
	private $registered_services;

	/**
	 * GoogleListingsAndAdsPlugin constructor.
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
		$this->maybe_register_services();

		// Do not run activate if service registration failed.
		if ( empty( $this->registered_services ) ) {
			return;
		}

		foreach ( $this->registered_services as $service ) {
			if ( $service instanceof Activateable ) {
				$service->activate();
			}
		}

		// Update the plugin activated flag.
		$this->container->get( OptionsInterface::class )->update( OptionsInterface::PLUGIN_ACTIVATED, true );
		flush_rewrite_rules();

	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->maybe_register_services();

		foreach ( $this->registered_services as $service ) {
			if ( $service instanceof Deactivateable ) {
				$service->deactivate();
			}
		}

		// Update the plugin activated flag.
		$this->container->get( OptionsInterface::class )->update( OptionsInterface::PLUGIN_ACTIVATED, false );
		flush_rewrite_rules();
	}

	/**
	 * Register the plugin with the WordPress system.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			self::SERVICE_REGISTRATION_HOOK,
			function() {
				$this->maybe_register_services();

			},
			20
		);

		add_action(
			'init',
			function() {
				$this->container->get( AssetsHandlerInterface::class )->register();

				// register the job initializer only if it is available. see JobInitializer::is_needed.
				if ( $this->container->has( JobInitializer::class ) ) {
					$this->container->get( JobInitializer::class )->register();
				}

				// Check if plugin requirements are valid and run activation.
				if ( PluginValidator::validate()
					&& ! $this->container->get( OptionsInterface::class )->get( OptionsInterface::PLUGIN_ACTIVATED ) ) {
					$this->activate();
				}

			}
		);

		require_once dirname( __DIR__ ) . '/Polyfills/bootstrap.php';
	}

	/**
	 * Register our services if dependency validation passes.
	 */
	protected function maybe_register_services(): void {
		// Don't register anything if a required plugin is missing or an incompatible plugin is active.
		if ( ! PluginValidator::validate() ) {
			$this->registered_services = [];
			return;
		}

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
