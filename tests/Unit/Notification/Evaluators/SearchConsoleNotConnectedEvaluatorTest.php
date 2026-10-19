<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SearchConsoleNotConnectedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchConsoleNotConnectedEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SearchConsoleNotConnectedEvaluatorTest extends UnitTest {

	/** @var Connection|MockObject */
	protected $connection;

	/** @var SearchConsoleNotConnectedEvaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->evaluator  = new SearchConsoleNotConnectedEvaluator( $this->connection );
	}

	public function test_get_id() {
		$this->assertEquals( 'search-console-not-connected', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SEARCH_CONSOLE_NOT_CONNECTED, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::SEARCH_CONSOLE_NOT_CONNECTED, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_search_console_is_not_connected() {
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_search_console_is_connected() {
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
