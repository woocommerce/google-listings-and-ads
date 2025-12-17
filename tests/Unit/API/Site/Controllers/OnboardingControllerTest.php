<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\OnboardingController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Class OnboardingControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class OnboardingControllerTest extends RESTControllerUnitTest {

	/** @var OnboardingController $controller */
	protected $controller;

	protected const ROUTE_COMPLETE = '/wc/gla/google/onboarding/complete';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new OnboardingController( $this->server );
		$this->controller->register();
	}

	/**
	 * Test completing onboarding successfully.
	 */
	public function test_complete_onboarding(): void {
		// Track actions that were fired
		$onboarding_completed_fired = false;
		$track_event_fired          = false;
		$track_event_name           = '';
		$track_event_details        = null;

		// Add hooks to check if actions are fired
		add_action(
			'woocommerce_gla_onboarding_completed',
			function () use ( &$onboarding_completed_fired ) {
				$onboarding_completed_fired = true;
			}
		);

		add_action(
			'woocommerce_gla_track_event',
			function ( $event_name, $event_details ) use ( &$track_event_fired, &$track_event_name, &$track_event_details ) {
				$track_event_fired   = true;
				$track_event_name    = $event_name;
				$track_event_details = $event_details;
			},
			10,
			2
		);

		$response = $this->do_request( self::ROUTE_COMPLETE, 'POST' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $response->get_data()['status'] );
		$this->assertEquals( 'Successfully onboarded service based merchant', $response->get_data()['message'] );

		// Verify actions were fired
		$this->assertTrue( $onboarding_completed_fired, 'woocommerce_gla_onboarding_completed action should be fired' );
		$this->assertTrue( $track_event_fired, 'woocommerce_gla_track_event action should be fired' );
		$this->assertEquals( 'onboarding_complete', $track_event_name, 'Event name should be onboarding_complete' );
		$this->assertEquals( [], $track_event_details, 'Event details should be an empty array' );
	}

	/**
	 * Test that the route is registered correctly.
	 */
	public function test_route_registered(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( self::ROUTE_COMPLETE, $routes );
	}
}
