<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountIssuesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Symfony\Component\Validator\Validator\ValidatorInterface;
use DateTime;
use DateInterval;
use Exception;
use Throwable;
use WC_Helper_Product;
use WC_Product;
use WC_Product_Variation;
use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

/**
 * @property Merchant $merchant
 * @property MerchantIssueQuery $merchant_issue_query
 * @property MerchantCenterService $merchant_center_service
 * @property ProductMetaQueryHelper $product_meta_query_helper
 * @property MerchantStatuses $merchant_statuses
 * @property ProductRepository $product_repository
 * @property ProductHelper $product_helper
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 * @group MerchantCenterStatuses
 */
class MerchantStatusesTest extends UnitTest {

	use PluginHelper;

	/**
	 * Lifetime of the MC Status transient.
	 */
	protected const MC_STATUS_LIFETIME = 60;

	private $merchant;
	private $mapi_account_issues_service;
	private $merchant_issue_query;
	private $merchant_center_service;
	private $product_meta_query_helper;
	private $merchant_statuses;
	private $product_repository;
	private $product_helper;
	private $transients;
	private $update_merchant_product_statuses_job;
	private $update_all_product_job;
	private $delete_all_product_job;
	private $options;
	private $container;
	private $initial_mc_statuses;
	private $merchant_issue_table;
	private $target_audience;

	/** @var JobRepository $job_repository */
	protected $job_repository;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->merchant                             = $this->createMock( Merchant::class );
		$this->mapi_account_issues_service          = $this->createMock( MapiAccountIssuesService::class );
		$this->merchant_issue_query                 = $this->createMock( MerchantIssueQuery::class );
		$this->merchant_center_service              = $this->createMock( MerchantCenterService::class );
		$this->product_meta_query_helper            = $this->createMock( ProductMetaQueryHelper::class );
		$this->product_repository                   = $this->createMock( ProductRepository::class );
		$this->product_helper                       = $this->createMock( ProductHelper::class );
		$this->transients                           = $this->createMock( TransientsInterface::class );
		$this->update_merchant_product_statuses_job = $this->createMock( UpdateMerchantProductStatuses::class );
		$this->update_all_product_job               = $this->createMock( UpdateAllProducts::class );
		$this->delete_all_product_job               = $this->createMock( DeleteAllProducts::class );
		$this->options                              = $this->createMock( OptionsInterface::class );

		$this->merchant_issue_table = $this->createMock( MerchantIssueTable::class );
		$this->target_audience      = $this->createMock( TargetAudience::class );

		$this->container = new Container();
		$this->container->addShared( Merchant::class, $this->merchant );
		$this->container->addShared( MapiAccountIssuesService::class, $this->mapi_account_issues_service );
		$this->container->addShared( MerchantIssueQuery::class, $this->merchant_issue_query );
		$this->container->addShared( MerchantCenterService::class, $this->merchant_center_service );
		$this->container->addShared( TransientsInterface::class, $this->transients );
		$this->container->addShared( ProductRepository::class, $this->product_repository );
		$this->container->addShared( ProductMetaQueryHelper::class, $this->product_meta_query_helper );
		$this->container->addShared( ProductHelper::class, $this->product_helper );
		$this->container->addShared( MerchantIssueTable::class, $this->merchant_issue_table );
		$this->container->addShared( TargetAudience::class, $this->target_audience );
		$this->container->addShared( UpdateMerchantProductStatuses::class, $this->update_merchant_product_statuses_job );
		$this->container->addShared( UpdateAllProducts::class, $this->update_all_product_job );
		$this->container->addShared( DeleteAllProducts::class, $this->delete_all_product_job );
		$this->container->addShared(
			BatchProductHelper::class,
			new BatchProductHelper(
				$this->createMock( ProductMetaHandler::class ),
				$this->product_helper,
				$this->createMock( ValidatorInterface::class ),
				$this->createMock( ProductFactory::class ),
				$this->createMock( AttributeMappingRulesQuery::class ),
				$this->createMock( MarketService::class ),
				$this->createMock( WPML::class ),
				$this->createMock( AttributeManager::class ),
				$this->createMock( MapiDataSourcesService::class )
			)
		);

		$this->job_repository = new JobRepository();
		$this->job_repository->set_container( $this->container );
		$this->container->addShared( JobRepository::class, $this->job_repository );

		$this->merchant_statuses = new MerchantStatuses();
		$this->merchant_statuses->set_container( $this->container );
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

		$this->mapi_account_issues_service->expects( $this->once() )
		->method( 'get_account_issues' )
		->willReturn(
			[
				[
					'name'                 => 'accounts/12345/issues/id',
					'title'                => 'title',
					'detail'               => 'detail',
					'documentationUri'     => 'https://example.com',
					'severity'             => 'CRITICAL',
					'impactedDestinations' => [
						[ 'impacts' => [ [ 'regionCode' => 'US' ] ] ],
					],
				],
				[
					'name'                 => 'accounts/12345/issues/id2',
					'title'                => 'title2',
					'detail'               => 'detail2',
					'documentationUri'     => 'https://example.com/2',
					'severity'             => 'ERROR',
					'impactedDestinations' => [
						[ 'impacts' => [ [ 'regionCode' => 'CA' ], [ 'regionCode' => 'US' ] ] ],
					],
				],
				[
					'name'                 => 'accounts/12345/issues/id3',
					'title'                => 'title3',
					'detail'               => 'detail3',
					'documentationUri'     => 'https://example.com/3',
					'severity'             => 'SEVERITY_UNSPECIFIED',
					'impactedDestinations' => [
						[ 'impacts' => [ [ 'regionCode' => 'GB' ] ] ],
					],
				],
			]
		);

		$this->merchant_center_service->expects( $this->any() )
		->method( 'is_connected' )
		->willReturn( true );

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
			md5( 'title3' ) => [
				'product_id'           => 0,
				'product'              => 'All products',
				'code'                 => 'id3',
				'issue'                => 'title3',
				'action'               => 'detail3',
				'action_url'           => 'https://example.com/3',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
				'type'                 => 'account',
				'severity'             => 'error',
				'source'               => 'mc',
				'applicable_countries' => '["GB"]',
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
		->method( 'update_or_insert' )
		->withConsecutive( [ $issues ], [] );
		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	public function test_refresh_account_issues_overrides_home_page_issue() {
		$this->product_meta_query_helper->expects( $this->any() )->method( 'get_all_values' )->willReturn( [] );

		$this->mapi_account_issues_service->expects( $this->once() )
		->method( 'get_account_issues' )
		->willReturn(
			[
				[
					'name'                 => 'accounts/12345/issues/home-page-issue',
					'title'                => 'Some original Google title',
					'detail'               => 'detail',
					'documentationUri'     => 'https://example.com',
					'severity'             => 'ERROR',
					'impactedDestinations' => [
						[ 'impacts' => [ [ 'regionCode' => 'US' ] ] ],
					],
				],
			]
		);

		$this->merchant_center_service->expects( $this->any() )
		->method( 'is_connected' )
		->willReturn( true );

		$expected = [
			md5( 'Some original Google title' ) => [
				'product_id'           => 0,
				'product'              => 'All products',
				'code'                 => 'home_page_issue',
				'issue'                => 'Website claim is lost, need to re verify and claim your website. Please reference the support link',
				'action'               => 'detail',
				'action_url'           => 'https://woocommerce.com/document/google-for-woocommerce/faq/#reverify-website',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
				'type'                 => 'account',
				'severity'             => 'error',
				'source'               => 'mc',
				'applicable_countries' => '["US"]',
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
		->method( 'update_or_insert' )
		->withConsecutive( [ $expected ], [] );
		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	/**
	 * A variable parent can hold its own string errors alongside the nested
	 * variation errors that ProductHelper::mark_as_invalid() appends to it.
	 * Only the string errors should be stored, and the nested arrays must not
	 * reach parse_presync_issue_text().
	 *
	 * @see https://github.com/woocommerce/google-listings-and-ads/issues/3593
	 */
	public function test_refresh_presync_issues_skips_nested_variation_errors() {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );

		$this->product_meta_query_helper->expects( $this->any() )
		->method( 'get_all_values' )
		->willReturn(
			[
				$parent->get_id() => [
					'[title] Missing value.',
					$variation->get_id() => [ '[price] Invalid price.' ],
				],
			]
		);

		$this->mapi_account_issues_service->expects( $this->once() )
		->method( 'get_account_issues' )
		->willReturn( [] );

		$expected = [
			[
				'product'              => $parent->get_name(),
				'product_id'           => $parent->get_id(),
				'code'                 => 'presync_error_title',
				'severity'             => 'error',
				'issue'                => 'Missing value [title]',
				'action'               => 'Update this attribute in your product data',
				'action_url'           => 'https://support.google.com/merchants/answer/10538362?hl=en&ref_topic=6098333',
				'applicable_countries' => '["all"]',
				'source'               => 'pre-sync',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
		->method( 'update_or_insert' )
		->withConsecutive( [ [] ], [ $expected ] );
		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	public function test_refresh_presync_product_issues_overrides_channel_mismatch_message() {
		// The default presync 'dataSource' action ("Update this attribute in your product data")
		// is wrong here — there's no such attribute — so it must be overridden.
		$product = WC_Helper_Product::create_simple_product();

		$this->mapi_account_issues_service->method( 'get_account_issues' )->willReturn( [] );
		$this->product_meta_query_helper->expects( $this->once() )
			->method( 'get_all_values' )
			->with( ProductMetaHandler::KEY_ERRORS )
			->willReturn(
				[
					$product->get_id() => [ '[dataSource] The provided data source channel does not match product channel.' ],
				]
			);
		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$expected = [
			[
				'product'              => $product->get_name(),
				'product_id'           => $product->get_id(),
				'code'                 => 'presync_error_dataSource',
				'severity'             => 'error',
				'issue'                => 'The provided data source channel does not match product channel [dataSource]',
				'action'               => "This data source isn't set up to sync online products; check your Merchant Center data sources",
				'action_url'           => 'https://support.google.com/merchants/answer/13982673',
				'applicable_countries' => '["all"]',
				'source'               => 'pre-sync',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
			->method( 'update_or_insert' )
			->withConsecutive( [ [] ], [ $expected ] );

		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	public function test_refresh_presync_product_issues_overrides_file_input_message() {
		$product = WC_Helper_Product::create_simple_product();

		$this->mapi_account_issues_service->method( 'get_account_issues' )->willReturn( [] );
		$this->product_meta_query_helper->expects( $this->once() )
			->method( 'get_all_values' )
			->willReturn(
				[
					$product->get_id() => [ "[dataSource] To manage items via the API, the data source must have an API input type. API data sources cannot have a 'fileInput' field set." ],
				]
			);
		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$expected = [
			[
				'product'              => $product->get_name(),
				'product_id'           => $product->get_id(),
				'code'                 => 'presync_error_dataSource',
				'severity'             => 'error',
				'issue'                => "To manage items via the API, the data source must have an API input type. API data sources cannot have a 'fileInput' field set [dataSource]",
				'action'               => "This data source isn't set up to accept products from Google for WooCommerce; check your Merchant Center data sources",
				'action_url'           => 'https://support.google.com/merchants/answer/13982673',
				'applicable_countries' => '["all"]',
				'source'               => 'pre-sync',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
			->method( 'update_or_insert' )
			->withConsecutive( [ [] ], [ $expected ] );

		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	public function test_refresh_presync_product_issues_overrides_backtick_quoted_file_input_message() {
		// The reported message quotes the field inconsistently — some accounts show
		// 'fileInput' and others `fileInput`. Matching the surrounding sentence with one quoting
		// style left the other cohort with the misleading default action, which is the very
		// problem this override exists to fix, so both forms must be recognised.
		$product = WC_Helper_Product::create_simple_product();

		$this->mapi_account_issues_service->method( 'get_account_issues' )->willReturn( [] );
		$this->product_meta_query_helper->expects( $this->once() )
			->method( 'get_all_values' )
			->willReturn(
				[
					$product->get_id() => [ '[dataSource] To manage items via the API, the data source must have an API input type. API data sources cannot have a `fileInput` field set.' ],
				]
			);
		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$expected = [
			[
				'product'              => $product->get_name(),
				'product_id'           => $product->get_id(),
				'code'                 => 'presync_error_dataSource',
				'severity'             => 'error',
				'issue'                => 'To manage items via the API, the data source must have an API input type. API data sources cannot have a `fileInput` field set [dataSource]',
				'action'               => "This data source isn't set up to accept products from Google for WooCommerce; check your Merchant Center data sources",
				'action_url'           => 'https://support.google.com/merchants/answer/13982673',
				'applicable_countries' => '["all"]',
				'source'               => 'pre-sync',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
			->method( 'update_or_insert' )
			->withConsecutive( [ [] ], [ $expected ] );

		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	public function test_refresh_presync_product_issues_leaves_unrelated_attribute_errors_unchanged() {
		// Regression check: the override must not affect a genuine product-attribute error.
		$product = WC_Helper_Product::create_simple_product();

		$this->mapi_account_issues_service->method( 'get_account_issues' )->willReturn( [] );
		$this->product_meta_query_helper->expects( $this->once() )
			->method( 'get_all_values' )
			->willReturn(
				[
					$product->get_id() => [ '[title] is missing.' ],
				]
			);
		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$expected = [
			[
				'product'              => $product->get_name(),
				'product_id'           => $product->get_id(),
				'code'                 => 'presync_error_title',
				'severity'             => 'error',
				'issue'                => 'is missing [title]',
				'action'               => 'Update this attribute in your product data',
				'action_url'           => 'https://support.google.com/merchants/answer/10538362?hl=en&ref_topic=6098333',
				'applicable_countries' => '["all"]',
				'source'               => 'pre-sync',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
			->method( 'update_or_insert' )
			->withConsecutive( [ [] ], [ $expected ] );

		$this->merchant_statuses->refresh_account_and_presync_issues();
	}

	public function test_refresh_presync_product_issues_leaves_an_unrecognized_data_source_message_unchanged() {
		// A 'dataSource'-coded issue whose text matches NEITHER known override substring (e.g. a
		// third data-source failure mode not yet seen in production) must fall through to the
		// generic default unchanged, rather than a too-broad substring check accidentally matching it.
		$product = WC_Helper_Product::create_simple_product();

		$this->mapi_account_issues_service->method( 'get_account_issues' )->willReturn( [] );
		$this->product_meta_query_helper->expects( $this->once() )
			->method( 'get_all_values' )
			->willReturn(
				[
					$product->get_id() => [ '[dataSource] The data source is temporarily unavailable.' ],
				]
			);
		$this->merchant_center_service->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );

		$expected = [
			[
				'product'              => $product->get_name(),
				'product_id'           => $product->get_id(),
				'code'                 => 'presync_error_dataSource',
				'severity'             => 'error',
				'issue'                => 'The data source is temporarily unavailable [dataSource]',
				'action'               => 'Update this attribute in your product data',
				'action_url'           => 'https://support.google.com/merchants/answer/10538362?hl=en&ref_topic=6098333',
				'applicable_countries' => '["all"]',
				'source'               => 'pre-sync',
				'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
			],
		];

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )
			->method( 'update_or_insert' )
			->withConsecutive( [ [] ], [ $expected ] );

		$this->merchant_statuses->refresh_account_and_presync_issues();
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

		$this->transients->expects( $this->exactly( 2 ) )
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

		$this->transients->expects( $this->exactly( 2 ) )
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

		$this->transients->expects( $this->exactly( 2 ) )
		->method( 'get' )
		->willReturn(
			null
		);

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 1 ) )
		->method( 'schedule' );

		$this->update_all_product_job->expects( $this->exactly( 1 ) )
		->method( 'can_schedule' )
		->with( null )
		->willReturn( true );

		$this->delete_all_product_job->expects( $this->exactly( 1 ) )
		->method( 'can_schedule' )
		->with( null )
		->willReturn( true );

		$this->transients->expects( $this->exactly( 1 ) )->method( 'delete' )->with( Transients::MC_STATUSES );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 2 ) )
		->method( 'is_scheduled' );

		$product_statistics = $this->merchant_statuses->get_product_statistics();

		$this->assertEquals(
			null,
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

		$this->transients->expects( $this->exactly( 2 ) )
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
			null,
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

		$this->transients->expects( $this->exactly( 2 ) )
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
			null,
			$product_statistics['statistics']
		);

		$this->assertEquals(
			true,
			$product_statistics['loading']
		);
	}

	public function test_get_product_statistics_with_failure_rate() {
		$this->merchant_center_service->expects( $this->once() )
		->method( 'is_connected' )
		->willReturn( true );

		$this->transients->expects( $this->exactly( 2 ) )
		->method( 'get' )
		->willReturn(
			null
		);

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 1 ) )
		->method( 'get_failure_rate_message' )->willReturn( 'The scheduled job has been paused due to a high failure rate.' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 1 ) )
		->method( 'schedule' );

		$this->update_merchant_product_statuses_job->expects( $this->exactly( 1 ) )
		->method( 'is_scheduled' )->willReturn( false );

		$product_statistics = $this->merchant_statuses->get_product_statistics();

		$this->assertEquals(
			null,
			$product_statistics['statistics']
		);

		$this->assertEquals(
			false,
			$product_statistics['loading']
		);

		$this->assertEquals(
			'The scheduled job has been paused due to a high failure rate.',
			$product_statistics['error']
		);
	}

	public function test_process_product_statuses() {
		$product_1        = WC_Helper_Product::create_simple_product();
		$product_2        = WC_Helper_Product::create_simple_product();
		$product_3        = WC_Helper_Product::create_simple_product();
		$product_4        = WC_Helper_Product::create_simple_product();
		$variable_product = WC_Helper_Product::create_variation_product();

		$variations     = $variable_product->get_available_variations();
		$variation_id_1 = $variations[0]['variation_id'];
		$variation_id_2 = $variations[1]['variation_id'];

		// Products set to DONT_SYNC_AND_SHOW should be skipped (their issues are not fetched).
		$product_4->update_meta_data( $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ), ChannelVisibility::DONT_SYNC_AND_SHOW );
		$product_4->save_meta_data();

		$matcher = $this->exactly( 2 );
		$this->product_repository->expects( $matcher )->method( 'find_by_ids_as_associative_array' )->willReturnCallback(
			function ( $args ) use ( $matcher, $product_1, $product_2, $product_3, $variable_product, $variation_id_1, $variation_id_2, $product_4 ) {
				switch ( $matcher->getInvocationCount() ) {
					case 1:
						$this->assertEquals( [ $product_1->get_id(), $product_2->get_id(), $product_3->get_id(),  $variation_id_1, $variation_id_2, $product_4->get_id() ], $args );
						return [
							$product_1->get_id() => $product_1,
							$product_2->get_id() => $product_2,
							$product_3->get_id() => $product_3,
							$variation_id_1      => wc_get_product( $variation_id_1 ) ,
							$variation_id_2      => wc_get_product( $variation_id_2 ),
							$product_4->get_id() => $product_4,
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

							MCStatus::APPROVED           => 1,
							MCStatus::PARTIALLY_APPROVED => 3,
							MCStatus::EXPIRING           => 1,
							MCStatus::DISAPPROVED        => 0,
							MCStatus::NOT_SYNCED         => 0,
							MCStatus::PENDING            => 0,
							'parents'                    => [
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
			[
				'mc_id'           => $this->get_mc_id( $product_4->get_id() ),
				'product_id'      => $product_4->get_id(),
				'status'          => MCStatus::PARTIALLY_APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->product_helper->method( 'get_synced_google_product_ids' )
		->willReturnCallback(
			function ( $product ) {
				return [ 'ES' => $this->get_mapi_id( $product->get_id() ) ];
			}
		);

		// product_4 (DONT_SYNC_AND_SHOW) is provided but must be excluded from the issues;
		// product_2's issue is pending_processing and must be filtered out as well.
		$products_by_google_id = [
			$this->get_mapi_id( $product_1->get_id() ) => $this->get_mapi_product( $product_1->get_id() ),
			$this->get_mapi_id( $product_2->get_id() ) => $this->get_mapi_product( $product_2->get_id(), 'pending_processing' ),
			$this->get_mapi_id( $product_4->get_id() ) => $this->get_mapi_product( $product_4->get_id() ),
		];

		// No rows from earlier pages of the refresh, so none are deleted.
		$this->merchant_issue_query->method( 'get_results' )->willReturn( [] );
		$this->merchant_issue_table->expects( $this->never() )->method( 'delete_by_ids' );

		$this->merchant_issue_query->expects( $this->once() )->method( 'update_or_insert' )->with(
			[
				[
					'product'              => html_entity_decode( $product_1->get_name(), ENT_QUOTES ),
					'product_id'           => $product_1->get_id(),
					'created_at'           => $this->merchant_statuses->get_cache_created_time()->format( 'Y-m-d H:i:s' ),
					'applicable_countries' => wp_json_encode( [ 'ES' ] ),
					'source'               => 'mc',
					'code'                 => 'issue_code',
					'issue'                => 'issue_description',
					'action'               => 'issue_detail',
					'action_url'           => 'https://example.com',
					'severity'             => 'DISAPPROVED',
				],
			]
		);

		$this->merchant_statuses->process_product_statuses(
			$product_statuses,
			$products_by_google_id
		);
	}

	public function test_process_mapi_products_tolerates_a_malformed_expiration_date() {
		$statuses_spy = $this->createPartialMock( MerchantStatuses::class, [ 'process_product_statuses' ] );
		$statuses_spy->set_container( $this->container );

		$this->product_helper->method( 'get_wc_product_id' )->willReturn( 11 );

		$product = Product::from_array(
			[
				'name'          => 'accounts/123/products/en~ES~gla_11',
				'productStatus' => [
					'destinationStatuses'  => [
						[
							'reportingContext'  => 'SHOPPING_ADS',
							'approvedCountries' => [ 'ES' ],
						],
					],
					'googleExpirationDate' => 'not-a-timestamp',
				],
			]
		);

		$statuses_spy->expects( $this->once() )
		->method( 'process_product_statuses' )
		->with(
			$this->callback(
				function ( array $statuses ) {
					return null === $statuses[11]['expiration_date'] && MCStatus::APPROVED === $statuses[11]['status'];
				}
			),
			[ 'en~ES~gla_11' => $product ]
		);

		$statuses_spy->process_mapi_products( [ $product ] );
	}

	public function test_process_mapi_products_converts_and_skips() {
		$statuses_spy = $this->createPartialMock( MerchantStatuses::class, [ 'process_product_statuses' ] );
		$statuses_spy->set_container( $this->container );

		$this->product_helper->method( 'get_wc_product_id' )
		->willReturnCallback(
			function ( string $google_id ) {
				// The foreign product carries no gla_ offer id and resolves to no WC product.
				return 'en~ES~foreign_offer' === $google_id ? 0 : (int) substr( $google_id, strrpos( $google_id, '_' ) + 1 );
			}
		);

		$approved    = Product::from_array(
			[
				'name'          => 'accounts/123/products/en~ES~gla_11',
				'productStatus' => [
					'destinationStatuses'  => [
						[
							'reportingContext'  => 'SHOPPING_ADS',
							'approvedCountries' => [ 'ES' ],
						],
					],
					'googleExpirationDate' => '2030-01-01T00:00:00Z',
				],
			]
		);
		$disapproved = Product::from_array(
			[
				'name'          => 'accounts/123/products/en~ES~gla_12',
				'productStatus' => [
					'destinationStatuses' => [
						[
							'reportingContext'     => 'SHOPPING_ADS',
							'disapprovedCountries' => [ 'ES' ],
						],
					],
				],
			]
		);
		$processing  = Product::from_array( [ 'name' => 'accounts/123/products/en~ES~gla_13' ] );
		$foreign     = Product::from_array(
			[
				'name'          => 'accounts/123/products/en~ES~foreign_offer',
				'productStatus' => [
					'destinationStatuses' => [
						[
							'reportingContext'  => 'SHOPPING_ADS',
							'approvedCountries' => [ 'ES' ],
						],
					],
				],
			]
		);

		$statuses_spy->expects( $this->once() )
		->method( 'process_product_statuses' )
		->with(
			[
				11 => [
					'mc_id'           => 'en~ES~gla_11',
					'product_id'      => 11,
					'status'          => MCStatus::APPROVED,
					'expiration_date' => new DateTime( '2030-01-01T00:00:00Z' ),
				],
				12 => [
					'mc_id'           => 'en~ES~gla_12',
					'product_id'      => 12,
					'status'          => MCStatus::DISAPPROVED,
					'expiration_date' => null,
				],
			],
			[
				'en~ES~gla_11' => $approved,
				'en~ES~gla_12' => $disapproved,
			]
		);

		$statuses_spy->process_mapi_products( [ $approved, $disapproved, $processing, $foreign ] );
	}

	/**
	 * @dataProvider offer_order_provider
	 *
	 * @param string[] $order Offer countries in processing order.
	 */
	public function test_process_mapi_products_records_a_product_disapproved_in_any_market( array $order ) {
		$product = WC_Helper_Product::create_simple_product();
		$stored  = $this->use_product_status_fakes( [ $product ] );

		$offers = [
			'US' => $this->get_mapi_offer( $product->get_id(), 'US', 'disapprovedCountries' ),
			'GB' => $this->get_mapi_offer( $product->get_id(), 'GB', 'approvedCountries' ),
		];

		$this->merchant_statuses->process_mapi_products( [ $offers[ $order[0] ], $offers[ $order[1] ] ] );
		$this->merchant_statuses->record_feed_issue_page( null, null );

		$this->assertSame( [ $product->get_id() => true ], $stored( OptionsInterface::FEED_ISSUE_TRACKING )['product_ids'] );
		$this->assertSame( array_keys( MerchantStatuses::DEFAULT_PRODUCT_STATS ), array_keys( $stored() ) );
	}

	public function offer_order_provider(): array {
		return [
			'disapproved offer first' => [ [ 'US', 'GB' ] ],
			'disapproved offer last'  => [ [ 'GB', 'US' ] ],
		];
	}

	/**
	 * @dataProvider offer_order_provider
	 *
	 * @param string[] $order Offer countries in page order.
	 */
	public function test_process_mapi_products_keeps_a_disapproved_product_across_pages( array $order ) {
		$product = WC_Helper_Product::create_simple_product();
		$stored  = $this->use_product_status_fakes( [ $product ] );

		$offers = [
			'US' => $this->get_mapi_offer( $product->get_id(), 'US', 'disapprovedCountries' ),
			'GB' => $this->get_mapi_offer( $product->get_id(), 'GB', 'approvedCountries' ),
		];

		$this->merchant_statuses->process_mapi_products( [ $offers[ $order[0] ] ] );
		$this->merchant_statuses->record_feed_issue_page( null, '0' );
		$this->merchant_statuses->process_mapi_products( [ $offers[ $order[1] ] ] );
		$this->merchant_statuses->record_feed_issue_page( '0', null );

		$this->assertSame( [ $product->get_id() => true ], $stored( OptionsInterface::FEED_ISSUE_TRACKING )['product_ids'] );
		$this->assertSame( array_keys( MerchantStatuses::DEFAULT_PRODUCT_STATS ), array_keys( $stored() ) );
	}

	public function test_process_mapi_products_records_a_disapproved_variation_as_its_parent() {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();
		$variation  = wc_get_product( $variations[0] );
		$sibling    = wc_get_product( $variations[1] );
		$stored     = $this->use_product_status_fakes( [ $variable, $variation, $sibling ] );

		$this->merchant_statuses->process_mapi_products(
			[
				$this->get_mapi_offer( $variation->get_id(), 'US', 'disapprovedCountries' ),
				$this->get_mapi_offer( $sibling->get_id(), 'US', 'disapprovedCountries' ),
			]
		);

		$this->merchant_statuses->record_feed_issue_page( null, null );
		$this->assertSame( [ $variable->get_id() => true ], $stored( OptionsInterface::FEED_ISSUE_TRACKING )['product_ids'] );
	}

	public function test_process_mapi_products_records_nothing_without_disapproved_offers() {
		$product = WC_Helper_Product::create_simple_product();
		$stored  = $this->use_product_status_fakes( [ $product ] );

		$this->merchant_statuses->process_mapi_products( [ $this->get_mapi_offer( $product->get_id(), 'US', 'approvedCountries' ) ] );

		$this->merchant_statuses->record_feed_issue_page( null, null );
		$this->assertSame( [], $stored( OptionsInterface::FEED_ISSUE_TRACKING )['product_ids'] );
		$this->assertSame( array_keys( MerchantStatuses::DEFAULT_PRODUCT_STATS ), array_keys( $stored() ) );
	}

	public function test_process_mapi_products_records_a_product_approved_in_any_market_as_its_top_level_product() {
		$simple    = WC_Helper_Product::create_simple_product();
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$pending   = WC_Helper_Product::create_simple_product();
		$stored    = $this->use_product_status_fakes( [ $simple, $variable, $variation, $pending ] );

		$this->merchant_statuses->process_mapi_products(
			[
				$this->get_mapi_offer( $simple->get_id(), 'US', 'disapprovedCountries' ),
				$this->get_mapi_offer( $simple->get_id(), 'GB', 'approvedCountries' ),
				$this->get_mapi_offer( $variation->get_id(), 'US', 'approvedCountries' ),
				$this->get_mapi_offer( $pending->get_id(), 'US', 'pendingCountries' ),
			]
		);
		$this->merchant_statuses->record_feed_issue_page( null, null );

		$tracking = $stored( OptionsInterface::FEED_ISSUE_TRACKING );
		$this->assertSame(
			[
				$simple->get_id()   => true,
				$variable->get_id() => true,
			],
			$tracking['approved_product_ids']
		);
		$this->assertSame( [ $simple->get_id() => true ], $tracking['product_ids'] );
	}

	public function test_process_mapi_products_tracks_approval_from_shopping_ads_and_free_listings_only() {
		$shopping_disapproved = WC_Helper_Product::create_simple_product();
		$display_only         = WC_Helper_Product::create_simple_product();
		$stored               = $this->use_product_status_fakes( [ $shopping_disapproved, $display_only ] );

		$offer = function ( int $product_id, array $destination_statuses ): Product {
			return Product::from_array(
				[
					'name'          => 'accounts/123/products/en~US~gla_' . $product_id,
					'productStatus' => [ 'destinationStatuses' => $destination_statuses ],
				]
			);
		};

		$this->merchant_statuses->process_mapi_products(
			[
				$offer(
					$shopping_disapproved->get_id(),
					[
						[
							'reportingContext'     => 'SHOPPING_ADS',
							'disapprovedCountries' => [ 'US' ],
						],
						[
							'reportingContext'  => 'DISPLAY_ADS',
							'approvedCountries' => [ 'US' ],
						],
					]
				),
				$offer(
					$display_only->get_id(),
					[
						[
							'reportingContext'  => 'DISPLAY_ADS',
							'approvedCountries' => [ 'US' ],
						],
					]
				),
			]
		);
		$this->merchant_statuses->record_feed_issue_page( null, null );

		$tracking = $stored( OptionsInterface::FEED_ISSUE_TRACKING );
		$this->assertSame( [ $shopping_disapproved->get_id() => true ], $tracking['product_ids'] );
		$this->assertSame( [], $tracking['approved_product_ids'] );
	}

	public function test_process_mapi_products_records_only_blocking_shopping_ads_and_free_listings_issue_codes() {
		$simple    = WC_Helper_Product::create_simple_product();
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$stored    = $this->use_product_status_fakes( [ $simple, $variable, $variation ] );
		$this->product_helper->method( 'get_synced_google_product_ids' )->willReturnCallback(
			function ( $product ) {
				return [ 'ES' => $this->get_mapi_id( $product->get_id() ) ];
			}
		);
		$this->merchant_issue_query->method( 'get_results' )->willReturn( [] );

		$this->merchant_statuses->process_mapi_products(
			[
				$this->get_mapi_offer_with_issues(
					$simple->get_id(),
					[
						$this->item_level_issue( 'missing_gtin', 'SHOPPING_ADS' ),
						$this->item_level_issue( 'image_link_broken[0]', 'FREE_LISTINGS' ),
						$this->item_level_issue( 'no_reporting_context', null ),
						$this->item_level_issue( 'display_ads_only', 'DISPLAY_ADS' ),
						$this->item_level_issue( 'youtube_only', 'YOUTUBE_SHOPPING' ),
						$this->item_level_issue( 'demoted', 'SHOPPING_ADS', 'DEMOTED' ),
						$this->item_level_issue( 'not_impacted', 'SHOPPING_ADS', 'NOT_IMPACTED' ),
						$this->item_level_issue( 'pending', 'SHOPPING_ADS', 'DISAPPROVED', 'pending_processing' ),
					]
				),
				$this->get_mapi_offer_with_issues( $variation->get_id(), [ $this->item_level_issue( 'missing_brand', 'SHOPPING_ADS' ) ] ),
			]
		);
		$this->merchant_statuses->record_feed_issue_page( null, null );

		$this->assertSame(
			[
				$simple->get_id()   => [
					'missing_gtin'         => true,
					'image_link_broken'    => true,
					'no_reporting_context' => true,
				],
				$variable->get_id() => [ 'missing_brand' => true ],
			],
			$stored( OptionsInterface::FEED_ISSUE_TRACKING )['issue_codes']
		);
	}

	public function test_record_feed_issue_page_merges_issue_codes_across_pages() {
		$product = WC_Helper_Product::create_simple_product();
		$stored  = $this->use_product_status_fakes( [ $product ] );
		$this->product_helper->method( 'get_synced_google_product_ids' )->willReturnCallback(
			function ( $wc_product ) {
				return [ 'ES' => $this->get_mapi_id( $wc_product->get_id() ) ];
			}
		);
		$this->merchant_issue_query->method( 'get_results' )->willReturn( [] );

		$this->merchant_statuses->process_mapi_products( [ $this->get_mapi_offer_with_issues( $product->get_id(), [ $this->item_level_issue( 'missing_gtin', 'SHOPPING_ADS' ) ] ) ] );
		$this->merchant_statuses->record_feed_issue_page( null, 'page-2' );
		$this->merchant_statuses->process_mapi_products( [ $this->get_mapi_offer_with_issues( $product->get_id(), [ $this->item_level_issue( 'missing_brand', 'FREE_LISTINGS' ) ] ) ] );
		$this->merchant_statuses->record_feed_issue_page( 'page-2', null );

		$this->assertSame(
			[
				$product->get_id() => [
					'missing_gtin'  => true,
					'missing_brand' => true,
				],
			],
			$stored( OptionsInterface::FEED_ISSUE_TRACKING )['issue_codes']
		);
	}

	public function test_record_feed_issue_page_drops_tracking_an_older_version_started() {
		$stored = $this->use_product_status_fakes( [] );
		$this->options->update(
			OptionsInterface::FEED_ISSUE_TRACKING,
			[
				'next_page_token' => 'page-2',
				'product_ids'     => [ 5 => true ],
			]
		);

		$this->merchant_statuses->record_feed_issue_page( 'page-2', null );

		$this->assertNull( $stored( OptionsInterface::FEED_ISSUE_TRACKING ) );
	}

	public function test_refresh_account_issues_tracks_only_shopping_ads_and_free_listings_impacts() {
		$stored = $this->use_product_status_fakes( [] );
		$this->product_meta_query_helper->method( 'get_all_values' )->willReturn( [] );
		$this->mapi_account_issues_service->method( 'get_account_issues' )->willReturn(
			[
				[
					'name'                 => 'accounts/1/issues/mixed-programs',
					'title'                => 'Mixed programs',
					'severity'             => 'CRITICAL',
					'impactedDestinations' => [
						[
							'reportingContext' => 'SHOPPING_ADS',
							'impacts'          => [
								[
									'regionCode' => 'US',
									'severity'   => 'ERROR',
								],
							],
						],
						[
							'reportingContext' => 'FREE_LISTINGS',
							'impacts'          => [
								[
									'regionCode' => 'CA',
									'severity'   => 'ERROR',
								],
							],
						],
						[
							'reportingContext' => 'DISPLAY_ADS',
							'impacts'          => [
								[
									'regionCode' => 'GB',
									'severity'   => 'CRITICAL',
								],
							],
						],
					],
				],
				[
					'name'                 => 'accounts/1/issues/display-only',
					'title'                => 'Display only',
					'severity'             => 'CRITICAL',
					'impactedDestinations' => [
						[
							'reportingContext' => 'DISPLAY_ADS',
							'impacts'          => [
								[
									'regionCode' => 'US',
									'severity'   => 'CRITICAL',
								],
							],
						],
					],
				],
				[
					'name'     => 'accounts/1/issues/no-destinations',
					'title'    => 'No destinations',
					'severity' => 'ERROR',
				],
				[
					'name'                 => 'accounts/1/issues/no-impact-severity',
					'title'                => 'No impact severity',
					'severity'             => 'CRITICAL',
					'impactedDestinations' => [
						[ 'impacts' => [ [ 'regionCode' => 'FR' ] ] ],
					],
				],
				[
					'name'                 => 'accounts/1/issues/merchant-quality-low',
					'title'                => 'Merchant quality low',
					'severity'             => 'CRITICAL',
					'impactedDestinations' => [
						[
							'reportingContext' => 'FREE_LISTINGS',
							'impacts'          => [
								[
									'regionCode' => 'US',
									'severity'   => 'CRITICAL',
								],
							],
						],
					],
				],
			]
		);

		$this->merchant_statuses->refresh_account_and_presync_issues();
		$this->merchant_statuses->record_feed_issue_page( null, null );

		$this->assertSame(
			[
				$this->feed_account_issue( 'mixed_programs', 'error', [ 'CA', 'US' ] ),
				$this->feed_account_issue( 'no_destinations', 'error', [] ),
				$this->feed_account_issue( 'no_impact_severity', 'critical', [ 'FR' ] ),
				$this->feed_account_issue( 'merchant_quality_low', 'warning', [ 'US' ] ),
			],
			$stored( OptionsInterface::FEED_ISSUE_TRACKING )['account_issues']
		);
	}

	public function test_record_feed_issue_page_leaves_account_issues_unknown_without_a_refresh() {
		$stored = $this->use_product_status_fakes( [] );

		$this->merchant_statuses->record_feed_issue_page( null, null );

		$this->assertNull( $stored( OptionsInterface::FEED_ISSUE_TRACKING )['account_issues'] );
	}

	public function test_feed_summary_preserves_earlier_pages_after_a_failed_fetch_and_retry() {
		$product = WC_Helper_Product::create_simple_product();
		$stored  = $this->use_product_status_fakes( [ $product ] );
		$this->merchant_issue_table->method( 'count_products_with_issues' )->willReturn( 0 );
		$this->product_meta_query_helper->method( 'count_products_by_value' )->willReturn( 1 );
		$this->options->update( OptionsInterface::FEED_ISSUE_SUMMARY, [ 'previous' => true ] );

		$mapi = $this->createMock( MapiProductsService::class );
		$call = 0;
		$mapi->method( 'list_page' )->willReturnCallback(
			function ( $token ) use ( &$call, $product ) {
				++$call;
				if ( 1 === $call ) {
					$this->assertNull( $token );
					return [
						'products'        => [ $this->get_mapi_offer( $product->get_id(), 'US', 'disapprovedCountries' ) ],
						'next_page_token' => 'page-2',
					];
				}
				$this->assertSame( 'page-2', $token );
				if ( 2 === $call ) {
					throw new Exception( 'Temporary API failure' );
				}
				return [
					'products'        => [ $this->get_mapi_offer( $product->get_id(), 'GB', 'approvedCountries' ) ],
					'next_page_token' => null,
				];
			}
		);

		// Each scheduled action gets a fresh service instance, as on separate HTTP requests.
		for ( $attempt = 0; $attempt < 3; ++$attempt ) {
			$statuses = new MerchantStatuses();
			$statuses->set_container( $this->container );
			$statuses->set_options_object( $this->options );
			$job = new UpdateMerchantProductStatuses(
				$this->createMock( ActionSchedulerInterface::class ),
				$this->createMock( ActionSchedulerJobMonitor::class ),
				$this->merchant_center_service,
				$mapi,
				$statuses
			);
			try {
				$job->process_items( 0 === $attempt ? [] : [ 'next_page_token' => 'page-2' ] );
				$this->assertNotSame( 1, $attempt, 'The second attempt must fail.' );
			} catch ( JobException $e ) {
				$this->assertSame( 1, $attempt );
				$this->assertStringContainsString( 'Temporary API failure', $e->getMessage() );
				$this->assertSame( [ 'previous' => true ], $stored( OptionsInterface::FEED_ISSUE_SUMMARY ) );
				$this->assertSame( [ $product->get_id() => true ], $stored( OptionsInterface::FEED_ISSUE_TRACKING )['product_ids'] );
			}
		}

		$this->assertSame( 1, $stored( OptionsInterface::FEED_ISSUE_SUMMARY )['disapproved_product_count'] );
		$this->assertNull( $stored( OptionsInterface::FEED_ISSUE_TRACKING ) );
	}

	/**
	 * @dataProvider incomplete_feed_check_provider
	 * @param bool $has_stale_tracking Whether tracking from a different check remains after a rollback.
	 */
	public function test_feed_summary_skips_checks_started_before_upgrade( bool $has_stale_tracking ) {
		$product = WC_Helper_Product::create_simple_product();
		$stored  = $this->use_product_status_fakes( [ $product ] );
		$this->options->update( OptionsInterface::FEED_ISSUE_SUMMARY, [ 'previous' => true ] );
		if ( $has_stale_tracking ) {
			$this->merchant_statuses->record_feed_issue_page( null, 'old-check-token' );
		}

		$this->merchant_statuses->process_mapi_products( [ $this->get_mapi_offer( $product->get_id(), 'US', 'disapprovedCountries' ) ] );
		$this->merchant_statuses->record_feed_issue_page( 'page-started-before-upgrade', null );
		$this->merchant_statuses->handle_complete_mc_statuses_fetching();

		$this->assertSame( [ 'previous' => true ], $stored( OptionsInterface::FEED_ISSUE_SUMMARY ) );
		$this->assertNull( $stored( OptionsInterface::FEED_ISSUE_TRACKING ) );
	}

	public function incomplete_feed_check_provider(): array {
		return [
			'missing tracking' => [ false ],
			'stale tracking'   => [ true ],
		];
	}

	public function test_feed_summary_exception_does_not_fail_completed_statuses() {
		$stored = $this->use_product_status_fakes( [] );
		$this->options->update( OptionsInterface::FEED_ISSUE_SUMMARY, [ 'previous' => true ] );
		$this->merchant_statuses->record_feed_issue_page( null, null );
		$this->merchant_issue_table->method( 'count_products_with_issues' )->willThrowException( new \Error( 'Count failed' ) );
		$this->transients->expects( $this->once() )->method( 'set' )->with(
			Transients::MC_STATUSES,
			$this->callback(
				function ( $value ) {
					return null === $value['error'] && false === $value['loading'];
				}
			),
			self::MC_STATUS_LIFETIME
		);
		$messages = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message ) use ( &$messages ) {
				$messages[] = $message;
			}
		);

		$this->merchant_statuses->handle_complete_mc_statuses_fetching();

		$this->assertSame( [ 'previous' => true ], $stored( OptionsInterface::FEED_ISSUE_SUMMARY ) );
		$this->assertNull( $stored( OptionsInterface::FEED_ISSUE_TRACKING ) );
		$this->assertNull( $stored() );
		$this->assertStringContainsString( 'Count failed', implode( "\n", $messages ) );
	}

	public function test_feed_page_tracking_exception_is_logged() {
		$this->options->method( 'update' )->willThrowException( new \Error( 'Tracking write failed' ) );
		$messages = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message ) use ( &$messages ) {
				$messages[] = $message;
			}
		);

		$this->merchant_statuses->record_feed_issue_page( null, null );

		$this->assertStringContainsString( 'Tracking write failed', implode( "\n", $messages ) );
	}

	public function test_process_product_statuses_skips_legacy_google_ids() {
		$product_valid  = WC_Helper_Product::create_simple_product();
		$product_legacy = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_by_ids_as_associative_array' )
		->willReturnCallback(
			function ( $ids ) use ( $product_valid, $product_legacy ) {
				$map = [
					$product_valid->get_id()  => $product_valid,
					$product_legacy->get_id() => $product_legacy,
				];
				return array_intersect_key( $map, array_flip( $ids ) );
			}
		);

		$this->options->method( 'get' )->willReturn( null );

		// product_valid carries a Merchant API id; product_legacy still has a pre-migration
		// colon-format id that the Merchant API rejects as an invalid name.
		$this->product_helper->method( 'get_synced_google_product_ids' )
		->willReturnCallback(
			function ( $product ) use ( $product_legacy ) {
				if ( $product->get_id() === $product_legacy->get_id() ) {
					return [ 'ES' => $this->get_mc_id( $product->get_id() ) ];
				}
				return [ 'ES' => $this->get_mapi_id( $product->get_id() ) ];
			}
		);

		$this->merchant_issue_query->method( 'get_results' )->willReturn( [] );

		// Products are provided under both id forms; only the valid Merchant API id may
		// produce issues, the legacy colon-format id is filtered out of the id map.
		$this->merchant_issue_query->expects( $this->once() )->method( 'update_or_insert' )->with(
			$this->callback(
				function ( array $issues ) use ( $product_valid ) {
					return 1 === count( $issues ) && $issues[0]['product_id'] === $product_valid->get_id();
				}
			)
		);

		$this->merchant_statuses->process_product_statuses(
			[
				[
					'mc_id'           => $this->get_mc_id( $product_valid->get_id() ),
					'product_id'      => $product_valid->get_id(),
					'status'          => MCStatus::APPROVED,
					'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
				],
				[
					'mc_id'           => $this->get_mc_id( $product_legacy->get_id() ),
					'product_id'      => $product_legacy->get_id(),
					'status'          => MCStatus::APPROVED,
					'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
				],
			],
			[
				$this->get_mapi_id( $product_valid->get_id() )  => $this->get_mapi_product( $product_valid->get_id() ),
				$this->get_mc_id( $product_legacy->get_id() )   => $this->get_mapi_product( $product_legacy->get_id() ),
			]
		);
	}

	public function test_process_product_statuses_keeps_valid_ids_when_a_product_has_mixed_ids() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_by_ids_as_associative_array' )
		->willReturnCallback(
			function ( $ids ) use ( $product ) {
				return array_intersect_key( [ $product->get_id() => $product ], array_flip( $ids ) );
			}
		);

		$this->options->method( 'get' )->willReturn( null );

		// One product synced in two countries: a legacy colon-format id and a Merchant API id.
		$this->product_helper->method( 'get_synced_google_product_ids' )
		->willReturnCallback(
			function ( $wc_product ) {
				return [
					'ES' => $this->get_mc_id( $wc_product->get_id() ),
					'US' => $this->get_mapi_id( $wc_product->get_id() ),
				];
			}
		);

		$this->merchant_issue_query->method( 'get_results' )->willReturn( [] );

		// Products are provided under both id forms; issues may only come from the
		// Merchant API id. The countries assertion is the discriminator: the copy under
		// the legacy id carries FR, so a leak would produce ["ES","FR"].
		$this->merchant_issue_query->expects( $this->once() )->method( 'update_or_insert' )->with(
			$this->callback(
				function ( array $issues ) use ( $product ) {
					return 1 === count( $issues )
						&& $issues[0]['product_id'] === $product->get_id()
						&& wp_json_encode( [ 'ES' ] ) === $issues[0]['applicable_countries'];
				}
			)
		);

		$this->merchant_statuses->process_product_statuses(
			[
				[
					'mc_id'           => $this->get_mapi_id( $product->get_id() ),
					'product_id'      => $product->get_id(),
					'status'          => MCStatus::APPROVED,
					'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
				],
			],
			[
				$this->get_mc_id( $product->get_id() )   => $this->get_mapi_product( $product->get_id(), 'merchant_action', [ 'FR' ] ),
				$this->get_mapi_id( $product->get_id() ) => $this->get_mapi_product( $product->get_id() ),
			]
		);
	}

	public function test_refresh_product_issues_folds_in_rows_from_earlier_pages() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_by_ids_as_associative_array' )
		->willReturnCallback(
			function ( $ids ) use ( $product ) {
				return array_intersect_key( [ $product->get_id() => $product ], array_flip( $ids ) );
			}
		);

		$this->options->method( 'get' )->willReturn( null );

		$this->product_helper->method( 'get_synced_google_product_ids' )
		->willReturnCallback(
			function ( $wc_product ) {
				return [ 'ES' => $this->get_mapi_id( $wc_product->get_id() ) ];
			}
		);

		// Rows written by earlier pages of the same refresh: one for the same product
		// and issue (folded in and deleted) and one for a different issue of the same
		// product (left untouched).
		$this->merchant_issue_query->method( 'get_results' )->willReturn(
			[
				[
					'id'                   => 5,
					'product_id'           => $product->get_id(),
					'issue'                => 'issue_description',
					'applicable_countries' => wp_json_encode( [ 'US', 'GB', 'ES' ] ),
				],
				[
					'id'                   => 6,
					'product_id'           => $product->get_id(),
					'issue'                => 'another_issue',
					'applicable_countries' => wp_json_encode( [ 'US' ] ),
				],
			]
		);

		$where_calls = [];
		$this->merchant_issue_query->method( 'where' )
		->willReturnCallback(
			function ( $column, $value, $compare = '=' ) use ( &$where_calls ) {
				$where_calls[] = [ $column, $value, $compare ];
				return $this->merchant_issue_query;
			}
		);

		$call_order = [];

		// Only the row for the same product and issue is replaced, and only after
		// the merged row is written, so an interrupted page cannot lose countries.
		$this->merchant_issue_table->expects( $this->once() )->method( 'delete_by_ids' )
		->with( [ 5 ] )
		->willReturnCallback(
			function () use ( &$call_order ) {
				$call_order[] = 'delete';
			}
		);

		// The stored countries are the union of the earlier page's row and this page's
		// entry, de-duplicated (ES appears in both) and sorted.
		$this->merchant_issue_query->expects( $this->once() )->method( 'update_or_insert' )->with(
			$this->callback(
				function ( array $issues ) use ( $product ) {
					return 1 === count( $issues )
						&& $issues[0]['product_id'] === $product->get_id()
						&& wp_json_encode( [ 'ES', 'GB', 'US' ] ) === $issues[0]['applicable_countries'];
				}
			)
		)
		->willReturnCallback(
			function () use ( &$call_order ) {
				$call_order[] = 'insert';
			}
		);

		$this->merchant_statuses->process_product_statuses(
			[
				[
					'mc_id'           => $this->get_mapi_id( $product->get_id() ),
					'product_id'      => $product->get_id(),
					'status'          => MCStatus::APPROVED,
					'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
				],
			],
			[ $this->get_mapi_id( $product->get_id() ) => $this->get_mapi_product( $product->get_id() ) ]
		);

		// The lookup covers exactly this page's products and no other issue source.
		$this->assertContains( [ 'product_id', [ $product->get_id() ], 'IN' ], $where_calls );
		$this->assertContains( [ 'source', 'mc', '=' ], $where_calls );
		$this->assertContains( [ 'type', 'product', '=' ], $where_calls );

		$this->assertSame( [ 'insert', 'delete' ], $call_order );
	}

	public function test_refresh_product_issues_resets_the_country_map_between_pages() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_by_ids_as_associative_array' )
		->willReturnCallback(
			function ( $ids ) use ( $product ) {
				return array_intersect_key( [ $product->get_id() => $product ], array_flip( $ids ) );
			}
		);

		$this->options->method( 'get' )->willReturn( null );

		$this->product_helper->method( 'get_synced_google_product_ids' )
		->willReturnCallback(
			function ( $wc_product ) {
				return [ 'ES' => $this->get_mapi_id( $wc_product->get_id() ) ];
			}
		);

		// No rows in the table either time: the second call stands for a page of a
		// later refresh cycle running in the same long-lived process.
		$this->merchant_issue_query->method( 'get_results' )->willReturn( [] );

		$captured_rows = [];
		$this->merchant_issue_query->expects( $this->exactly( 2 ) )->method( 'update_or_insert' )
		->willReturnCallback(
			function ( array $issues ) use ( &$captured_rows ) {
				$captured_rows[] = $issues;
			}
		);

		$page = [
			[
				'mc_id'           => $this->get_mapi_id( $product->get_id() ),
				'product_id'      => $product->get_id(),
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],
		];

		$this->merchant_statuses->process_product_statuses(
			$page,
			[ $this->get_mapi_id( $product->get_id() ) => $this->get_mapi_product( $product->get_id(), 'merchant_action', [ 'FR' ] ) ]
		);
		$this->merchant_statuses->process_product_statuses(
			$page,
			[ $this->get_mapi_id( $product->get_id() ) => $this->get_mapi_product( $product->get_id() ) ]
		);

		// Without the per-page reset, the singleton's in-memory map would leak FR
		// from the first call into the second call's row.
		$this->assertSame( wp_json_encode( [ 'FR' ] ), $captured_rows[0][0]['applicable_countries'] );
		$this->assertSame( wp_json_encode( [ 'ES' ] ), $captured_rows[1][0]['applicable_countries'] );
	}

	public function test_refresh_product_issues_tolerates_malformed_stored_countries() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_by_ids_as_associative_array' )
		->willReturnCallback(
			function ( $ids ) use ( $product ) {
				return array_intersect_key( [ $product->get_id() => $product ], array_flip( $ids ) );
			}
		);

		$this->options->method( 'get' )->willReturn( null );

		$this->product_helper->method( 'get_synced_google_product_ids' )
		->willReturnCallback(
			function ( $wc_product ) {
				return [ 'ES' => $this->get_mapi_id( $wc_product->get_id() ) ];
			}
		);

		// A matching row whose stored countries are valid JSON but not an array
		// contributes nothing, and must not fail the page.
		$this->merchant_issue_query->method( 'get_results' )->willReturn(
			[
				[
					'id'                   => 7,
					'product_id'           => $product->get_id(),
					'issue'                => 'issue_description',
					'applicable_countries' => '"GB"',
				],
			]
		);

		$this->merchant_issue_table->expects( $this->once() )->method( 'delete_by_ids' )->with( [ 7 ] );

		$this->merchant_issue_query->expects( $this->once() )->method( 'update_or_insert' )->with(
			$this->callback(
				function ( array $issues ) {
					return 1 === count( $issues )
						&& wp_json_encode( [ 'ES' ] ) === $issues[0]['applicable_countries'];
				}
			)
		);

		$this->merchant_statuses->process_product_statuses(
			[
				[
					'mc_id'           => $this->get_mapi_id( $product->get_id() ),
					'product_id'      => $product->get_id(),
					'status'          => MCStatus::APPROVED,
					'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
				],
			],
			[ $this->get_mapi_id( $product->get_id() ) => $this->get_mapi_product( $product->get_id() ) ]
		);
	}

	public function test_handle_complete_mc_statuses_fetching_with_default_intermediate_data() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[
					OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA,
					MerchantStatuses::DEFAULT_PRODUCT_STATS,
					$this->merchant_statuses::DEFAULT_PRODUCT_STATS,
				],
			]
		);

		$expected_product_ids = [ 1, 2, 3, 4, 5, 6 ];
		$this->product_repository->expects( $this->once() )->method( 'find_all_product_ids' )->willReturn( $expected_product_ids );

		$this->transients->expects( $this->once() )
		->method( 'set' )->with(
			Transients::MC_STATUSES,
			$this->callback(
				function ( $value ) use ( $expected_product_ids ) {
					$this->assertEquals(
						[
							MCStatus::APPROVED           => 0,
							MCStatus::PARTIALLY_APPROVED => 0,
							MCStatus::EXPIRING           => 0,
							MCStatus::PENDING            => 0,
							MCStatus::DISAPPROVED        => 0,
							MCStatus::NOT_SYNCED         => count( $expected_product_ids ),
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

	public function test_handle_complete_mc_statuses_fetching() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[
					OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA,
					MerchantStatuses::DEFAULT_PRODUCT_STATS,
					[
						MCStatus::APPROVED           => 3,
						MCStatus::PARTIALLY_APPROVED => 1,
						MCStatus::EXPIRING           => 0,
						MCStatus::PENDING            => 0,
						MCStatus::DISAPPROVED        => 1,
						MCStatus::NOT_SYNCED         => 0,
					],
				],
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
							MCStatus::APPROVED           => 3,
							MCStatus::PARTIALLY_APPROVED => 1,
							MCStatus::EXPIRING           => 0,
							MCStatus::PENDING            => 0,
							MCStatus::DISAPPROVED        => 1,
							MCStatus::NOT_SYNCED         => 1,
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

	public function test_handle_complete_mc_statuses_fetching_saves_feed_issue_summary() {
		// Another status count equals the not-synced count, which the aggregate synced-total helper drops by value.
		$this->options->method( 'get' )->willReturnMap(
			[
				[
					OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA,
					MerchantStatuses::DEFAULT_PRODUCT_STATS,
					array_merge(
						MerchantStatuses::DEFAULT_PRODUCT_STATS,
						[
							MCStatus::APPROVED   => 1,
							MCStatus::NOT_SYNCED => 1,
						]
					),
				],
				[
					OptionsInterface::FEED_ISSUE_TRACKING,
					null,
					$this->feed_issue_tracking(
						[
							'product_ids'          => [
								5 => true,
								9 => true,
							],
							'approved_product_ids' => [
								5 => true,
								6 => true,
								7 => true,
							],
							'issue_codes'          => [
								5 => [
									'image_link_broken' => true,
									'missing_gtin'      => true,
								],
								6 => [ 'image_link_broken' => true ],
								9 => [ 'image_link_broken' => true ],
							],
							'account_issues'       => [
								$this->feed_account_issue( 'home_page_issue', 'critical', [ 'US' ] ),
								$this->feed_account_issue( 'policy_enforcement', 'error', [ 'CA' ] ),
								$this->feed_account_issue( 'merchant_quality_low', 'warning', [ 'US' ] ),
							],
						]
					),
				],
			]
		);
		$this->product_repository->method( 'find_all_product_ids' )->willReturn( [ 1, 2, 3 ] );
		$this->transients->expects( $this->once() )->method( 'set' )->with(
			Transients::MC_STATUSES,
			$this->callback(
				function ( $value ) {
					return array_keys( array_diff_key( MerchantStatuses::DEFAULT_PRODUCT_STATS, [ 'parents' => [] ] ) ) === array_keys( $value['statistics'] );
				}
			),
			self::MC_STATUS_LIFETIME
		);

		$this->product_meta_query_helper->method( 'count_products_by_value' )->willReturnCallback(
			function ( $meta_key, $values ) {
				$this->assertSame( ProductMetaHandler::KEY_MC_STATUS, $meta_key );
				$this->assertEqualsCanonicalizing( [ 'approved', 'partially_approved', 'pending', 'expiring', 'disapproved' ], $values );
				return 7;
			}
		);
		$this->merchant_issue_table->expects( $this->never() )->method( 'count_account_issues' );
		$this->merchant_issue_table->method( 'count_products_with_issues' )->willReturnCallback(
			function ( $source, $excluded = [] ) {
				$this->assertSame( [ 'pre-sync', [] ], [ $source, $excluded ] );
				return 5;
			}
		);
		$this->merchant_issue_table->method( 'get_account_issue_codes' )->willReturnCallback(
			function ( $source ) {
				$this->assertSame( 'filter', $source );
				return [ 'missing_contact_information', 'third_party_code' ];
			}
		);
		$this->merchant_issue_table->method( 'get_top_issue_codes' )->willReturnCallback(
			function ( $source, $excluded = [], $limit = 5 ) {
				$this->assertSame( [ 'pre-sync', [], 5 ], [ $source, $excluded, $limit ] );
				return [ 'presync_error_price' => 3 ];
			}
		);
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA' ] );

		$expected_summary = [
			'blocking_account_issue_count' => 2,
			'disapproved_product_count'    => 2,
			'error_issue_product_count'    => 3,
			'presync_error_product_count'  => 5,
			'synced_product_count'         => 7,
		];
		$this->assertSame( MerchantStatuses::FEED_ISSUE_SUMMARY_FIELDS, array_keys( $expected_summary ) );
		$expected_summary += [
			// Product 5 is also disapproved, so it counts only as disapproved.
			'approved_product_count'       => 2,
			'critical_account_issue_count' => 1,
			'account_issue_codes'          => [
				'home_page_issue'    => 'critical',
				'policy_enforcement' => 'error',
			],
			'critical_covers_all_targets'  => 'partial',
			'plugin_issue_codes'           => [ 'missing_contact_information' ],
			'top_product_issue_codes'      => [
				'image_link_broken' => 3,
				'missing_gtin'      => 1,
			],
			'top_presync_issue_codes'      => [ 'presync_error_price' => 3 ],
		];

		$saved = null;
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::FEED_ISSUE_SUMMARY,
				$this->callback(
					function ( $summary ) use ( &$saved ) {
						$saved = $summary;
						return true;
					}
				)
			);

		$before = time();
		$this->merchant_statuses->handle_complete_mc_statuses_fetching();

		$this->assert_checked_at_between( $before, time(), $saved );
		unset( $saved['feed_issue_summary_checked_at'] );
		$this->assertSame( $expected_summary, $saved );
	}

	public function test_handle_complete_mc_statuses_fetching_saves_zero_counts_without_data() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, MerchantStatuses::DEFAULT_PRODUCT_STATS, MerchantStatuses::DEFAULT_PRODUCT_STATS ],
				[ OptionsInterface::FEED_ISSUE_TRACKING, null, $this->feed_issue_tracking() ],
			]
		);
		$this->product_repository->method( 'find_all_product_ids' )->willReturn( [] );
		$this->product_meta_query_helper->method( 'count_products_by_value' )->willReturn( 0 );
		$this->merchant_issue_table->method( 'count_products_with_issues' )->willReturn( 0 );
		$this->merchant_issue_table->method( 'get_account_issue_codes' )->willReturn( [] );
		$this->merchant_issue_table->method( 'get_top_issue_codes' )->willReturn( [] );
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );

		$saved = null;
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::FEED_ISSUE_SUMMARY,
				$this->callback(
					function ( $summary ) use ( &$saved ) {
						$saved = $summary;
						return true;
					}
				)
			);

		$before = time();
		$this->merchant_statuses->handle_complete_mc_statuses_fetching();

		$this->assert_checked_at_between( $before, time(), $saved );
		unset( $saved['feed_issue_summary_checked_at'] );
		$this->assertSame(
			[
				'blocking_account_issue_count' => 0,
				'disapproved_product_count'    => 0,
				'error_issue_product_count'    => 0,
				'presync_error_product_count'  => 0,
				'synced_product_count'         => 0,
				'approved_product_count'       => 0,
				'critical_account_issue_count' => 0,
				'account_issue_codes'          => [],
				'critical_covers_all_targets'  => 'no',
				'plugin_issue_codes'           => [],
				'top_product_issue_codes'      => [],
				'top_presync_issue_codes'      => [],
			],
			$saved
		);
	}

	/**
	 * @dataProvider failed_feed_count_provider
	 * @param string $failed_count The count that fails.
	 */
	public function test_handle_complete_mc_statuses_fetching_keeps_previous_summary_when_a_count_fails( string $failed_count ) {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, MerchantStatuses::DEFAULT_PRODUCT_STATS, MerchantStatuses::DEFAULT_PRODUCT_STATS ],
				[
					OptionsInterface::FEED_ISSUE_TRACKING,
					null,
					// Account issues are unknown when the refresh that reads them didn't run in the request that started the check.
					$this->feed_issue_tracking( [ 'account_issues' => 'blocking_account_issue_count' === $failed_count ? null : [] ] ),
				],
			]
		);
		$this->product_repository->method( 'find_all_product_ids' )->willReturn( [] );
		$this->product_meta_query_helper->method( 'count_products_by_value' )->willReturn( 'synced_product_count' === $failed_count ? null : 0 );
		$this->merchant_issue_table->method( 'count_products_with_issues' )->willReturn( 'presync_error_product_count' === $failed_count ? null : 0 );
		$messages = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message ) use ( &$messages ) {
				$messages[] = $message;
			}
		);

		$this->options->expects( $this->never() )->method( 'update' );

		$this->merchant_statuses->handle_complete_mc_statuses_fetching();
		$this->assertStringContainsString( $failed_count, implode( "\n", $messages ) );
	}

	public function failed_feed_count_provider(): array {
		return [
			'account'  => [ 'blocking_account_issue_count' ],
			'pre-sync' => [ 'presync_error_product_count' ],
			'synced'   => [ 'synced_product_count' ],
		];
	}

	public function test_feed_summary_maps_mc_account_codes_to_their_highest_blocking_severity() {
		$summary = $this->complete_status_check_with_issue_details(
			[
				'account_issues' => [
					$this->feed_account_issue( 'raised_code', 'error' ),
					$this->feed_account_issue( 'raised_code', 'critical' ),
					$this->feed_account_issue( 'kept_code', 'critical' ),
					$this->feed_account_issue( 'kept_code', 'error' ),
					$this->feed_account_issue( 'error_code', 'error' ),
					$this->feed_account_issue( 'warning_code', 'warning' ),
					$this->feed_account_issue( 'suggestion_code', 'suggestion' ),
				],
			]
		);

		$this->assertSame(
			[
				'kept_code'   => 'critical',
				'raised_code' => 'critical',
				'error_code'  => 'error',
			],
			$summary['account_issue_codes']
		);
		$this->assertSame( 2, $summary['critical_account_issue_count'] );
		$this->assertSame( 5, $summary['blocking_account_issue_count'] );
	}

	public function test_feed_summary_keeps_ten_account_codes_critical_first() {
		$issues = [];
		foreach ( range( 12, 1 ) as $n ) {
			$issues[] = $this->feed_account_issue( sprintf( 'code_%02d', $n ), $n > 9 ? 'critical' : 'error' );
		}

		$summary = $this->complete_status_check_with_issue_details( [ 'account_issues' => $issues ] );

		$this->assertSame(
			[
				'code_10' => 'critical',
				'code_11' => 'critical',
				'code_12' => 'critical',
				'code_01' => 'error',
				'code_02' => 'error',
				'code_03' => 'error',
				'code_04' => 'error',
				'code_05' => 'error',
				'code_06' => 'error',
				'code_07' => 'error',
			],
			$summary['account_issue_codes']
		);
	}

	public function test_feed_summary_sanitizes_issue_codes() {
		$summary = $this->complete_status_check_with_issue_details(
			[
				'account_issues' => [
					$this->feed_account_issue( 'policy-violation/v2.1', 'error' ),
					$this->feed_account_issue( str_repeat( 'a', 70 ), 'error' ),
				],
				'issue_codes'    => [ 1 => [ 'image link!' => true ] ],
				'presync_codes'  => [ 'presync_error_price.amount' => 3 ],
			]
		);

		$this->assertSame(
			[
				str_repeat( 'a', 64 )   => 'error',
				'policy_violation_v2_1' => 'error',
			],
			$summary['account_issue_codes']
		);
		$this->assertSame( [ 'image_link_' => 1 ], $summary['top_product_issue_codes'] );
		$this->assertSame( [ 'presync_error_price_amount' => 3 ], $summary['top_presync_issue_codes'] );
	}

	public function test_feed_summary_counts_products_per_issue_code_and_keeps_the_top_five() {
		$summary = $this->complete_status_check_with_issue_details(
			[
				'issue_codes' => [
					1 => [
						'code_a' => true,
						'code_b' => true,
						'code_c' => true,
					],
					2 => [
						'code_b' => true,
						'code_d' => true,
						'code_e' => true,
						'code_f' => true,
					],
					3 => [
						'code_b' => true,
						'code_c' => true,
						''       => true,
					],
					4 => [ '123' => true ],
				],
			]
		);

		$this->assertSame( 4, $summary['error_issue_product_count'] );
		$this->assertSame(
			[
				'code_b' => 3,
				'code_c' => 2,
				'123'    => 1,
				'code_a' => 1,
				'code_d' => 1,
			],
			$summary['top_product_issue_codes']
		);
	}

	public function test_feed_summary_counts_a_product_disapproved_in_any_market_only_as_disapproved() {
		$summary = $this->complete_status_check_with_issue_details(
			[
				'product_ids'          => [
					1 => true,
					2 => true,
				],
				'approved_product_ids' => [
					2 => true,
					3 => true,
					4 => true,
				],
			]
		);

		$this->assertSame( 2, $summary['disapproved_product_count'] );
		$this->assertSame( 2, $summary['approved_product_count'] );
	}

	/**
	 * @dataProvider critical_coverage_provider
	 * @param array  $issues                  Account issues.
	 * @param string $expected_coverage       Expected critical_covers_all_targets.
	 * @param int    $expected_critical_count Expected critical_account_issue_count.
	 */
	public function test_feed_summary_reports_critical_issue_coverage_of_target_countries( array $issues, string $expected_coverage, int $expected_critical_count ) {
		$summary = $this->complete_status_check_with_issue_details(
			[
				'account_issues'   => $issues,
				'target_countries' => [ 'US', 'CA' ],
			]
		);

		$this->assertSame(
			[
				'critical_account_issue_count' => $expected_critical_count,
				'critical_covers_all_targets'  => $expected_coverage,
			],
			[
				'critical_account_issue_count' => $summary['critical_account_issue_count'],
				'critical_covers_all_targets'  => $summary['critical_covers_all_targets'],
			]
		);
	}

	public function critical_coverage_provider(): array {
		return [
			'covers one of two targets'          => [ [ $this->feed_account_issue( 'a', 'critical', [ 'US' ] ) ], 'partial', 1 ],
			'an empty country list covers all'   => [
				[
					$this->feed_account_issue( 'a', 'critical', [ 'US' ] ),
					$this->feed_account_issue( 'b', 'critical', [] ),
				],
				'yes',
				2,
			],
			'the World region covers all'        => [ [ $this->feed_account_issue( 'a', 'critical', [ '001' ] ) ], 'yes', 1 ],
			'the World region on an error issue' => [ [ $this->feed_account_issue( 'a', 'error', [ '001' ] ) ], 'no', 0 ],
			'issues together cover every target' => [
				[
					$this->feed_account_issue( 'a', 'critical', [ 'US' ] ),
					$this->feed_account_issue( 'b', 'critical', [ 'CA', 'GB' ] ),
				],
				'yes',
				2,
			],
			'covers only other countries'        => [ [ $this->feed_account_issue( 'a', 'critical', [ 'GB' ] ) ], 'no', 1 ],
			'no critical issues'                 => [ [ $this->feed_account_issue( 'a', 'error', [] ) ], 'no', 0 ],
		];
	}

	public function test_feed_summary_lists_present_plugin_issue_codes() {
		$summary = $this->complete_status_check_with_issue_details(
			[
				'filter_codes'   => [
					'shipping_settings_sync_failed',
					'third_party_code',
					'missing_contact_information',
					'shipping_settings_sync_failed',
				],
				'account_issues' => [ $this->feed_account_issue( 'incompatible_google_product_feed', 'critical' ) ],
			]
		);

		$this->assertSame( [ 'missing_contact_information', 'shipping_settings_sync_failed' ], $summary['plugin_issue_codes'] );
		$this->assertSame( 1, $summary['critical_account_issue_count'] );
	}

	/**
	 * @dataProvider failed_issue_detail_read_provider
	 * @param array    $reads     Issue detail read overrides.
	 * @param string[] $null_keys Summary keys expected to be null.
	 */
	public function test_feed_summary_saves_null_for_a_failed_issue_detail_read( array $reads, array $null_keys ) {
		$messages = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message ) use ( &$messages ) {
				$messages[] = $message;
			}
		);

		$summary = $this->complete_status_check_with_issue_details( $reads );

		$this->assertSame( array_fill_keys( MerchantStatuses::FEED_ISSUE_SUMMARY_FIELDS, 0 ), array_slice( $summary, 0, 5 ) );
		$issue_detail_keys = [
			'feed_issue_summary_checked_at',
			'approved_product_count',
			'critical_account_issue_count',
			'account_issue_codes',
			'critical_covers_all_targets',
			'plugin_issue_codes',
			'top_product_issue_codes',
			'top_presync_issue_codes',
		];
		$this->assertSame( $issue_detail_keys, array_keys( array_slice( $summary, 5 ) ) );
		foreach ( $issue_detail_keys as $key ) {
			$this->assertSame( in_array( $key, $null_keys, true ), null === $summary[ $key ], $key );
		}
		foreach ( $null_keys as $key ) {
			$this->assertStringContainsString( $key, implode( "\n", $messages ) );
		}
	}

	public function failed_issue_detail_read_provider(): array {
		return [
			'plugin issue codes'   => [ [ 'filter_codes' => null ], [ 'plugin_issue_codes' ] ],
			'pre-sync issue codes' => [ [ 'presync_codes' => null ], [ 'top_presync_issue_codes' ] ],
		];
	}

	public function test_feed_summary_saves_counts_with_null_issue_details_when_a_detail_throws() {
		$messages = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message ) use ( &$messages ) {
				$messages[] = $message;
			}
		);

		$summary = $this->complete_status_check_with_issue_details(
			[
				'account_issues'   => [ $this->feed_account_issue( 'a', 'critical', [ 'US' ] ) ],
				'target_countries' => new Exception( 'Target audience failed' ),
			]
		);

		$this->assertSame(
			[
				'blocking_account_issue_count'  => 1,
				'disapproved_product_count'     => 0,
				'error_issue_product_count'     => 0,
				'presync_error_product_count'   => 0,
				'synced_product_count'          => 0,
				'feed_issue_summary_checked_at' => null,
				'approved_product_count'        => null,
				'critical_account_issue_count'  => null,
				'account_issue_codes'           => null,
				'critical_covers_all_targets'   => null,
				'plugin_issue_codes'            => null,
				'top_product_issue_codes'       => null,
				'top_presync_issue_codes'       => null,
			],
			$summary
		);
		$this->assertStringContainsString( 'Target audience failed', implode( "\n", $messages ) );
	}

	public function test_delete_removes_feed_issue_summary() {
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive( [ OptionsInterface::FEED_ISSUE_SUMMARY ], [ OptionsInterface::FEED_ISSUE_TRACKING ] );

		$this->merchant_statuses->delete();
	}

	public function test_handle_failed_mc_statuses_fetching() {
		$product_1  = WC_Helper_Product::create_simple_product();
		$product_id = $product_1->get_id();

		$this->options->expects( $this->exactly( 2 ) )
		->method( 'update' )->withConsecutive(
			// The first time we update the intermediate data with the statuses from  product_statuses.
			[
				OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA,
				array_merge( $this->merchant_statuses::DEFAULT_PRODUCT_STATS, [ 'approved' => 1 ] ),
			],
			// When the job fails it should revert the intermediate data to the previous state.
			[ OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA, $this->merchant_statuses::DEFAULT_PRODUCT_STATS ]
		);

		$product_statuses_1 = [
			[
				'product_id'      => $product_id,
				'status'          => MCStatus::APPROVED,
				'expiration_date' => ( new DateTime() )->add( new DateInterval( 'P20D' ) ),
			],

		];

		$this->product_repository->expects( $this->exactly( 2 ) )->method( 'find_by_ids_as_associative_array' )->willReturn( [ $product_id => $product_1 ] );

		$this->merchant_issue_table->expects( $this->once() )->method( 'delete_specific_product_issues' )->with( [ $product_id ] );

		$this->transients->expects( $this->once() )
		->method( 'set' )->with(
			Transients::MC_STATUSES,
			$this->callback(
				function ( $value ) {
					$this->assertEquals(
						null,
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

		$this->merchant_statuses->process_product_statuses( $product_statuses_1 );

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

		$this->merchant_statuses->process_product_statuses(
			$product_statuses_1
		);

		$merchant_statuses_2 = new MerchantStatuses();
		$merchant_statuses_2->set_container( $this->container );
		$merchant_statuses_2->set_options_object( $this->options );

		$merchant_statuses_2->process_product_statuses(
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

		$this->merchant_statuses->process_product_statuses(
			$product_statuses_1
		);

		$merchant_statuses_2 = new MerchantStatuses();
		$merchant_statuses_2->set_container( $this->container );
		$merchant_statuses_2->set_options_object( $this->options );

		$merchant_statuses_2->process_product_statuses(
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

		$this->merchant_statuses->process_product_statuses(
			$product_statuses_1
		);

		$merchant_statuses_2 = new MerchantStatuses();
		$merchant_statuses_2->set_container( $this->container );
		$merchant_statuses_2->set_options_object( $this->options );

		$merchant_statuses_2->process_product_statuses(
			$product_statuses_2
		);
	}

	public function test_get_issues_when_job_is_scheduled() {
		$this->merchant_center_service->expects( $this->any() )
		->method( 'is_connected' )
		->willReturn( true );

		$this->update_merchant_product_statuses_job->expects( $this->any() )->method( 'is_scheduled' )->willReturn( true );

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )->method( 'get_results' )->willReturn( [] );

		$response = $this->merchant_statuses->get_issues();

		$this->assertEquals(
			[
				'loading' => true,
				'total'   => 0,
				'issues'  => [],
			],
			$response
		);
	}

	public function test_get_issues_when_job_is_not_scheduled() {
		$this->merchant_center_service->expects( $this->any() )
		->method( 'is_connected' )
		->willReturn( true );

		$this->update_merchant_product_statuses_job->expects( $this->any() )->method( 'is_scheduled' )->willReturn( false );

		$this->merchant_issue_query->expects( $this->exactly( 2 ) )->method( 'get_results' )->willReturn( [] );

		$response = $this->merchant_statuses->get_issues();

		$this->assertEquals(
			[
				'loading' => false,
				'total'   => 0,
				'issues'  => [],
			],
			$response
		);
	}

	public function test_clear_cache_no_bulk_sync() {
		$this->update_all_product_job->expects( $this->once() )->method( 'can_schedule' )->with( null )->willReturn( true );
		$this->delete_all_product_job->expects( $this->once() )->method( 'can_schedule' )->with( null )->willReturn( true );

		$this->transients->expects( $this->exactly( 1 ) )
		->method( 'delete' )
		->with(
			TransientsInterface::MC_STATUSES
		);

		$this->merchant_statuses->clear_cache();
	}

	public function test_clear_cache_with_bulk_sync() {
		$this->update_all_product_job->expects( $this->once() )->method( 'can_schedule' )->with( null )->willReturn( false );
		$this->delete_all_product_job->expects( $this->never() )->method( 'can_schedule' )->with( null )->willReturn( true );

		$this->transients->expects( $this->exactly( 0 ) )
		->method( 'delete' );

		$this->merchant_statuses->clear_cache();
	}

	public function test_clear_product_statuses_cache_and_issues() {
		$this->merchant_issue_table->expects( $this->once() )->method( 'delete_stale' )->with( $this->merchant_statuses->get_cache_created_time() );
		$this->options->expects( $this->exactly( 2 ) )->method( 'delete' )->withConsecutive( [ OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA ], [ OptionsInterface::FEED_ISSUE_TRACKING ] );
		$this->product_meta_query_helper->expects( $this->once() )->method( 'delete_all_values' )->with( ProductMetaHandler::KEY_MC_STATUS );
		$this->merchant_statuses->clear_product_statuses_cache_and_issues();
	}

	protected function get_mapi_product( $wc_product_id, string $resolution = 'merchant_action', array $countries = [ 'ES' ] ): Product {
		return Product::from_array(
			[
				'name'          => 'accounts/123/products/' . $this->get_mapi_id( $wc_product_id ),
				'productStatus' => [
					'itemLevelIssues' => [
						[
							'code'                => 'issue_code',
							'description'         => 'issue_description',
							'detail'              => 'issue_detail',
							'documentation'       => 'https://example.com',
							'resolution'          => $resolution,
							'severity'            => 'DISAPPROVED',
							'applicableCountries' => $countries,
						],
					],
				],
			]
		);
	}

	/**
	 * A Merchant API offer for one market with a destination status.
	 *
	 * @param int    $wc_product_id
	 * @param string $country
	 * @param string $status_field approvedCountries or disapprovedCountries.
	 *
	 * @return Product
	 */
	protected function get_mapi_offer( int $wc_product_id, string $country, string $status_field ): Product {
		return Product::from_array(
			[
				'name'          => 'accounts/123/products/en~' . $country . '~gla_' . $wc_product_id,
				'productStatus' => [
					'destinationStatuses' => [
						[
							'reportingContext' => 'SHOPPING_ADS',
							$status_field      => [ $country ],
						],
					],
				],
			]
		);
	}

	/**
	 * A Merchant API offer approved in ES with the given item-level issues.
	 *
	 * @param int     $wc_product_id
	 * @param array[] $issues
	 *
	 * @return Product
	 */
	protected function get_mapi_offer_with_issues( int $wc_product_id, array $issues ): Product {
		return Product::from_array(
			[
				'name'          => 'accounts/123/products/' . $this->get_mapi_id( $wc_product_id ),
				'productStatus' => [
					'destinationStatuses' => [
						[
							'reportingContext'  => 'SHOPPING_ADS',
							'approvedCountries' => [ 'ES' ],
						],
					],
					'itemLevelIssues'     => $issues,
				],
			]
		);
	}

	/**
	 * An item-level issue as the Merchant API returns it.
	 *
	 * @param string      $code
	 * @param string|null $reporting_context Omitted when null.
	 * @param string      $severity
	 * @param string      $resolution
	 *
	 * @return array
	 */
	protected function item_level_issue( string $code, ?string $reporting_context, string $severity = 'DISAPPROVED', string $resolution = 'merchant_action' ): array {
		return array_filter(
			[
				'code'                => $code,
				'description'         => 'Description of ' . $code,
				'resolution'          => $resolution,
				'severity'            => $severity,
				'applicableCountries' => [ 'ES' ],
				'reportingContext'    => $reporting_context,
			],
			function ( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * Wire the product lookups and a fake intermediate-data option for process_mapi_products().
	 *
	 * @param WC_Product[] $products
	 *
	 * @return callable Returns the stored intermediate data.
	 */
	protected function use_product_status_fakes( array $products ): callable {
		$map = [];
		foreach ( $products as $product ) {
			$map[ $product->get_id() ] = $product;
		}

		$this->product_helper->method( 'get_wc_product_id' )->willReturnCallback(
			function ( string $google_id ) {
				return (int) substr( $google_id, strrpos( $google_id, '_' ) + 1 );
			}
		);
		$this->product_repository->method( 'find_by_ids_as_associative_array' )->willReturnCallback(
			function ( $ids ) use ( $map ) {
				return array_intersect_key( $map, array_flip( $ids ) );
			}
		);

		$stored = [];
		$this->options->method( 'get' )->willReturnCallback(
			function ( $key, $fallback = null ) use ( &$stored ) {
				return $stored[ $key ] ?? $fallback;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( $key, $value ) use ( &$stored ) {
				$stored[ $key ] = $value;
				return true;
			}
		);
		$this->options->method( 'delete' )->willReturnCallback(
			function ( $key ) use ( &$stored ) {
				unset( $stored[ $key ] );
				return true;
			}
		);

		return function ( $key = OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA ) use ( &$stored ) {
			return $stored[ $key ] ?? null;
		};
	}

	/**
	 * Complete a status check whose tracking holds the given values, and return the saved summary.
	 *
	 * @param array $reads Overrides for the tracking (product_ids, approved_product_ids, issue_codes, account_issues)
	 *                     and the reads at completion: filter_codes, presync_codes and target_countries (a Throwable is thrown).
	 *
	 * @return array|null
	 */
	protected function complete_status_check_with_issue_details( array $reads = [] ): ?array {
		$reads += [
			'filter_codes'     => [],
			'presync_codes'    => [],
			'target_countries' => [ 'US' ],
		];

		$stored = $this->use_product_status_fakes( [] );
		$this->options->update(
			OptionsInterface::FEED_ISSUE_TRACKING,
			$this->feed_issue_tracking( array_intersect_key( $reads, array_flip( [ 'product_ids', 'approved_product_ids', 'issue_codes', 'account_issues' ] ) ) )
		);

		$this->product_meta_query_helper->method( 'count_products_by_value' )->willReturn( 0 );
		$this->merchant_issue_table->method( 'count_products_with_issues' )->willReturn( 0 );
		$this->merchant_issue_table->method( 'get_account_issue_codes' )->willReturn( $reads['filter_codes'] );
		$this->merchant_issue_table->method( 'get_top_issue_codes' )->willReturn( $reads['presync_codes'] );
		if ( $reads['target_countries'] instanceof Throwable ) {
			$this->target_audience->method( 'get_target_countries' )->willThrowException( $reads['target_countries'] );
		} else {
			$this->target_audience->method( 'get_target_countries' )->willReturn( $reads['target_countries'] );
		}

		$this->merchant_statuses->handle_complete_mc_statuses_fetching();

		return $stored( OptionsInterface::FEED_ISSUE_SUMMARY );
	}

	/**
	 * Build the feed issue tracking of a completed check.
	 *
	 * @param array $overrides
	 *
	 * @return array
	 */
	protected function feed_issue_tracking( array $overrides = [] ): array {
		return array_merge(
			[
				'next_page_token'      => null,
				'product_ids'          => [],
				'approved_product_ids' => [],
				'issue_codes'          => [],
				'account_issues'       => [],
			],
			$overrides
		);
	}

	/**
	 * Build a Shopping ads and free listings account issue as the feed issue tracking stores it.
	 *
	 * @param string   $code
	 * @param string   $severity
	 * @param string[] $applicable_countries
	 *
	 * @return array
	 */
	protected function feed_account_issue( string $code, string $severity, array $applicable_countries = [ 'US' ] ): array {
		return [
			'code'                 => $code,
			'severity'             => $severity,
			'applicable_countries' => $applicable_countries,
		];
	}

	/**
	 * Assert the summary's check time is a Unix time within the given range.
	 *
	 * @param int        $before
	 * @param int        $after
	 * @param array|null $summary
	 */
	protected function assert_checked_at_between( int $before, int $after, ?array $summary ): void {
		$this->assertIsInt( $summary['feed_issue_summary_checked_at'] ?? null );
		$this->assertGreaterThanOrEqual( $before, $summary['feed_issue_summary_checked_at'] );
		$this->assertLessThanOrEqual( $after, $summary['feed_issue_summary_checked_at'] );
	}

	protected function get_mapi_id( $wc_product_id ): string {
		return 'en~ES~gla_' . $wc_product_id;
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
	 *  Assert that the process_product_statuses method updates the product stats in two batches.
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
