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
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductstatusesCustomBatchResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductstatusesCustomBatchResponseEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductStatusItemLevelIssue;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use DateTime;
use DateInterval;
use Exception;
use WC_Helper_Product;
use WC_Product_Variation;
use WC_Product_Variable;

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
	private $options;
	private $container;
	private $initial_mc_statuses;

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
		$this->options                              = $this->createMock( OptionsInterface::class );

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

		$this->container         = $container;
		$this->merchant_statuses = new MerchantStatuses();
		$this->merchant_statuses->set_container( $container );
		$this->merchant_statuses->set_options_object( $this->options );

		$this->initial_mc_statuses = [
			MCStatus::APPROVED           => 0,
			MCStatus::PARTIALLY_APPROVED => 0,
			MCStatus::EXPIRING           => 0,
			MCStatus::DISAPPROVED        => 0,
			MCStatus::NOT_SYNCED         => 0,
			MCStatus::PENDING            => 0,
		];

		add_filter(
			'woocommerce_gla_mc_status_lifetime',
			function () {
				return self::MC_STATUS_LIFETIME;
			}
		);
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
					'error'      => null,
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

	public function test_get_product_statistics_with_transient_and_error() {
		$this->merchant_center_service->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->willReturn(
				[
					'statistics' => [],
					'loading'    => false,
					'error'      => 'My error message.',
				]
			);

		$this->update_merchant_product_statuses_job->expects( $this->never() )
			->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 2 ) )
			->method( 'is_scheduled' );

		$product_statistics = $this->merchant_statuses->get_product_statistics();

		$this->assertEquals(
			'My error message.',
			$product_statistics['error']
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
					'error'      => null,
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

		$matcher = $this->exactly( 2 );
		$this->product_repository->expects( $matcher )->method( 'find_by_ids_as_associative_array' )->willReturnCallback(
			function ( $args ) use ( $matcher, $product_1, $product_2, $product_3, $variable_product, $variation_id_1, $variation_id_2 ) {
				switch ( $matcher->getInvocationCount() ) {
					case 1:
						$this->assertEquals( [ $product_1->get_id(), $product_2->get_id(), $product_3->get_id(),  $variation_id_1, $variation_id_2 ], $args );
						return [
							$product_1->get_id() => $product_1,
							$product_2->get_id() => $product_2,
							$product_3->get_id() => $product_3,
							$variation_id_1      => wc_get_product( $variation_id_1 ) ,
							$variation_id_2      => wc_get_product( $variation_id_2 ),
						];
					case 2:
						$this->assertEquals( [ $variable_product->get_id() ], $args );
						return [ $variable_product->get_id() => $variable_product ];
				}
			}
		);

		$this->options->expects( $this->exactly( 1 ) )
			->method( 'get' )
			->with( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA )
			->willReturn( null );

		$this->options->expects( $this->once() )
			->method( 'update' )->with(
				OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA,
				$this->callback(
					function ( $value ) use ( $variable_product ) {
						$this->assertEquals(
							[

								MCStatus::APPROVED    => 1,
								MCStatus::PARTIALLY_APPROVED => 2,
								MCStatus::EXPIRING    => 1,
								MCStatus::DISAPPROVED => 0,
								MCStatus::NOT_SYNCED  => 0,
								MCStatus::PENDING     => 0,
								'parents'             => [
									$variable_product->get_id() => MCStatus::PARTIALLY_APPROVED,
								],
							],
							$value
						);

						return true;
					}
				)
			);

		$product_statuses = [
			[
				'mc_id'           => $this->get_mc_id( $product_1->get_id() ),
				'product_id'      => $product_1->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
			[
				'mc_id'           => $this->get_mc_id( $product_2->get_id() ),
				'product_id'      => $product_2->get_id(),
				'status'          => MCStatus::PARTIALLY_APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
			[
				'mc_id'           => $this->get_mc_id( $product_3->get_id() ),
				'product_id'      => $product_3->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P1D' ) ), // Expiring tomorrow
			],
			// Variations are grouped by parent id.
			[
				'mc_id'           => $this->get_mc_id( $variation_id_1 ),
				'product_id'      => $variation_id_1,
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
			[
				'mc_id'           => $this->get_mc_id( $variation_id_2 ),
				'product_id'      => $variation_id_2,
				'status'          => MCStatus::PARTIALLY_APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->product_helper->expects( $this->any() )
			->method( 'get_wc_product_id' )
			->willReturnOnConsecutiveCalls(
				$product_1->get_id(),
				$product_2->get_id(),
				$product_3->get_id(),
				$variation_id_1,
				$variation_id_2
			);

		$product_status = $this->get_product_status_item( $product_1->get_id() );
		$response       = new ProductstatusesCustomBatchResponse();
		$entry          = new ProductstatusesCustomBatchResponseEntry();
		$entry->setProductStatus( $product_status );
		$response->setEntries( [ $entry ] );

		$this->merchant->expects( $this->once() )
			->method( 'get_productstatuses_batch' )
			->with( [ $this->get_mc_id( $product_1->get_id() ), $this->get_mc_id( $product_2->get_id() ), $this->get_mc_id( $product_3->get_id() ), $this->get_mc_id( $variation_id_1 ), $this->get_mc_id( $variation_id_2 ) ] )
			->willReturn( $response );

		$this->merchant_issue_query->expects( $this->once() )->method( 'update_or_insert' )->with(
			[
				[
					'product'              => html_entity_decode( $product_1->get_name() ),
					'product_id'           => $product_1->get_id(),
					'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
					'applicable_countries' => json_encode( [ 'ES' ] ),
					'source'               => 'mc',
					'code'                 => $product_status->getItemLevelIssues()[0]->getCode(),
					'issue'                => $product_status->getItemLevelIssues()[0]->getDescription(),
					'action'               => $product_status->getItemLevelIssues()[0]->getDetail(),
					'action_url'           => $product_status->getItemLevelIssues()[0]->getDocumentation(),
					'severity'             => $product_status->getItemLevelIssues()[0]->getServability(),
				],
			]
		);

		$this->merchant_statuses->update_product_stats(
			$product_statuses
		);
	}

	public function test_handle_complete_mc_statuses_fetching() {
		$this->options->expects( $this->once() )
			->method( 'get' )->with( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA )->willReturn(
				[
					MCStatus::APPROVED           => 3,
					MCStatus::PARTIALLY_APPROVED => 1,
					MCStatus::EXPIRING           => 0,
					MCStatus::PENDING            => 0,
					MCStatus::DISAPPROVED        => 1,
					MCStatus::NOT_SYNCED         => 0,
				]
			);

			$this->product_repository->expects( $this->once() )->method( 'find_all_product_ids' )->willReturn( [ 1, 2, 3, 4, 5, 6 ] );

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

	public function test_handle_failed_mc_statuses_fetching() {
		$this->options->expects( $this->once() )
			->method( 'delete' )->with( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA );

			$this->transients->expects( $this->once() )
			->method( 'set' )->with(
				Transients::MC_STATUSES,
				$this->callback(
					function ( $value ) {
						$this->assertEquals(
							[],
							$value['statistics']
						);

						$this->assertEquals(
							'My error message.',
							$value['error']
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

			$this->merchant_statuses->handle_failed_mc_statuses_fetching( 'My error message.' );
	}

	public function test_update_product_with_multiple_variables_and_multiple_batches_with_different_statuses() {
		$variable_product = WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_available_variations( 'objects' );
		$variation_1      = $variations[0];
		$variation_2      = $variations[1];

		$product_statuses_1 = [
			[
				'product_id'      => $variation_1->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$product_statuses_2 = [
			[
				'product_id'      => $variation_2->get_id(),
				'status'          => MCStatus::DISAPPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->assert_find_by_ids_for_variations( $variable_product, $variation_1, $variation_2 );

		$this->assert_update_intermediate_data(
			[
				MCStatus::APPROVED => 1,
				'parents'          => [ $variable_product->get_id() => MCStatus::APPROVED ],
			],
			[
				MCStatus::APPROVED    => 0,
				MCStatus::DISAPPROVED => 1,
				'parents'             => [ $variable_product->get_id() => MCStatus::DISAPPROVED ],
			]
		);

		$response = new ProductstatusesCustomBatchResponse();
		$response->setEntries( [] );

		$this->merchant->expects( $this->exactly( 2 ) )
			->method( 'get_productstatuses_batch' )
			->willReturn( $response );

		$this->merchant_statuses->update_product_stats(
			$product_statuses_1
		);

		$merchant_statuses_2 = new MerchantStatuses();
		$merchant_statuses_2->set_container( $this->container );
		$merchant_statuses_2->set_options_object( $this->options );

		$merchant_statuses_2->update_product_stats(
			$product_statuses_2
		);
	}

	public function test_update_product_with_multiple_variables_and_multiple_batches_and_same_status() {
		$variable_product = WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_available_variations( 'objects' );
		$variation_1      = $variations[0];
		$variation_2      = $variations[1];

		$product_statuses_1 = [
			[
				'product_id'      => $variation_1->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$product_statuses_2 = [
			[
				'product_id'      => $variation_2->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->assert_find_by_ids_for_variations( $variable_product, $variation_1, $variation_2 );

		$this->assert_update_intermediate_data(
			[
				MCStatus::APPROVED => 1,
				'parents'          => [ $variable_product->get_id() => MCStatus::APPROVED ],
			],
			[
				MCStatus::APPROVED => 1,
				'parents'          => [ $variable_product->get_id() => MCStatus::APPROVED ],
			]
		);

		$response = new ProductstatusesCustomBatchResponse();
		$response->setEntries( [] );

		$this->merchant->expects( $this->exactly( 2 ) )
			->method( 'get_productstatuses_batch' )
			->willReturn( $response );

		$this->merchant_statuses->update_product_stats(
			$product_statuses_1
		);

		$merchant_statuses_2 = new MerchantStatuses();
		$merchant_statuses_2->set_container( $this->container );
		$merchant_statuses_2->set_options_object( $this->options );

		$merchant_statuses_2->update_product_stats(
			$product_statuses_2
		);
	}

	public function test_update_product_with_multiple_variables_and_multiple_batches_and_dont_override_previous_state() {
		$variable_product = WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_available_variations( 'objects' );
		$variation_1      = $variations[0];
		$variation_2      = $variations[1];

		$product_statuses_1 = [
			[
				'product_id'      => $variation_1->get_id(),
				'status'          => MCStatus::DISAPPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$product_statuses_2 = [
			[
				'product_id'      => $variation_2->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->assert_find_by_ids_for_variations( $variable_product, $variation_1, $variation_2 );

		$this->assert_update_intermediate_data(
			[
				MCStatus::DISAPPROVED => 1,
				'parents'             => [ $variable_product->get_id() => MCStatus::DISAPPROVED ],
			],
			[
				MCStatus::DISAPPROVED => 1,
				'parents'             => [ $variable_product->get_id() => MCStatus::DISAPPROVED ],
			]
		);

		$response = new ProductstatusesCustomBatchResponse();
		$response->setEntries( [] );

		$this->merchant->expects( $this->exactly( 2 ) )
			->method( 'get_productstatuses_batch' )
			->willReturn( $response );

		$this->merchant_statuses->update_product_stats(
			$product_statuses_1
		);

		$merchant_statuses_2 = new MerchantStatuses();
		$merchant_statuses_2->set_container( $this->container );
		$merchant_statuses_2->set_options_object( $this->options );

		$merchant_statuses_2->update_product_stats(
			$product_statuses_2
		);
	}

	protected function get_product_status_item( $wc_product_id ): ProductStatus {
		$product_status = new ProductStatus();
		$product_status->setProductId( $this->get_mc_id( $wc_product_id ) );

		$issue = new ProductStatusItemLevelIssue();
		$issue->setResolution( 'merchant_action' );
		$issue->setApplicableCountries( [ 'ES' ] );
		$issue->setCode( 'issue_code' );
		$issue->setDescription( 'issue_description' );
		$issue->setDetail( 'issue_detail' );
		$issue->setDocumentation( 'https://example.com' );
		$issue->setServability( 'critical' );

		$product_status->setItemLevelIssues( [ $issue ] );

		return $product_status;
	}

	/**
	 * Get a MC ID for a product.
	 *
	 * @param int $wc_id
	 *
	 * @return string
	 */
	protected function get_mc_id( $wc_id ) {
		return 'online:en:ES:gla_' . $wc_id;
	}

	/**
	 *  Assert that the update_product_stats method updates the product stats in two batches.
	 *
	 * @param array $first_update_results
	 * @param array $second_update_results
	 */
	protected function assert_update_intermediate_data( array $first_update_results, array $second_update_results ) {
		$matcher = $this->exactly( 2 );
		$this->options->expects( $matcher )
			->method( 'get' )
			->willReturnCallback(
				function ( $args ) use ( $matcher, $first_update_results ) {
					switch ( $matcher->getInvocationCount() ) {
						case 1:
							$this->assertEquals( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $args );
							return null;
						case 2:
							$this->assertEquals( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $args );
							return array_merge( $this->initial_mc_statuses, $first_update_results );
					}
				}
			);

		$matcher = $this->exactly( 2 );
		$this->options->expects( $matcher )
			->method( 'update' )
			->willReturnCallback(
				function ( $option, $intermediate_data ) use ( $matcher, $first_update_results, $second_update_results ) {
					$this->assertEquals( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $option );

					switch ( $matcher->getInvocationCount() ) {
						case 1:
							$this->assertEquals(
								array_merge( $this->initial_mc_statuses, $first_update_results ),
								$intermediate_data
							);
							return true;
						case 2:
							$this->assertEquals( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $option );
							$this->assertEquals(
								array_merge( $this->initial_mc_statuses, $second_update_results ),
								$intermediate_data
							);
							return true;
					}
				}
			);
	}

	/**
	 *
	 *  Assert that the find_by_ids_as_associative_array method is called with the expected arguments for variations when updating the product stats in two batches.
	 *  Each batch calls find_by_ids_as_associative_array twice, once for the variation and once for the parent product.
	 *
	 * @param WC_Product_Variable  $variable_product
	 * @param WC_Product_Variation $variation_1
	 * @param WC_Product_Variation $variation_2
	 */
	protected function assert_find_by_ids_for_variations( WC_Product_Variable $variable_product, WC_Product_Variation $variation_1, WC_Product_Variation $variation_2 ) {
		$matcher = $this->exactly( 4 );
		$this->product_repository->expects( $matcher )->method( 'find_by_ids_as_associative_array' )->willReturnCallback(
			function ( $args ) use ( $matcher, $variable_product, $variation_1, $variation_2 ) {
				switch ( $matcher->getInvocationCount() ) {
					case 1:
						$this->assertEquals( [ $variation_1->get_id() ], $args );
						return [
							$variation_1->get_id() => $variation_1 ,
						];
					case 2:
						$this->assertEquals( [ $variable_product->get_id() ], $args );
						return [ $variable_product->get_id() => $variable_product ];
					case 3:
						$this->assertEquals( [ $variation_2->get_id() ], $args );
						return [
							$variation_2->get_id() => $variation_2,
						];
					case 4:
						$this->assertEquals( [ $variable_product->get_id() ], $args );
						return [ $variable_product->get_id() => $variable_product ];
				}
			}
		);
	}
}
