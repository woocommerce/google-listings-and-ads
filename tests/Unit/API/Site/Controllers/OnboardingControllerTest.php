<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\OnboardingController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class OnboardingControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class OnboardingControllerTest extends RESTControllerUnitTest {

	/** @var OnboardingController $controller */
	protected $controller;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	protected const ROUTE_ONBOARDING_COMPLETE = '/wc/gla/google/onboarding/complete';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->controller = new OnboardingController( $this->server );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
	}

	/**
	 * Test completing onboarding successfully.
	 */
	public function test_complete_onboarding(): void {
		// Track actions that were fired
		$onboarding_completed_fired = false;

		// Add hook to check if action is fired
		add_action(
			'woocommerce_gla_onboarding_completed',
			function () use ( &$onboarding_completed_fired ) {
				$onboarding_completed_fired = true;
			}
		);

		$response = $this->do_request( self::ROUTE_ONBOARDING_COMPLETE, 'POST' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully onboarded service based merchant.',
			],
			$response->get_data()
		);

		// Verify action was fired
		$this->assertTrue( $onboarding_completed_fired, 'woocommerce_gla_onboarding_completed action should be fired' );
	}

	/**
	 * Test that the route is registered correctly.
	 */
	public function test_route_registered(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( self::ROUTE_ONBOARDING_COMPLETE, $routes );
	}

	/**
	 * Test deleting onboarding completion successfully.
	 */
	public function test_delete_onboarding_completion(): void {
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::ONBOARDING_COMPLETED_AT )
			->willReturn( true );

		$response = $this->do_request( self::ROUTE_ONBOARDING_COMPLETE, 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully deleted onboarding completion status.',
			],
			$response->get_data()
		);
	}
}
