<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\EnhancedConversionsOffEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class EnhancedConversionsOffEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class EnhancedConversionsOffEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var EnhancedConversionsOffEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service = $this->createMock( AdsService::class );
		$this->options     = $this->createMock( OptionsInterface::class );

		$this->evaluator = new EnhancedConversionsOffEvaluator();
		$this->evaluator->set_ads_object( $this->ads_service );
		$this->evaluator->set_options_object( $this->options );
	}

	public function test_get_id() {
		$this->assertEquals( 'enhanced-conversions-off', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::ENHANCED_CONVERSIONS_OFF, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::ENHANCED_CONVERSIONS_OFF, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_ads_account_connected_and_enhanced_conversions_disabled() {
		$this->ads_service->method( 'connected_account' )->willReturn( true );
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED, false )
			->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_ads_account_not_connected() {
		$this->ads_service->method( 'connected_account' )->willReturn( false );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_enhanced_conversions_enabled() {
		$this->ads_service->method( 'connected_account' )->willReturn( true );
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED, false )
			->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
