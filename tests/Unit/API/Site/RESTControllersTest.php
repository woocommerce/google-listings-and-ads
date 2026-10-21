<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\RESTControllers;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

/**
 * Class RESTControllersTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site
 */
class RESTControllersTest extends UnitTest {

	/**
	 * Regression: when the DI container returns a class-name string instead of
	 * a resolved BaseController instance (observed during plugin upgrade when
	 * autoloader state lags new files on disk), registration must skip the
	 * entry and log an error rather than throwing a fatal.
	 */
	public function test_register_controllers_skips_string_entries_without_fatal() {
		$controller = $this->createMock( BaseController::class );
		$controller->expects( $this->once() )->method( 'register' );

		$container = $this->createMock( ContainerInterface::class );
		$container->method( 'get' )
			->with( 'rest_controller' )
			->willReturn(
				[
					'Automattic\\WooCommerce\\GoogleListingsAndAds\\API\\Site\\Controllers\\Ads\\AssetGenerationController',
					$controller,
				]
			);

		$captured = [];
		$listener = function ( $message ) use ( &$captured ) {
			$captured[] = $message;
		};
		add_action( 'woocommerce_gla_error', $listener, 10, 2 );

		$rest_controllers = new RESTControllers();
		$rest_controllers->set_container( $container );

		$reflection = new \ReflectionClass( $rest_controllers );
		$method     = $reflection->getMethod( 'register_controllers' );
		$method->setAccessible( true );
		$method->invoke( $rest_controllers );

		remove_action( 'woocommerce_gla_error', $listener, 10 );

		$this->assertCount( 1, $captured, 'An error should be logged for the skipped string entry.' );
		$this->assertStringContainsString( 'must implement', $captured[0] );
		$this->assertStringContainsString( 'BaseController', $captured[0] );
	}

	/**
	 * Regression: a non-BaseController object (e.g. plain stdClass) must also
	 * be skipped rather than fatal. Locks the defensive check against any
	 * future tagged binding returning the wrong type.
	 */
	public function test_register_controllers_skips_non_base_controller_objects() {
		$container = $this->createMock( ContainerInterface::class );
		$container->method( 'get' )
			->with( 'rest_controller' )
			->willReturn( [ new \stdClass() ] );

		$captured = [];
		$listener = function ( $message ) use ( &$captured ) {
			$captured[] = $message;
		};
		add_action( 'woocommerce_gla_error', $listener );

		$rest_controllers = new RESTControllers();
		$rest_controllers->set_container( $container );

		$reflection = new \ReflectionClass( $rest_controllers );
		$method     = $reflection->getMethod( 'register_controllers' );
		$method->setAccessible( true );
		$method->invoke( $rest_controllers );

		remove_action( 'woocommerce_gla_error', $listener );

		$this->assertCount( 1, $captured );
		$this->assertStringContainsString( 'stdClass', $captured[0] );
		$this->assertStringContainsString( 'must implement', $captured[0] );
	}

	/**
	 * The loop must keep registering valid controllers no matter where invalid
	 * entries appear in the container's returned array, and log exactly one
	 * error per invalid entry. Proves a single bad entry never cascades into
	 * skipping otherwise-valid controllers.
	 */
	public function test_register_controllers_registers_valid_entries_alongside_invalid_ones() {
		$first  = $this->createMock( BaseController::class );
		$second = $this->createMock( BaseController::class );
		$first->expects( $this->once() )->method( 'register' );
		$second->expects( $this->once() )->method( 'register' );

		$container = $this->createMock( ContainerInterface::class );
		$container->method( 'get' )
			->with( 'rest_controller' )
			->willReturn(
				[
					$first,
					'Automattic\\WooCommerce\\GoogleListingsAndAds\\Bogus\\ClassNameOne',
					new \stdClass(),
					$second,
					null,
				]
			);

		$captured = [];
		$listener = function ( $message ) use ( &$captured ) {
			$captured[] = $message;
		};
		add_action( 'woocommerce_gla_error', $listener );

		$rest_controllers = new RESTControllers();
		$rest_controllers->set_container( $container );

		$reflection = new \ReflectionClass( $rest_controllers );
		$method     = $reflection->getMethod( 'register_controllers' );
		$method->setAccessible( true );
		$method->invoke( $rest_controllers );

		remove_action( 'woocommerce_gla_error', $listener );

		$this->assertCount( 3, $captured, 'One error expected per invalid entry (string, stdClass, null).' );
	}

	/**
	 * Regression: when resolving the whole 'rest_controller' tag throws (e.g. a
	 * TypeError from a dependency whose constructor signature changed mid-upgrade,
	 * per GOOWOO-800), registration must catch it, log via woocommerce_gla_error,
	 * and return without propagating the fatal to the rest of the admin page.
	 *
	 * Uses a TypeError specifically, not a plain Exception: TypeError extends
	 * \Error, not \Exception, so this also locks in that the catch clause is
	 * \Throwable (broad enough to cover Error subclasses) rather than just
	 * \Exception, which would silently miss the exact failure this PR fixes.
	 */
	public function test_register_controllers_catches_error_thrown_while_resolving_tag() {
		$container = $this->createMock( ContainerInterface::class );
		$container->method( 'get' )
			->with( 'rest_controller' )
			->willThrowException(
				new \TypeError(
					'MerchantMetrics::__construct(): Argument #1 ($mapi_client) must be of type MerchantApiClient, ShoppingContent given'
				)
			);

		$captured = [];
		$listener = function ( $message ) use ( &$captured ) {
			$captured[] = $message;
		};
		add_action( 'woocommerce_gla_error', $listener );

		$rest_controllers = new RESTControllers();
		$rest_controllers->set_container( $container );

		$reflection = new \ReflectionClass( $rest_controllers );
		$method     = $reflection->getMethod( 'register_controllers' );
		$method->setAccessible( true );
		$method->invoke( $rest_controllers );

		remove_action( 'woocommerce_gla_error', $listener );

		$this->assertCount( 1, $captured, 'The TypeError should be logged instead of propagating as a fatal.' );
		$this->assertStringContainsString( 'MerchantMetrics', $captured[0] );
	}
}
