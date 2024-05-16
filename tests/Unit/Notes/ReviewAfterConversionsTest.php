<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReviewAfterConversions;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReviewAfterConversionsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notes
 */
class ReviewAfterConversionsTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|MerchantMetrics $merchant_metrics */
	protected $merchant_metrics;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var ReviewAfterConversions $note */
	protected $note;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ads_service      = $this->createMock( AdsService::class );
		$this->merchant_metrics = $this->createMock( MerchantMetrics::class );
		$this->wp               = $this->createMock( WP::class );

		$this->note = new ReviewAfterConversions( $this->merchant_metrics, $this->wp );
		$this->note->set_ads_object( $this->ads_service );
	}

	public function test_name() {
		$this->assertEquals( 'gla-review-after-conversions', $this->note->get_name() );
	}

	public function test_note_entry() {
		$note = $this->note->get_entry();

		$this->assertEquals( 'gla-review-after-conversions', $note->get_name() );
		$this->assertEquals( 'gla', $note->get_source() );
		$this->assertEquals( 'leave-review', $note->get_actions()[0]->name );
	}

	public function test_should_not_add_already_added() {
		$this->note->get_entry()->save();

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_not_connected() {
		$this->ads_service->method( 'is_connected' )->willReturn( false );

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_not_add_low_merchant_metrics() {
		$this->ads_service->method( 'is_connected' )->willReturn( true );
		$this->merchant_metrics->expects( $this->once() )
			->method( 'get_cached_ads_metrics' )
			->willReturn(
				[ 'conversions' => 0 ]
			);

		$this->assertFalse( $this->note->should_be_added() );
	}

	public function test_should_add() {
		$this->ads_service->method( 'is_connected' )->willReturn( true );
		$this->merchant_metrics->expects( $this->once() )
			->method( 'get_cached_ads_metrics' )
			->willReturn(
				[ 'conversions' => 1 ]
			);

		$this->assertTrue( $this->note->should_be_added() );
	}
}
