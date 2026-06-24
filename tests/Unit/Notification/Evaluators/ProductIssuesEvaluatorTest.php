<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\ProductIssuesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductIssuesEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class ProductIssuesEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var ProductIssuesEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->transients      = $this->createMock( TransientsInterface::class );
		$this->evaluator       = new ProductIssuesEvaluator();
		$this->evaluator->set_merchant_center_object( $this->merchant_center );
		$this->evaluator->set_transients_object( $this->transients );
	}

	public function test_get_id() {
		$this->assertEquals( 'product-issues', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::PRODUCT_ISSUES, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_null() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_disapproved_products_exist() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->transients->method( 'get' )
			->with( TransientsInterface::MC_STATUSES )
			->willReturn(
				[
					'statistics' => [
						MCStatus::APPROVED    => 5,
						MCStatus::DISAPPROVED => 2,
						MCStatus::PENDING     => 1,
					],
				]
			);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_disapproved_products() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->transients->method( 'get' )
			->with( TransientsInterface::MC_STATUSES )
			->willReturn(
				[
					'statistics' => [
						MCStatus::APPROVED    => 5,
						MCStatus::DISAPPROVED => 0,
						MCStatus::PENDING     => 1,
					],
				]
			);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_transient_missing() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->transients->method( 'get' )
			->with( TransientsInterface::MC_STATUSES )
			->willReturn( null );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_mc_not_connected() {
		$this->merchant_center->method( 'is_connected' )->willReturn( false );

		$this->transients->expects( $this->never() )
			->method( 'get' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
