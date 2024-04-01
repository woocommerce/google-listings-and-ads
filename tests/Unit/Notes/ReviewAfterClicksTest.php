<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReviewAfterClicks;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReviewAfterClicksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes
 */
class ReviewAfterClicksTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|MerchantMetrics $merchant_metrics */
	protected $merchant_metrics;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var ReviewAfterClicks $note */
	protected $note;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant_center  = $this->createMock( MerchantCenterService::class );
		$this->merchant_metrics = $this->createMock( MerchantMetrics::class );
		$this->wp               = $this->createMock( WP::class );

		$this->note = new ReviewAfterClicks( $this->merchant_metrics, $this->wp );
		$this->note->set_merchant_center_object( $this->merchant_center );
	}

	public function test_name() {
		$this->assertEquals( 'gla-review-after-clicks', $this->note->get_name() );
	}

	public function test_note_entry() {
		$note = $this->note->get_entry();

		$this->assertEquals( 'gla-review-after-clicks', $note->get_name() );
		$this->assertEquals( 'gla', $note->get_source() );
		$this->assertEquals( 'leave-review', $note->get_actions()[0]->name );
	}

	public function test_should_not_add_already_added() {
		$this->note->get_entry()->save();

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_not_connected() {
		$this->merchant_center->method( 'is_connected' )->willReturn( false );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_low_merchant_metrics() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->merchant_metrics->expects( $this->once() )
			->method( 'get_cached_free_listing_metrics' )
			->willReturn(
				[ 'clicks' => 10 ]
			);

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->merchant_metrics->expects( $this->once() )
			->method( 'get_cached_free_listing_metrics' )
			->willReturn(
				[ 'clicks' => 11 ]
			);

		$this->assertTrue( $this->note->should_be_added() );
	}
}
