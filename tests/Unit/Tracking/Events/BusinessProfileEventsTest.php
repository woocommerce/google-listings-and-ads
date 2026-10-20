<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking\Events;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\BusinessProfileEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksInterface;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileEventsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking\Events
 */
class BusinessProfileEventsTest extends UnitTest {

	/** @var MockObject|TracksInterface $tracks */
	protected $tracks;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->tracks = $this->createMock( TracksInterface::class );

		$events = new BusinessProfileEvents();
		$events->set_tracks( $this->tracks );
		$events->register();
	}

	public function test_records_a_location_connection() {
		$this->tracks->expects( $this->once() )
			->method( 'record_event' )
			->with( 'google_business_profile_location_connected', [] );

		do_action( 'woocommerce_gla_business_profile_location_connected' );
	}

	public function test_records_a_sync_success_with_its_type() {
		$this->tracks->expects( $this->once() )
			->method( 'record_event' )
			->with( 'google_business_profile_sync_success', [ 'type' => 'create' ] );

		do_action( 'woocommerce_gla_business_profile_sync_success', [ 'type' => 'create' ] );
	}

	public function test_records_a_sync_failure_with_its_type_and_reason() {
		$properties = [
			'type'   => 'create',
			'reason' => 'INVALID_ARGUMENT',
		];

		$this->tracks->expects( $this->once() )
			->method( 'record_event' )
			->with( 'google_business_profile_sync_failure', $properties );

		do_action( 'woocommerce_gla_business_profile_sync_failure', $properties );
	}

	public function test_records_a_sync_event_without_properties_when_a_caller_passes_something_else() {
		$this->tracks->expects( $this->once() )
			->method( 'record_event' )
			->with( 'google_business_profile_sync_success', [] );

		do_action( 'woocommerce_gla_business_profile_sync_success', 'create' );
	}
}
