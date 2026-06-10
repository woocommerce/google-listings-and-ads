<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SkippedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SkippedCampaignEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SkippedCampaignEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var SkippedCampaignEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service     = $this->createMock( AdsService::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->evaluator       = new SkippedCampaignEvaluator();
		$this->evaluator->set_ads_object( $this->ads_service );
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
	}

	public function test_get_id() {
		$this->assertEquals( 'skipped-campaign-creation', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SKIPPED_CAMPAIGN_CREATION, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_null() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_mc_complete_and_ads_incomplete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_mc_incomplete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_ads_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
