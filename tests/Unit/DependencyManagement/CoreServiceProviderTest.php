<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\NotificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\AbandonedOnboardingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CampaignNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CouponsNotSyncedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\EnhancedConversionsOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\NotOnboarded90DaysEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\PausedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ProductIssuesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ReadyButNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\RecommendationsAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SalesNotGrowingEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SkippedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\Sold10ItemsEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TrackingOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use WP_REST_Request;

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
		CampaignNoSalesEvaluator::class,
		CouponsNotSyncedEvaluator::class,
		EnhancedConversionsOffEvaluator::class,
		NotOnboarded90DaysEvaluator::class,
		PausedCampaignEvaluator::class,
		ProductIssuesEvaluator::class,
		ReadyButNoSalesEvaluator::class,
		RecommendationsAvailableEvaluator::class,
		SalesNotGrowingEvaluator::class,
		SkippedCampaignEvaluator::class,
		Sold10ItemsEvaluator::class,
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

		do_action( 'rest_api_init' );

		$request  = new WP_REST_Request( 'GET', '/wc/gla/notifications' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'notifications', $response->get_data() );
		$this->assertIsArray( $response->get_data()['notifications'] );
	}

	public function test_notification_controller_resolves_from_container(): void {
		$controller = $this->container->get( NotificationController::class );

		$this->assertInstanceOf( NotificationController::class, $controller );
	}
}
