<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SearchConsoleNotConnectedEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
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

	/** @var OnboardingCompleted|MockObject */
	protected $onboarding_completed;

	/** @var SearchConsoleNotConnectedEvaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->connection           = $this->createMock( Connection::class );
		$this->onboarding_completed = $this->createMock( OnboardingCompleted::class );
		$this->evaluator            = new SearchConsoleNotConnectedEvaluator( $this->connection, $this->onboarding_completed );
	}

	public function test_get_id() {
		$this->assertEquals( 'search-console-not-connected', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SEARCH_CONSOLE_NOT_CONNECTED, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_null() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_setup_is_finished_and_search_console_is_not_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_search_console_is_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_before_setup_is_finished() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->connection->method( 'is_connected_from_stored_data' )->willReturn( false );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
