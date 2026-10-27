<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking\Events;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SearchConsoleConnectionEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksInterface;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchConsoleConnectionEventsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking\Events
 */
class SearchConsoleConnectionEventsTest extends UnitTest {

	/** @var MockObject|TracksInterface $tracks */
	protected $tracks;

	/** @var SearchConsoleConnectionEvents $events */
	protected $events;

	public function setUp(): void {
		parent::setUp();

		$this->tracks = $this->createMock( TracksInterface::class );
		$this->events = new SearchConsoleConnectionEvents();
		$this->events->set_tracks( $this->tracks );
		$this->events->register();
	}

	public function tearDown(): void {
		remove_action( 'woocommerce_gla_search_console_connected', [ $this->events, 'track_search_console_connected' ] );

		parent::tearDown();
	}

	public function test_connected_action_records_one_search_console_connected_event() {
		$this->tracks->expects( $this->once() )
			->method( 'record_event' )
			->with( 'search_console_connected', [] );

		do_action( 'woocommerce_gla_search_console_connected' );
	}
}
