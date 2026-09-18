<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection as SearchConsoleConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\DisconnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\OnboardingController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole\AccountController as SearchConsoleAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DisconnectControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class DisconnectControllerTest extends RESTControllerUnitTest {

	/** @var DisconnectController $controller */
	protected $controller;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|SearchConsoleConnection $search_console_connection */
	protected $search_console_connection;

	protected const ROUTE_CONNECTIONS         = '/wc/gla/connections';
	protected const ROUTE_ONBOARDING_COMPLETE = '/wc/gla/google/onboarding/complete';
	protected const ROUTE_SEARCH_CONSOLE      = '/wc/gla/search-console/connection';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// Register OnboardingController so it can be called by DisconnectController
		$this->options         = $this->createMock( OptionsInterface::class );
		$onboarding_controller = new OnboardingController( $this->server );
		$onboarding_controller->set_options_object( $this->options );
		$onboarding_controller->register();

		// Register the Search Console AccountController so it can be called by DisconnectController
		$this->search_console_connection = $this->createMock( SearchConsoleConnection::class );
		$search_console_controller       = new SearchConsoleAccountController( $this->server, $this->search_console_connection );
		$search_console_controller->register();

		$this->controller = new DisconnectController( $this->server );
		$this->controller->register();
	}

	/**
	 * Test that the route is registered correctly.
	 */
	public function test_route_registered(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( self::ROUTE_CONNECTIONS, $routes );
	}

	/**
	 * Test that disconnect calls the onboarding complete DELETE endpoint.
	 *
	 * Note: The actual DELETE endpoint behavior is tested in OnboardingControllerTest.
	 * This test only verifies that DisconnectController includes it in the disconnect flow.
	 */
	public function test_disconnect_calls_onboarding_complete_endpoint(): void {
		// Expect the delete method to be called exactly once
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT )
			->willReturn( true );

		$response = $this->do_request( self::ROUTE_CONNECTIONS, 'DELETE' );

		// Verify the response structure
		$data = $response->get_data();
		$this->assertArrayHasKey( 'errors', $data );
		$this->assertArrayHasKey( 'responses', $data );

		// Verify that the onboarding complete endpoint was successfully called
		$this->assertArrayHasKey(
			self::ROUTE_ONBOARDING_COMPLETE,
			$data['responses'],
			'The onboarding complete endpoint should be successfully called by disconnect'
		);
	}

	/**
	 * Test that disconnect calls the Search Console disconnect endpoint.
	 *
	 * Regression test for GOOWOO-1044: "Disconnect all" previously omitted
	 * `search-console/connection`, so the Search Console connection's local
	 * state (property/verification/disconnected marker) was never cleared,
	 * and the next connect attempt silently re-resolved the prior property
	 * instead of showing the property selector.
	 *
	 * Note: The actual DELETE endpoint behavior is tested in
	 * SearchConsole\AccountControllerTest. This test only verifies that
	 * DisconnectController includes it in the disconnect flow.
	 */
	public function test_disconnect_calls_search_console_endpoint(): void {
		$this->search_console_connection->expects( $this->once() )
			->method( 'disconnect' );

		$response = $this->do_request( self::ROUTE_CONNECTIONS, 'DELETE' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'errors', $data );
		$this->assertArrayHasKey( 'responses', $data );

		$this->assertArrayHasKey(
			self::ROUTE_SEARCH_CONSOLE,
			$data['responses'],
			'The Search Console connection endpoint should be successfully called by disconnect'
		);
	}
}
