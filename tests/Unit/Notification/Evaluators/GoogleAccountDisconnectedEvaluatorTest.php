<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\GoogleAccountDisconnectedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleAccountDisconnectedEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class GoogleAccountDisconnectedEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|AdsService $ads */
	protected $ads;

	/** @var GoogleAccountDisconnectedEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->ads             = $this->createMock( AdsService::class );
		$this->evaluator       = new GoogleAccountDisconnectedEvaluator( $this->merchant_center, $this->ads );
	}

	public function test_get_id() {
		$this->assertEquals( 'google-account-disconnected', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::GOOGLE_ACCOUNT_DISCONNECTED, $this->evaluator->get_priority() );
	}

	public function test_dismissal_lasts_until_next_login_per_user() {
		$this->assertEquals( NotificationSnoozeDurations::UNTIL_NEXT_LOGIN, $this->evaluator->get_snooze_duration() );
		$this->assertNotInstanceOf( SiteScopedNotificationEvaluatorInterface::class, $this->evaluator );
	}

	public function test_should_show_when_setup_complete_and_google_disconnected() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_google_connected' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_google_connected() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_only_ads_setup_complete_and_google_disconnected() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->ads->method( 'is_setup_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_google_connected' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_setup_not_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->ads->method( 'is_setup_complete' )->willReturn( false );
		$this->merchant_center->method( 'is_google_connected' )->willReturn( false );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
