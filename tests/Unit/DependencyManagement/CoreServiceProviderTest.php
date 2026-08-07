<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\NotificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\BadgeWidgetEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CampaignNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CollectReviewsEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\EnhancedConversionsOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\NotOnboardedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\PausedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ProductIssuesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ReadyButNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\RecommendationsAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SalesNotGrowingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SkippedCampaignCreationEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\PaidOrdersEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TrackingOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Request;
use WP_Test_Spy_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class CoreServiceProviderTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DependencyManagement
 * @group Notifications
 */
class CoreServiceProviderTest extends ContainerAwareUnitTest {

	/**
	 * Expected notification evaluator classes registered in the container.
	 *
	 * @var string[]
	 */
	private const EVALUATOR_CLASSES = [
		AbandonedOnboardingEvaluator::class,
		BadgeWidgetEvaluator::class,
		CampaignNoSalesEvaluator::class,
		CollectReviewsEvaluator::class,
		CouponsNotSyncedEvaluator::class,
		EnhancedConversionsOffEvaluator::class,
		NotOnboardedEvaluator::class,
		PausedCampaignEvaluator::class,
		ProductIssuesEvaluator::class,
		ReadyButNoSalesEvaluator::class,
		RecommendationsAvailableEvaluator::class,
		SalesNotGrowingEvaluator::class,
		SkippedCampaignCreationEvaluator::class,
		PaidOrdersEvaluator::class,
		TrackingOffEvaluator::class,
	];

	public function test_notification_service_resolves_from_container(): void {
		$service = $this->container->get( NotificationService::class );

		$this->assertInstanceOf( NotificationService::class, $service );
	}

	public function test_notification_evaluators_resolve_from_container(): void {
		$this->assertTrue( $this->container->has( NotificationEvaluatorInterface::class ) );

		$evaluators = $this->container->get( NotificationEvaluatorInterface::class );

		$this->assertCount( count( self::EVALUATOR_CLASSES ), $evaluators );

		foreach ( $evaluators as $evaluator ) {
			$this->assertInstanceOf( NotificationEvaluatorInterface::class, $evaluator );
		}

		$evaluator_classes = array_map( 'get_class', $evaluators );

		foreach ( self::EVALUATOR_CLASSES as $evaluator_class ) {
			$this->assertContains( $evaluator_class, $evaluator_classes );
		}
	}

	public function test_get_notifications_endpoint_returns_valid_response(): void {
		$this->login_as_administrator();

		global $wp_rest_server;
		$wp_rest_server = new WP_Test_Spy_REST_Server();
		$server         = new RESTServer( $wp_rest_server );

		/** @var MockObject|NotificationService $service */
		$service = $this->createMock( NotificationService::class );
		$service->method( 'get_notifications' )->willReturn( [] );

		$controller = new NotificationController( $server, $service );
		$controller->register();

		$request  = new WP_REST_Request( 'GET', '/wc/gla/notifications' );
		$response = $server->dispatch_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'notifications', $response->get_data() );
		$this->assertIsArray( $response->get_data()['notifications'] );
	}

	public function test_notification_controller_resolves_from_container(): void {
		$this->assertTrue( $this->container->has( 'rest_controller' ) );

		$controllers        = $this->container->get( 'rest_controller' );
		$controller_classes = array_map( 'get_class', $controllers );

		$this->assertContains( NotificationController::class, $controller_classes );
	}
}
