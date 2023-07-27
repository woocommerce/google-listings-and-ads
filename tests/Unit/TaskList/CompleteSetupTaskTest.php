<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\TaskList\CompleteSetupTask;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class CompleteSetupTaskTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\TaskList
 */
class CompleteSetupTaskTest extends UnitTest {

	/** @var MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var CompleteSetupTask $task */
	protected $task;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		// Mock tasks list.
		TaskLists::clear_lists();
		TaskLists::add_list( [ 'id' => 'extended' ] );
		// Mock MC setup.
		$this->merchant_center = $this->createMock( MerchantCenterService::class );

		$this->task = new CompleteSetupTask();
		$this->task->set_merchant_center_object( $this->merchant_center );
	}

	/**
	 * Test that after the task is registered and `init` fired,
	 * the task is added to the `extended` list and the `add_woocommerce_extended_task_list_item` action is called.
	 */
	public function test_register() {
		$this->task->register();
		do_action( 'init', 1 );

		$this->assertGreaterThan( 0, did_action( 'add_woocommerce_extended_task_list_item' ) );

		$this->assertNotNull( TaskLists::get_list( 'extended' )->get_task( $this->task->get_id() ) );
		$this->assertEquals( $this->task, TaskLists::get_list( 'extended' )->get_task( $this->task->get_id() ) );
	}

	public function test_id() {
		$this->assertEquals( 'gla_complete_setup', $this->task->get_id() );
	}

	public function test_title() {
		$this->assertEquals( 'Set up Google Listings & Ads', $this->task->get_title() );
	}

	public function test_content() {
		$this->assertEquals( '', $this->task->get_content() );
	}

	public function test_time() {
		$this->assertEquals( '20 minutes', $this->task->get_time() );
	}

	public function test_dismissable() {
		$this->assertTrue( $this->task->is_dismissable() );
	}

	public function test_is_complete_and_mc_setup_not_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );

		$this->assertFalse( $this->task->is_complete() );
	}

	public function test_is_complete_and_mc_setup_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->assertTrue( $this->task->is_complete() );
	}

	public function test_get_action_url_and_mc_setup_not_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );

		$this->assertStringContainsString( 'admin.php?page=wc-admin&path=/google/start', $this->task->get_action_url() );
	}

	public function test_get_action_url_and_mc_setup_complete() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( true );

		$this->assertStringContainsString( 'admin.php?page=wc-admin&path=/google/dashboard', $this->task->get_action_url() );
	}
}
