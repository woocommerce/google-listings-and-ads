<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\DisconnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\OnboardingController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager\AccountController as TagManagerAccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings as TagManagerSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshTagManagerAdsConversionConflict;
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

	/** @var MockObject|TagManagerConnection $tag_manager_connection */
	protected $tag_manager_connection;

	/** @var MockObject|RefreshTagManagerAdsConversionConflict $tag_manager_conflict_job */
	protected $tag_manager_conflict_job;

	protected const ROUTE_CONNECTIONS         = '/wc/gla/connections';
	protected const ROUTE_ONBOARDING_COMPLETE = '/wc/gla/google/onboarding/complete';
	protected const ROUTE_TAG_MANAGER         = '/wc/gla/tag-manager/connection';

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

		// Register the Tag Manager AccountController so its disconnect route can be called by DisconnectController
		$this->tag_manager_connection   = $this->createMock( TagManagerConnection::class );
		$this->tag_manager_conflict_job = $this->createMock( RefreshTagManagerAdsConversionConflict::class );

		$job_repository = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )
			->with( RefreshTagManagerAdsConversionConflict::class )
			->willReturn( $this->tag_manager_conflict_job );

		$tag_manager_controller = new TagManagerAccountController(
			$this->server,
			$this->tag_manager_connection,
			$this->createMock( TagManagerSiteTag::class ),
			$this->createMock( TagManagerSettings::class ),
			$job_repository
		);
		$tag_manager_controller->register();

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
	 * Test that disconnect calls the Tag Manager connection DELETE endpoint.
	 *
	 * Note: The actual DELETE endpoint behavior is tested in the Tag Manager AccountControllerTest.
	 * This test only verifies that DisconnectController includes it in the disconnect flow.
	 */
	public function test_disconnect_calls_tag_manager_connection_endpoint(): void {
		$this->tag_manager_connection->expects( $this->once() )
			->method( 'disconnect' )
			->willReturn( 'Successfully disconnected.' );

		$this->tag_manager_conflict_job->expects( $this->once() )
			->method( 'unschedule' );

		$response = $this->do_request( self::ROUTE_CONNECTIONS, 'DELETE' );

		$this->assertArrayHasKey(
			self::ROUTE_TAG_MANAGER,
			$response->get_data()['responses'],
			'The Tag Manager connection endpoint should be successfully called by disconnect'
		);
	}
}
