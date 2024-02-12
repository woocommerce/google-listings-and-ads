<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use DateTime;
use DateInterval;
use Exception;
use WC_Helper_Product;

defined( 'ABSPATH' ) || exit;

/**
 * @property Merchant $merchant
 * @property MerchantIssueQuery $merchant_issue_query
 * @property MerchantCenterService $merchant_center_service
 * @property ShoppingContent\AccountStatus $account_status
 * @property ProductMetaQueryHelper $product_meta_query_helper
 * @property MerchantStatuses $merchant_statuses
 * @property ProductRepository $product_repository
 * @property ProductHelper $product_helper
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 * @group MerchantCenterStatuses
 */
class MerchantStatusesTest extends UnitTest {


	/**
	 * Lifetime of the MC Status transient.
	 */
	protected const MC_STATUS_LIFETIME = 60;

	private $merchant;
	private $merchant_issue_query;
	private $merchant_center_service;
	private $account_status;
	private $product_meta_query_helper;
	private $merchant_statuses;
	private $product_repository;
	private $product_helper;
	private $transients;
	private $update_merchant_product_statuses_job;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant                             = $this->createMock( Merchant::class );
		$this->merchant_issue_query                 = $this->createMock( MerchantIssueQuery::class );
		$this->merchant_center_service              = $this->createMock( MerchantCenterService::class );
		$this->account_status                       = $this->createMock( ShoppingContent\AccountStatus::class );
		$this->product_meta_query_helper            = $this->createMock( ProductMetaQueryHelper::class );
		$this->product_repository                   = $this->createMock( ProductRepository::class );
		$this->product_helper                       = $this->createMock( ProductHelper::class );
		$this->transients                           = $this->createMock( TransientsInterface::class );
		$this->update_merchant_product_statuses_job = $this->createMock( UpdateMerchantProductStatuses::class );

		$merchant_issue_table = $this->createMock( MerchantIssueTable::class );

		$container = new Container();
		$container->share( Merchant::class, $this->merchant );
		$container->share( MerchantIssueQuery::class, $this->merchant_issue_query );
		$container->share( MerchantCenterService::class, $this->merchant_center_service );
		$container->share( TransientsInterface::class, $this->transients );
		$container->share( ProductRepository::class, $this->product_repository );
		$container->share( ProductMetaQueryHelper::class, $this->product_meta_query_helper );
		$container->share( ProductHelper::class, $this->product_helper );
		$container->share( MerchantIssueTable::class, $merchant_issue_table );
		$container->share( UpdateMerchantProductStatuses::class, $this->update_merchant_product_statuses_job );

		$this->merchant_statuses = new MerchantStatuses();
		$this->merchant_statuses->set_container( $container );
	}

	public function test_refresh_account_issues() {
		$this->product_meta_query_helper->expects( $this->any() )->method( 'get_all_values' )->willReturn( [] );

		$this->account_status->expects( $this->any() )
			->method( 'getAccountLevelIssues' )
			->willReturn(
				[
					'one'   => new ShoppingContent\AccountStatusAccountLevelIssue(
						[
							'id'            => 'id',
							'title'         => 'title',
							'country'       => 'US',
							'destination'   => 'destination',
							'detail'        => 'detail',
							'documentation' => 'https://example.com',
							'severity'      => 'critical',
						]
					),
					'two'   => new ShoppingContent\AccountStatusAccountLevelIssue(
						[
							'id'            => 'id2',
							'title'         => 'title2',
							'country'       => 'CA',
							'destination'   => 'destination2',
							'detail'        => 'detail2',
							'documentation' => 'https://example.com/2',
							'severity'      => 'error',
						]
					),
					'three' => new ShoppingContent\AccountStatusAccountLevelIssue(
						[
							'id'            => 'id2',
							'title'         => 'title2',
							'country'       => 'US',
							'destination'   => 'destination2',
							'detail'        => 'detail2',
							'documentation' => 'https://example.com/2',
							'severity'      => 'error',
						]
					),
				]
			);

		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->merchant->expects( $this->any() )
			->method( 'get_accountstatus' )
			->willReturn( $this->account_status );

		$issues = [
			md5( 'title' )  => [
				'product_id'           => 0,
				'product'              => 'All products',
				'code'                 => 'id',
				'issue'                => 'title',
				'action'               => 'detail',
				'action_url'           => 'https://example.com',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
				'type'                 => 'account',
				'severity'             => 'critical',
				'source'               => 'mc',
				'applicable_countries' => '["US"]',
			],
			md5( 'title2' ) => [
				'product_id'           => 0,
				'product'              => 'All products',
				'code'                 => 'id2',
				'issue'                => 'title2',
				'action'               => 'detail2',
				'action_url'           => 'https://example.com/2',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
				'type'                 => 'account',
				'severity'             => 'error',
				'source'               => 'mc',
				'applicable_countries' => '["CA","US"]',
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
			->method( 'update_or_insert' )
			->withConsecutive( [ $issues ], [] );
		$this->merchant_statuses->maybe_refresh_status_data( true );
	}

	/*
	 * Test get product statistics using the transient.
	 *
	 */
	public function test_get_product_statistics_when_mc_is_not_connected() {
		$this->merchant_center_service->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

			$this->merchant_center_service->expects( $this->once() )
			->method( 'is_google_connected' )
			->willReturn( false );

		$this->transients->expects( $this->never() )
			->method( 'get' );

		$this->update_merchant_product_statuses_job->expects( $this->never() )->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->never() )->method( 'is_scheduled' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Google account is not connected.' );

		$this->merchant_statuses->get_product_statistics();
	}

	public function test_get_product_statistics_with_transient() {
		$this->merchant_center_service->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'statistics' => [
						MCStatus::APPROVED           => 3,
						MCStatus::PARTIALLY_APPROVED => 1,
						MCStatus::EXPIRING           => 0,
						MCStatus::PENDING            => 0,
						MCStatus::DISAPPROVED        => 3,
						MCStatus::NOT_SYNCED         => 0,
					],
					'loading'    => false,
				]
			);

		$this->update_merchant_product_statuses_job->expects( $this->never() )
			->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 2 ) )
			->method( 'is_scheduled' );

		$product_statistics = $this->merchant_statuses->get_product_statistics();

		$this->assertEquals(
			[
				'active'      => 4,
				'expiring'    => 0,
				'pending'     => 0,
				'disapproved' => 3,
				'not_synced'  => 0,
			],
			$product_statistics['statistics']
		);

		$this->assertEquals(
			false,
			$product_statistics['loading']
		);
	}

	public function test_get_product_statistics_without_transient() {
		$this->merchant_center_service->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				null
			);

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 1 ) )
			->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 2 ) )
			->method( 'is_scheduled' );

		$product_statistics = $this->merchant_statuses->get_product_statistics();

		$this->assertEquals(
			[],
			$product_statistics['statistics']
		);

		$this->assertEquals(
			true,
			$product_statistics['loading']
		);
	}

	public function test_get_product_statistics_with_product_statuses_job_running() {
		$this->merchant_center_service->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'statistics' => [
						MCStatus::APPROVED           => 3,
						MCStatus::PARTIALLY_APPROVED => 1,
						MCStatus::EXPIRING           => 0,
						MCStatus::PENDING            => 0,
						MCStatus::DISAPPROVED        => 3,
						MCStatus::NOT_SYNCED         => 0,
					],
					'loading'    => false,
				]
			);

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 0 ) )
			->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 2 ) )
			->method( 'is_scheduled' )->willReturn( true );

		$product_statistics = $this->merchant_statuses->get_product_statistics();

		$this->assertEquals(
			[],
			$product_statistics['statistics']
		);

		$this->assertEquals(
			true,
			$product_statistics['loading']
		);
	}

	public function test_get_product_statistics_with_force_refresh() {
		$this->merchant_center_service->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'statistics' => [
						MCStatus::APPROVED           => 3,
						MCStatus::PARTIALLY_APPROVED => 1,
						MCStatus::EXPIRING           => 0,
						MCStatus::PENDING            => 0,
						MCStatus::DISAPPROVED        => 3,
						MCStatus::NOT_SYNCED         => 0,
					],
					'loading'    => false,
				]
			);

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 1 ) )
			->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 2 ) )
			->method( 'is_scheduled' )->willReturnOnConsecutiveCalls( false, true );

		$force_refresh      = true;
		$product_statistics = $this->merchant_statuses->get_product_statistics( $force_refresh );

		$this->assertEquals(
			[],
			$product_statistics['statistics']
		);

		$this->assertEquals(
			true,
			$product_statistics['loading']
		);
	}

	public function test_update_product_stats() {
		$product_1        = WC_Helper_Product::create_simple_product();
		$product_2        = WC_Helper_Product::create_simple_product();
		$product_3        = WC_Helper_Product::create_simple_product();
		$variable_product = WC_Helper_Product::create_variation_product();

		$variations     = $variable_product->get_available_variations();
		$variation_id_1 = $variations[0]['variation_id'];
		$variation_id_2 = $variations[1]['variation_id'];

		add_filter(
			'woocommerce_gla_mc_status_lifetime',
			function () {
				return self::MC_STATUS_LIFETIME;
			}
		);

		$this->transients->expects( $this->exactly( 1 ) )
			->method( 'get' );

		$this->transients->expects( $this->once() )
			->method( 'set' )->with(
				Transients::MC_STATUSES,
				$this->callback(
					function ( $value ) {
						$this->assertEquals(
							[

								MCStatus::APPROVED    => 2,
								MCStatus::PARTIALLY_APPROVED => 1,
								MCStatus::EXPIRING    => 1,
								MCStatus::DISAPPROVED => 0,
								MCStatus::NOT_SYNCED  => 0,
								MCStatus::PENDING     => 0,
							],
							$value['statistics']
						);

						$this->assertEquals(
							true,
							$value['loading']
						);

						return true;
					}
				),
				self::MC_STATUS_LIFETIME
			);

		$product_statuses = [
			[
				'product_id'      => $product_1->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
			[
				'product_id'      => $product_2->get_id(),
				'status'          => MCStatus::PARTIALLY_APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
			[
				'product_id'      => $product_3->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P1D' ) ), // Expiring tomorrow
			],
			// Variations are grouped by parent id.
			[
				'product_id'      => $variation_id_1,
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
			[
				'product_id'      => $variation_id_2,
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->merchant_statuses->update_product_stats(
			$product_statuses
		);
	}

	public function test_handle_complete_mc_statuses_fetching() {
		add_filter(
			'woocommerce_gla_mc_status_lifetime',
			function () {
				return self::MC_STATUS_LIFETIME;
			}
		);

		$this->transients->expects( $this->once() )
			->method( 'get' )->with( Transients::MC_STATUSES )->willReturn(
				[
					'statistics' => [
						MCStatus::APPROVED           => 3,
						MCStatus::PARTIALLY_APPROVED => 1,
						MCStatus::EXPIRING           => 0,
						MCStatus::PENDING            => 0,
						MCStatus::DISAPPROVED        => 1,
						MCStatus::NOT_SYNCED         => 0,
					],
					'loading'    => false,
				]
			);

			$this->product_repository->expects( $this->once() )->method( 'find_all_product_ids' )->willReturn( [ 1, 2, 3,  4, 5, 6 ] );

			$this->transients->expects( $this->once() )
			->method( 'set' )->with(
				Transients::MC_STATUSES,
				$this->callback(
					function ( $value ) {
						$this->assertEquals(
							[
								MCStatus::APPROVED    => 3,
								MCStatus::PARTIALLY_APPROVED => 1,
								MCStatus::EXPIRING    => 0,
								MCStatus::PENDING     => 0,
								MCStatus::DISAPPROVED => 1,
								MCStatus::NOT_SYNCED  => 1,
							],
							$value['statistics']
						);

						$this->assertEquals(
							false,
							$value['loading']
						);

						return true;
					}
				),
				self::MC_STATUS_LIFETIME
			);

			$this->merchant_statuses->handle_complete_mc_statuses_fetching();
	}
}
