<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class IntegrationInitializer
 *
 * Initializes all active integrations.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class IntegrationInitializer implements Service, Registerable {

	use ValidateInterface;

	/**
	 * @var IntegrationInterface[]
	 */
	protected $integrations = [];

	/**
	 * IntegrationInitializer constructor.
	 *
	 * @param IntegrationInterface[] $integrations
	 */
	public function __construct( array $integrations ) {
		foreach ( $integrations as $integration ) {
			$this->validate_instanceof( $integration, IntegrationInterface::class );
			$this->integrations[] = $integration;
		}
	}

	/**
	 * Initialize all active integrations.
	 */
	public function register(): void {
		foreach ( $this->integrations as $integration ) {
			if ( $integration->is_active() ) {
				$integration->init();
			}
		}
	}
}
