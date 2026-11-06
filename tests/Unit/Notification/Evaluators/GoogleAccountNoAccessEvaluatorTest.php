<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\GoogleAccountNoAccessEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleAccountNoAccessEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class GoogleAccountNoAccessEvaluatorTest extends UnitTest {

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|AdsService $ads */
	protected $ads;

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var GoogleAccountNoAccessEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->ads             = $this->createMock( AdsService::class );
		$this->connection      = $this->createMock( Connection::class );
		$this->evaluator       = new GoogleAccountNoAccessEvaluator( $this->merchant_center, $this->ads, $this->connection );
	}

	public function test_get_id() {
		$this->assertEquals( 'google-account-no-access', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::GOOGLE_ACCOUNT_NO_ACCESS, $this->evaluator->get_priority() );
	}

	public function test_dismissal_lasts_until_next_login_per_user() {
		$this->assertEquals( NotificationSnoozeDurations::UNTIL_NEXT_LOGIN, $this->evaluator->get_snooze_duration() );
		$this->assertNotInstanceOf( SiteScopedNotificationEvaluatorInterface::class, $this->evaluator );
	}

	public function test_invalidation_hooks_fire_when_connected_accounts_change() {
		$this->assertEquals(
			[
				'update_option_gla_google_connected',
				'add_option_gla_merchant_id',
				'update_option_gla_merchant_id',
				'delete_option_gla_merchant_id',
				'add_option_gla_ads_id',
				'update_option_gla_ads_id',
				'delete_option_gla_ads_id',
			],
			$this->evaluator->get_invalidation_hooks()
		);
	}

	public function test_refresh_without_remote_call_when_setup_not_complete() {
		$this->mock_store_state( false, true );
		$this->connection->expects( $this->never() )->method( 'get_access_status' );

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_refresh_without_remote_call_when_google_disconnected() {
		$this->mock_store_state( true, false );
		$this->connection->expects( $this->never() )->method( 'get_access_status' );

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_refresh_for_ads_only_store_when_ads_access_missing() {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( false );
		$this->ads->method( 'is_setup_complete' )->willReturn( true );
		$this->merchant_center->method( 'is_google_connected' )->willReturn( true );
		$this->connection->method( 'get_access_status' )->willReturn(
			[
				'status'     => 'connected',
				'ads_access' => false,
			]
		);

		$this->assertTrue( $this->evaluator->refresh() );
		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_refresh_when_merchant_center_access_missing() {
		$this->mock_store_state( true, true );
		$this->mock_access_status( false, true );

		$this->assertTrue( $this->evaluator->refresh() );
	}

	public function test_refresh_when_ads_access_missing() {
		$this->mock_store_state( true, true );
		$this->mock_access_status( true, false );

		$this->assertTrue( $this->evaluator->refresh() );
	}

	public function test_refresh_with_access_to_both_accounts() {
		$this->mock_store_state( true, true );
		$this->mock_access_status( true, true );

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_refresh_without_ads_account() {
		$this->mock_store_state( true, true );
		$this->connection->method( 'get_access_status' )->willReturn(
			[
				'status'          => 'connected',
				'merchant_access' => true,
			]
		);

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_refresh_when_access_unknown() {
		$this->mock_store_state( true, true );
		$this->mock_access_status( null, null );

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_refresh_when_google_reports_not_connected() {
		$this->mock_store_state( true, true );
		$this->connection->method( 'get_access_status' )->willReturn( [ 'status' => 'disconnected' ] );

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_refresh_when_status_check_throws() {
		$this->mock_store_state( true, true );
		$this->connection->method( 'get_access_status' )->willThrowException( new Exception( 'Error retrieving status' ) );

		$this->assertFalse( $this->evaluator->refresh() );
	}

	public function test_should_show_reads_stored_result_without_remote_call() {
		$this->mock_store_state( true, true );
		$this->store_result( 1 );
		$this->connection->expects( $this->never() )->method( 'get_access_status' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_stored_result_once_google_disconnected() {
		$this->mock_store_state( true, false );
		$this->store_result( 1 );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_needs_refresh_when_nothing_stored() {
		$this->mock_store_state( true, true );

		$this->assertTrue( $this->evaluator->needs_refresh() );
	}

	public function test_no_refresh_needed_when_not_set_up_and_connected() {
		$this->mock_store_state( true, false );

		$this->assertFalse( $this->evaluator->needs_refresh() );
	}

	public function test_invalidate_hides_stored_result_until_refreshed() {
		$this->mock_store_state( true, true );
		$this->store_result( 1 );

		$this->evaluator->invalidate();

		$this->assertFalse( $this->evaluator->should_show() );
		$this->assertTrue( $this->evaluator->needs_refresh() );
	}

	public function test_refresh_shows_result_computed_after_invalidation() {
		$this->mock_store_state( true, true );
		$this->mock_access_status( false, true );

		// Invalidated a minute ago; this refresh starts later and checks the new accounts.
		$this->store_result( 1, time() - 120, time() - 60 );
		$this->evaluator->refresh();

		$this->assertTrue( $this->evaluator->should_show() );
		$this->assertFalse( $this->evaluator->needs_refresh() );
	}

	public function test_invalidation_during_refresh_keeps_result_hidden() {
		$this->mock_store_state( true, true );
		$this->store_result( 0 );

		// The accounts change while the refresh is still checking the previous ones.
		$this->connection->method( 'get_access_status' )->willReturnCallback(
			function () {
				$this->evaluator->invalidate();

				return [
					'status'          => 'connected',
					'merchant_access' => false,
					'ads_access'      => true,
				];
			}
		);

		$this->evaluator->refresh();

		$this->assertFalse( $this->evaluator->should_show() );
		$this->assertTrue( $this->evaluator->needs_refresh() );
	}

	/**
	 * Mock whether Merchant Center setup is complete and a Google account is connected.
	 *
	 * @param bool $setup_complete
	 * @param bool $google_connected
	 */
	private function mock_store_state( bool $setup_complete, bool $google_connected ): void {
		$this->merchant_center->method( 'is_setup_complete' )->willReturn( $setup_complete );
		$this->merchant_center->method( 'is_google_connected' )->willReturn( $google_connected );
	}

	/**
	 * Mock a connected status with the given access check results.
	 *
	 * @param bool|null $merchant_access
	 * @param bool|null $ads_access
	 */
	private function mock_access_status( ?bool $merchant_access, ?bool $ads_access ): void {
		$this->connection->method( 'get_access_status' )->willReturn(
			[
				'status'          => 'connected',
				'merchant_access' => $merchant_access,
				'ads_access'      => $ads_access,
			]
		);
	}

	/**
	 * Store a result in the evaluator's cache. By default it was computed now and never invalidated.
	 *
	 * @param int      $value
	 * @param int|null $computed_at    When the result was computed. Defaults to now.
	 * @param int      $invalidated_at When the result was last invalidated, or 0 for never.
	 */
	private function store_result( int $value, ?int $computed_at = null, int $invalidated_at = 0 ): void {
		set_transient(
			NotificationCacheKeys::for_site( 'google-account-no-access' ),
			[
				'value'          => $value,
				'computed_at'    => $computed_at ?? time(),
				'invalidated_at' => $invalidated_at,
			],
			HOUR_IN_SECONDS
		);
	}
}
