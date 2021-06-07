<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Logging;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;
use WC_Log_Levels;
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
	 * Check if debug logging should be enabled.
	 *
	 * @return bool Whether the service is needed.
	 */
	public static function is_needed(): bool {
		return apply_filters( 'gla_enable_debug_logging', true );
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			$this->logger = wc_get_logger();

			add_action( 'gla_debug_message', [ $this, 'log_message' ], 10, 2 );
			add_action( 'gla_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_error', [ $this, 'log_error' ], 10, 2 );
			add_action( 'gla_mc_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_ads_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_sv_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_guzzle_client_exception', [ $this, 'log_exception' ], 10, 2 );
			add_action( 'gla_guzzle_invalid_response', [ $this, 'log_response' ], 10, 2 );
		}
	}

	/**
	 * Log an exception.
	 *
	 * @param Exception $exception
	 * @param string    $method
	 */
	public function log_exception( $exception, string $method ): void {
		$this->log( $exception->getMessage(), $method, WC_Log_Levels::ERROR );
	}

	/**
	 * Log an exception.
	 *
	 * @param string $message
	 * @param string $method
	 */
	public function log_error( string $message, string $method ): void {
		$this->log( $message, $method, WC_Log_Levels::ERROR );
	}

	/**
	 * Log a JSON response.
	 *
	 * @param mixed  $response
	 * @param string $method
	 */
	public function log_response( $response, string $method ): void {
		$message = wp_json_encode( $response, JSON_PRETTY_PRINT );
		$this->log( $message, $method );
	}

	/**
	 * Log a generic note.
	 *
	 * @param string $message
	 * @param string $method
	 */
	public function log_message( string $message, string $method ): void {
		$this->log( $message, $method );
	}

	/**
	 * Log a message as a debug log entry.
	 *
	 * @param string $message
	 * @param string $method
	 * @param string $level
	 */
	protected function log( string $message, string $method, string $level = WC_Log_Levels::DEBUG ) {
		$this->logger->log(
			$level,
			sprintf( '%s %s', $method, $message ),
			[
				'source' => 'google-listings-and-ads',
			]
		);
	}
}
