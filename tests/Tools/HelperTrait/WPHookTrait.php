<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\WPHookMock;

/**
 * Trait for mocking calls to WP hooks.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait WPHookTrait {

	/**
	 * Expect a single call to do_action() with specific hook and args.
	 *
	 * @param string $hook
	 * @param array  $args
	 */
	protected function expect_do_action_once( string $hook, $args ) {
		global $wp_filter;

		$mock = $this->createMock( WPHookMock::class );
		$mock->expects( $this->once() )
			->method( 'do_action' )
			->with( $args );

		$wp_filter[ $hook ] = $mock; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}

	/**
	 * Expect no calls to do_action() with a specific hook.
	 *
	 * @param string $hook
	 */
	protected function expect_do_action_never( string $hook ) {
		global $wp_filter;

		$mock = $this->createMock( WPHookMock::class );
		$mock->expects( $this->never() )->method( 'do_action' );

		$wp_filter[ $hook ] = $mock; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}
}
