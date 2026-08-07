<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CollectReviewsEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class CollectReviewsEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class CollectReviewsEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var CollectReviewsEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center      = $this->createMock( MerchantCenterService::class );
		$this->onboarding_completed = $this->createMock( OnboardingCompleted::class );
		$this->options              = $this->createMock( OptionsInterface::class );

		$this->evaluator = new CollectReviewsEvaluator( $this->onboarding_completed );
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
		$this->evaluator->set_options_object( $this->options );
	}

	public function test_get_id() {
		$this->assertEquals( 'collect-reviews', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::COLLECT_REVIEWS, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::COLLECT_REVIEWS, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_onboarded_connected_and_collection_disabled() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'collect_reviews_after_purchase' => false ] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_onboarding_not_complete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->merchant_center->expects( $this->never() )->method( 'is_connected' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_without_merchant_center_connection() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_connected' )->willReturn( false );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_collection_already_enabled() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_CENTER, [] )
			->willReturn( [ 'collect_reviews_after_purchase' => true ] );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
