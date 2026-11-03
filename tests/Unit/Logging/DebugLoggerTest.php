<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Logging;

use Automattic\WooCommerce\GoogleListingsAndAds\Logging\DebugLogger;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class DebugLoggerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Logging
 */
class DebugLoggerTest extends UnitTest {

	/** @var DebugLogger */
	protected $logger;

	public function setUp(): void {
		parent::setUp();

		$this->logger = new DebugLogger();
		$this->logger->register();
	}

	/**
	 * @dataProvider data_provider_logged_hooks
	 *
	 * @param string $hook
	 * @param string $callback_method
	 */
	public function test_registers_a_listener_for_every_logged_hook( string $hook, string $callback_method ) {
		$this->assertSame( 10, has_action( $hook, [ $this->logger, $callback_method ] ) );
	}

	/**
	 * @return array
	 */
	public function data_provider_logged_hooks(): array {
		return [
			'debug message'                      => [ 'woocommerce_gla_debug_message', 'log_message' ],
			'generic exception'                  => [ 'woocommerce_gla_exception', 'log_exception' ],
			'generic error'                      => [ 'woocommerce_gla_error', 'log_error' ],
			'Merchant Center client exception'   => [ 'woocommerce_gla_mc_client_exception', 'log_exception' ],
			'Ads client exception'               => [ 'woocommerce_gla_ads_client_exception', 'log_exception' ],
			'Site Verification client exception' => [ 'woocommerce_gla_sv_client_exception', 'log_exception' ],
			'Tag Manager client exception'       => [ 'woocommerce_gla_tag_manager_client_exception', 'log_exception' ],
			'Guzzle client exception'            => [ 'woocommerce_gla_guzzle_client_exception', 'log_exception' ],
			'Guzzle invalid response'            => [ 'woocommerce_gla_guzzle_invalid_response', 'log_response' ],
		];
	}
}
