<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TagManagerAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerAvailableEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class TagManagerAvailableEvaluatorTest extends UnitTest {

	/** @var MockObject|TagManagerConnection $tag_manager_connection */
	protected $tag_manager_connection;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var TagManagerAvailableEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Creates the evaluator with mocked dependencies before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->tag_manager_connection = $this->createMock( TagManagerConnection::class );
		$this->onboarding_completed   = $this->createMock( OnboardingCompleted::class );

		$this->evaluator = new TagManagerAvailableEvaluator( $this->tag_manager_connection, $this->onboarding_completed );
	}

	public function test_get_id() {
		$this->assertEquals( 'tag-manager-available', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::TAG_MANAGER_AVAILABLE, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_permanent() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_onboarding_incomplete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->tag_manager_connection->expects( $this->never() )->method( 'get_connection_data' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_no_account_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn(
			[
				'account_id'          => null,
				'account_name'        => null,
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_stored_connection_has_no_keys() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn( [] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_account_connected_without_container() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn(
			[
				'account_id'          => '123',
				'account_name'        => 'Example Store',
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_account_and_container_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->tag_manager_connection->method( 'get_connection_data' )->willReturn(
			[
				'account_id'          => '123',
				'account_name'        => 'Example Store',
				'container_id'        => '456',
				'container_name'      => 'Example Store - Web',
				'container_public_id' => 'GTM-ABCDEFG',
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
