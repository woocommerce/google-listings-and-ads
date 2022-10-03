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
use Google\Service\ShoppingContent;

defined( 'ABSPATH' ) || exit;

/**
 * @property Merchant                      $merchant
 * @property MerchantIssueQuery            $merchant_issue_query
 * @property MerchantCenterService         $merchant_center_service
 * @property ShoppingContent\AccountStatus $account_status
 * @property ProductMetaQueryHelper        $product_meta_query_helper
 * @property MerchantStatuses              $merchant_statuses
 * @property ProductHelper                 $product_helper
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
	private $product_helper;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant                  = $this->createMock( Merchant::class );
		$this->merchant_issue_query      = $this->createMock( MerchantIssueQuery::class );
		$this->merchant_center_service   = $this->createMock( MerchantCenterService::class );
		$this->account_status            = $this->createMock( ShoppingContent\AccountStatus::class );
		$this->product_meta_query_helper = $this->createMock( ProductMetaQueryHelper::class );
		$this->product_helper            = $this->createMock( ProductHelper::class );

		$product_repository   = $this->createMock( ProductRepository::class );
		$transients           = $this->createMock( TransientsInterface::class );
		$merchant_issue_table = $this->createMock( MerchantIssueTable::class );

		$container = new Container();
		$container->share( Merchant::class, $this->merchant );
		$container->share( MerchantIssueQuery::class, $this->merchant_issue_query );
		$container->share( MerchantCenterService::class, $this->merchant_center_service );
		$container->share( TransientsInterface::class, $transients );
		$container->share( ProductRepository::class, $product_repository );
		$container->share( ProductMetaQueryHelper::class, $this->product_meta_query_helper );
		$container->share( MerchantIssueTable::class, $merchant_issue_table );
		$container->share( ProductHelper::class, $this->product_helper );

		$this->merchant_statuses = new MerchantStatuses();
		$this->merchant_statuses->set_container( $container );
	}

	public function test_refresh_account_issues() {
		$this->product_meta_query_helper->expects( $this->any() )->method( 'get_all_values' )->willReturn( [] );
		$this->product_helper->expects( $this->once() )->method( 'maybe_swap_for_parent_ids' )->willReturn( [] );

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
}
