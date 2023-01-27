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
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use WP_Post;

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
 * @property ShoppingContent\ProductStatus $product_status
 * @property ShoppingContent\ProductStatusesCustomBatchResponse $product_statuses_custom_batch_response
 * @property ShoppingContent\ProductStatusesCustomBatchResponseEntry $product_statuses_custom_batch_response_entry
 * @property ShoppingContent\ProductStatusDestinationStatus $product_status_destination_status
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 * @group MerchantCenterStatuses
 */
class MerchantStatusesTest extends UnitTest {

	private $merchant;
	private $merchant_issue_query;
	private $merchant_center_service;
	private $account_status;
	private $product_meta_query_helper;
	private $merchant_statuses;
	private $product_repository;
	private $product_helper;
	private $product_status;
	private $product_statuses_custom_batch_response;
	private $product_statuses_custom_batch_response_entry;
	private $product_status_destination_status;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant                                     = $this->createMock( Merchant::class );
		$this->merchant_issue_query                         = $this->createMock( MerchantIssueQuery::class );
		$this->merchant_center_service                      = $this->createMock( MerchantCenterService::class );
		$this->account_status                               = $this->createMock( ShoppingContent\AccountStatus::class );
		$this->product_meta_query_helper                    = $this->createMock( ProductMetaQueryHelper::class );
		$this->product_repository                           = $this->createMock( ProductRepository::class );
		$this->product_helper                               = $this->createMock( ProductHelper::class );
		$this->product_status                               = $this->createMock( ShoppingContent\ProductStatus::class );
		$this->product_statuses_custom_batch_response       = $this->createMock( ShoppingContent\ProductstatusesCustomBatchResponse::class );
		$this->product_statuses_custom_batch_response_entry = $this->createMock( ShoppingContent\ProductstatusesCustomBatchResponseEntry::class );
		$this->product_status_destination_status            = $this->createMock( ShoppingContent\ProductStatusDestinationStatus::class );

		$transients           = $this->createMock( TransientsInterface::class );
		$merchant_issue_table = $this->createMock( MerchantIssueTable::class );

		$container = new Container();
		$container->share( Merchant::class, $this->merchant );
		$container->share( MerchantIssueQuery::class, $this->merchant_issue_query );
		$container->share( MerchantCenterService::class, $this->merchant_center_service );
		$container->share( TransientsInterface::class, $transients );
		$container->share( ProductRepository::class, $this->product_repository );
		$container->share( ProductMetaQueryHelper::class, $this->product_meta_query_helper );
		$container->share( ProductHelper::class, $this->product_helper );
		$container->share( MerchantIssueTable::class, $merchant_issue_table );

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
	 * Test get product statistics.
	 *
	 * Test data:
	 * - Product ID: 100, Type: Variable,  Parent: 0,   MC Status: N/A
	 * - Product ID: 101, Type: Simple,    Parent: 0,   MC Status: disapproved
	 * - Product ID: 102, Type: Variation, Parent: 100, MC Status: approved
	 * - Product ID: 103, Type: Variable,  Parent: 0,   MC Status: N/A
	 * - Product ID: 104, Type: Variation, Parent: 103, MC Status: disapproved
	 * - Product ID: 105, Type: Variation, Parent: 100, MC Status: disapproved
	 *
	 * Note that product 102 and 105 have the same parent so they will be grouped as one.
	 * The MC status of 102 is approved while that of 105 is disapproved, Since "disapproved"
	 * has higher priority it'd be marked as disapproved.
	 */
	public function test_get_product_statistics() {
		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->account_status->expects( $this->any() )
			->method( 'getAccountLevelIssues' )
			->willReturn( [] );

		$this->merchant->expects( $this->any() )
			->method( 'get_accountstatus' )
			->willReturn( $this->account_status );

		$this->product_repository->expects( $this->once() )
			->method( 'find_synced_product_ids' )
			->willReturn( [ 101, 102, 104, 105 ] );

		$this->product_meta_query_helper->expects( $this->exactly( 3 ) )
			->method( 'get_all_values' )
			->willReturnOnConsecutiveCalls(
				[
					101 => [ 'TW' => 'online:en:TW:gla_101' ],
					102 => [ 'TW' => 'online:en:TW:gla_102' ],
					104 => [ 'TW' => 'online:en:TW:gla_104' ],
					105 => [ 'TW' => 'online:en:TW:gla_105' ],
				],
				[],
				[]
			);

		$this->product_helper->expects( $this->exactly( 12 ) )
			->method( 'get_wc_product_id' )
			->willReturnOnConsecutiveCalls(
				101,
				102,
				104,
				105,
				101,
				102,
				104,
				105,
				101,
				102,
				104,
				105,
			);

		$this->product_helper->expects( $this->exactly( 4 ) )
			->method( 'get_wc_product_by_wp_post' )
			->willReturnOnConsecutiveCalls(
				new WP_Post(
					(object) [
						'ID'          => 101,
						'post_type'   => 'product',
						'post_parent' => 0,
					]
				),
				new WP_Post(
					(object) [
						'ID'          => 102,
						'post_type'   => 'product',
						'post_parent' => 100,
					]
				),
				new WP_Post(
					(object) [
						'ID'          => 104,
						'post_type'   => 'product',
						'post_parent' => 103,
					]
				),
				new WP_Post(
					(object) [
						'ID'          => 105,
						'post_type'   => 'product',
						'post_parent' => 100,
					]
				),
			);

		$this->product_status_destination_status->expects( $this->exactly( 4 ) )
			->method( 'getStatus' )
			->willReturnOnConsecutiveCalls(
				'disapproved', // 101
				'approved',    // 102
				'disapproved', // 104
				'disapproved', // 105
			);

		$this->product_status_destination_status->expects( $this->exactly( 4 ) )
			->method( 'getDestination' )
			->willReturn( 'SurfacesAcrossGoogle' );

		$this->product_status->expects( $this->exactly( 4 ) )
			->method( 'getDestinationStatuses' )
			->willReturnOnConsecutiveCalls(
				[ $this->product_status_destination_status ],
				[ $this->product_status_destination_status ],
				[ $this->product_status_destination_status ],
				[ $this->product_status_destination_status ],
			);

		$this->product_status->expects( $this->exactly( 12 ) )
			->method( 'getProductId' )
			->willReturnOnConsecutiveCalls(
				'gla_101',
				'gla_102',
				'gla_104',
				'gla_105',
				'gla_101',
				'gla_102',
				'gla_104',
				'gla_105',
				'gla_101',
				'gla_102',
				'gla_104',
				'gla_105',
			);

		$this->product_status->expects( $this->any() )
			->method( 'getItemLevelIssues' )
			->willReturn( [] );

		$this->product_statuses_custom_batch_response_entry->expects( $this->any() )
			->method( 'getProductStatus' )
			->willReturn( $this->product_status );

		$this->product_statuses_custom_batch_response->expects( $this->once() )
			->method( 'getEntries' )
			->willReturn(
				[
					$this->product_statuses_custom_batch_response_entry,
					$this->product_statuses_custom_batch_response_entry,
					$this->product_statuses_custom_batch_response_entry,
					$this->product_statuses_custom_batch_response_entry,
				]
			);

		$this->merchant->expects( $this->once() )
			->method( 'get_productstatuses_batch' )
			->willReturn( $this->product_statuses_custom_batch_response );

		$product_statistics = $this->merchant_statuses->get_product_statistics( true );

		$this->assertEquals(
			[
				'active'      => 0,
				'expiring'    => 0,
				'pending'     => 0,
				'disapproved' => 3,
				'not_synced'  => 0,
			],
			$product_statistics['statistics']
		);
	}
}
