<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Logging;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;
use WC_Logger;

/**
 * Class DebugLogger
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Logging
 */
class DebugLogger implements Service, Registerable, Conditional {

	/**
	 * WooCommerce logger class instance.
	 *
	 * @var WC_Logger
	 */
	private $logger = null;

	/**
	 * Only needed if we enable it through a snippet.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return apply_filters( 'gla_enable_debug_logging', false );
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			$this->logger = wc_get_logger();

			add_action( 'gla_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_mc_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_ads_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_sv_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_guzzle_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_guzzle_invalid_response', [ $this, 'log_response' ], 10, 2 );
		}
	}

	/**
	 * Log a JSON response.
	 *
	 * @param Exception $exception
	 * @param string    $method
	 */
	public function log_exception( $exception, string $method ) {
		$this->log( $exception->getMessage(), $method );
	}

	/**
	 * Log a JSON response.
	 *
	 * @param JSON   $response
	 * @param string $method
	 */
	public function log_response( $response, string $method ) {
		$message = wp_json_encode( $response, JSON_PRETTY_PRINT );
		$this->log( $message, $method );
	}

	/**
	 * Log a message as a debug log entry.
	 *
	 * @param string $message
	 * @param string $method
	 */
	protected function log( string $message, string $method ) {
		$this->logger->debug(
			sprintf( '%s %s', $method, $message ),
			[
				'source' => 'google-listings-and-ads',
			]
		);
	}
}
