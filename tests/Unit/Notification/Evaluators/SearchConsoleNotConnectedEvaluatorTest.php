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

	public function test_should_show_when_connection_state_has_not_been_stored() {
		$this->connection->method( 'get_connection_data' )->willReturn( [] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	/**
	 * @param string $state Search Console connection state.
	 *
	 * @dataProvider disconnected_state_provider
	 */
	public function test_should_show_when_search_console_is_not_fully_connected( string $state ) {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'state' => $state ] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_search_console_is_connected() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'state' => Connection::STATE_CONNECTED ] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * @return array
	 */
	public function disconnected_state_provider(): array {
		return [
			'disconnected'      => [ Connection::STATE_DISCONNECTED ],
			'incomplete'        => [ Connection::STATE_INCOMPLETE ],
			'action needed'     => [ Connection::STATE_ACTION_NEEDED ],
			'reconnect'         => [ Connection::STATE_RECONNECT ],
			'connection failed' => [ Connection::STATE_CONNECTION_FAILED ],
			'transient error'   => [ Connection::STATE_TRANSIENT_ERROR ],
		];
	}
}
