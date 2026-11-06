<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\DisconnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\OnboardingController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Response as Response;

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

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	protected const ROUTE_CONNECTIONS         = '/wc/gla/connections';
	protected const ROUTE_ONBOARDING_COMPLETE = '/wc/gla/google/onboarding/complete';

	/**
	 * Service disconnect endpoints called by DisconnectController, other than onboarding complete.
	 */
	protected const SERVICE_DISCONNECT_ROUTES = [
		'ads/connection',
		'mc/connection',
		'google/connect',
		'jetpack/connect',
		'rest-api/authorize',
		'youtube/connection',
	];

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

		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->controller                   = new DisconnectController( $this->server, $this->service_based_merchant_state );
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

	public function test_disconnect_resets_supported_products_confirmation_when_all_services_disconnect(): void {
		$this->register_service_disconnect_routes();

		$this->service_based_merchant_state->expects( $this->once() )
			->method( 'reset_supported_products_confirmation' );

		$response = $this->do_request( self::ROUTE_CONNECTIONS, 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $response->get_data()['errors'] );
	}

	public function test_disconnect_keeps_supported_products_confirmation_when_a_service_fails_to_disconnect(): void {
		$this->register_service_disconnect_routes( 'mc/connection' );

		$this->service_based_merchant_state->expects( $this->never() )
			->method( 'reset_supported_products_confirmation' );

		$response = $this->do_request( self::ROUTE_CONNECTIONS, 'DELETE' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( '/wc/gla/mc/connection', $response->get_data()['errors'] );
	}

	/**
	 * Register stub DELETE routes for the services DisconnectController disconnects.
	 *
	 * @param string|null $failing_route Route that should respond with an error.
	 */
	protected function register_service_disconnect_routes( ?string $failing_route = null ): void {
		foreach ( self::SERVICE_DISCONNECT_ROUTES as $route ) {
			$this->server->register_route(
				'wc/gla',
				$route,
				[
					[
						'methods'             => TransportMethods::DELETABLE,
						'callback'            => function () use ( $route, $failing_route ) {
							return $route === $failing_route
								? new Response( [ 'message' => 'error' ], 400 )
								: new Response( [ 'status' => 'success' ], 200 );
						},
						'permission_callback' => '__return_true',
					],
				]
			);
		}

		$this->options->method( 'delete' )->willReturn( true );
	}
}
