<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SmallProductImagesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SmallProductImagesEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SmallProductImagesEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var MockObject|ProductImageSizeAudit $image_size_audit */
	protected $image_size_audit;

	/** @var SmallProductImagesEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center              = $this->createMock( MerchantCenterService::class );
		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->image_size_audit             = $this->createMock( ProductImageSizeAudit::class );
		$this->evaluator                    = new SmallProductImagesEvaluator( $this->service_based_merchant_state, $this->image_size_audit );
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
	}

	public function test_get_id() {
		$this->assertEquals( 'small-product-images', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SMALL_PRODUCT_IMAGES, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::SMALL_PRODUCT_IMAGES, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_small_images_exist() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->image_size_audit->method( 'get_small_image_product_count' )->willReturn( 3 );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_small_images() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->image_size_audit->method( 'get_small_image_product_count' )->willReturn( 0 );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_not_connected() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->image_size_audit->expects( $this->never() )->method( 'get_small_image_product_count' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_for_service_based_merchant() {
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->image_size_audit->expects( $this->never() )->method( 'get_small_image_product_count' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
