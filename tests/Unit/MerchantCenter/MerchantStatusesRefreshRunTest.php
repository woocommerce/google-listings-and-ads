<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountIssuesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTime;
use Exception;
use ReflectionProperty;
use WC_Helper_Product;

/**
 * Class MerchantStatusesRefreshRunTest
 *
 * Drives the status refresh job page by page against the real product meta and
 * merchant issue table: the previous run's statuses and issues must stay readable
 * until the new run replaces them, only a completed run removes what it did not
 * refresh, and that holds whichever process (and process start time) runs a page.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 */
class MerchantStatusesRefreshRunTest extends UnitTest {

	use PluginHelper;

	/** @var MerchantIssueTable */
	protected $issue_table;

	/** @var Container */
	protected $container;

	/** @var array Page token ('' for the first page) => list_page() result or a Throwable. */
	protected $pages = [];

	/** @var array[] Items of the pages the job scheduled. */
	protected $scheduled = [];

	/** @var bool Whether fetching the account issues fails. */
	protected $account_fetch_fails = false;

	/** @var string[] Page tokens fetched, '' for the first page. */
	protected $fetched = [];

	/** @var callable[] Called while fetching the page of a token, e.g. to run another process meanwhile. */
	protected $during_fetch = [];

	/** @var array Transients set, keyed by name. */
	protected $transients = [];

	/** @var int[] Products by name: A, B, C (no longer in Merchant Center), V (variable) with V1 and V2. */
	protected $ids = [];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wpdb;

		$this->issue_table = new MerchantIssueTable( new WP(), $wpdb );
		$this->issue_table->install();

		$account_issues = $this->createMock( MapiAccountIssuesService::class );
		$account_issues->method( 'get_account_issues' )->willReturnCallback(
			function () {
				if ( $this->account_fetch_fails ) {
					throw new Exception( 'Account issues unavailable.' );
				}

				return [
					[
						'name'  => 'accounts/1/issues/new-account-issue',
						'title' => 'New account issue',
					],
				];
			}
		);

		$woogle          = woogle_get_container();
		$this->container = new Container();
		$this->container->addShared( ProductRepository::class, $woogle->get( ProductRepository::class ) );
		$this->container->addShared( ProductHelper::class, $woogle->get( ProductHelper::class ) );
		$this->container->addShared( BatchProductHelper::class, $woogle->get( BatchProductHelper::class ) );
		$this->container->addShared( ProductMetaQueryHelper::class, new ProductMetaQueryHelper( $wpdb ) );
		$this->container->addShared( MerchantIssueTable::class, $this->issue_table );
		$transients = $this->createMock( TransientsInterface::class );
		$transients->method( 'set' )->willReturnCallback(
			function ( $name, $value ) {
				$this->transients[ $name ] = $value;
				return true;
			}
		);
		$this->container->addShared( TransientsInterface::class, $transients );
		$this->container->addShared( MapiAccountIssuesService::class, $account_issues );
		$this->container->add(
			MerchantIssueQuery::class,
			function () use ( $wpdb ) {
				return new MerchantIssueQuery( $wpdb, $this->issue_table );
			}
		);

		// The previous run, two days ago: every product approved with one issue, plus an account issue.
		foreach ( [ 'A', 'B', 'C' ] as $name ) {
			$this->ids[ $name ] = $this->synced_product( WC_Helper_Product::create_simple_product()->get_id() );
			$this->insert_issue( $this->ids[ $name ], "old_{$name}" );
		}
		$variable        = WC_Helper_Product::create_variation_product();
		$this->ids['V']  = $this->synced_product( $variable->get_id() );
		$this->ids['V1'] = $this->synced_product( $variable->get_children()[0] );
		$this->ids['V2'] = $this->synced_product( $variable->get_children()[1] );
		$this->insert_issue( 0, 'old_account_issue', MerchantStatuses::TYPE_ACCOUNT );
	}

	public function test_previous_results_stay_readable_until_the_run_replaces_them() {
		[ 'A' => $a, 'B' => $b, 'C' => $c, 'V' => $v, 'V1' => $v1, 'V2' => $v2 ] = $this->ids;
		$this->pages = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'disapprovedCountries', [ 'new_A' => [ 'US' ] ] ) ], 'p2' ),
			'p2' => $this->page_of(
				[
					$this->mapi_product( $b, 'pendingCountries', [ 'new_B' => [ 'US' ] ] ),
					$this->mapi_product( $v1, 'approvedCountries' ),
					$this->mapi_product( $v2, 'approvedCountries' ),
				]
			),
		];

		$next = $this->run_page( null );

		// Mid-run: page 1 replaced A and the account issues; B and C still read as before.
		$this->assertSame( MCStatus::DISAPPROVED, $this->mc_status( $a ) );
		$this->assertSame( [ 'new_A' ], $this->issue_codes( $a ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $b ) );
		$this->assertSame( [ 'old_B' ], $this->issue_codes( $b ) );
		$this->assertSame( [ 'old_C' ], $this->issue_codes( $c ) );
		$this->assertSame( [ 'new_account_issue' ], $this->issue_codes( 0 ) );
		// Read through WooCommerce, priming its product meta cache before completion.
		$this->assertSame( MCStatus::APPROVED, wc_get_product( $c )->get_meta( $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ) ) );

		global $wpdb;
		$counts = ( new ProductJourney( $wpdb ) )->get_counts();
		$this->assertSame( 3, $counts[ ProductJourney::APPROVED ] );
		$this->assertSame( 1, $counts[ ProductJourney::DISAPPROVED ] );

		// Page 2 runs in a later request, and completes the run.
		$this->assertNull( $this->run_page( $next ) );

		$this->assertSame( MCStatus::DISAPPROVED, $this->mc_status( $a ) );
		$this->assertSame( [ 'new_A' ], $this->issue_codes( $a ) );
		$this->assertSame( MCStatus::PENDING, $this->mc_status( $b ) );
		$this->assertSame( [ 'new_B' ], $this->issue_codes( $b ) );
		$this->assertSame( [ 'new_account_issue' ], $this->issue_codes( 0 ) );
		// The variable product and its variations, written on page 2, keep their status.
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $v ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $v1 ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $v2 ) );
		// C was not in this run: its status and issues are gone, including from Woo's cache.
		$this->assertSame( '', $this->mc_status( $c ) );
		$this->assertSame( '', wc_get_product( $c )->get_meta( $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ) ) );
		$this->assertSame( [], $this->issue_codes( $c ) );
	}

	public function test_a_run_failing_on_the_account_fetch_keeps_the_previous_results() {
		$this->account_fetch_fails = true;
		$this->pages               = [ '' => $this->page_of( [] ) ];

		try {
			$this->run_page( null );
			$this->fail( 'The failed fetch should throw.' );
		} catch ( JobException $e ) {
			$this->assertStringContainsString( 'Account issues unavailable.', $e->getMessage() );
		}

		foreach ( [ 'A', 'B', 'C' ] as $name ) {
			$this->assertSame( MCStatus::APPROVED, $this->mc_status( $this->ids[ $name ] ) );
			$this->assertSame( [ "old_{$name}" ], $this->issue_codes( $this->ids[ $name ] ) );
		}
		$this->assertSame( [ 'old_account_issue' ], $this->issue_codes( 0 ) );
	}

	public function test_a_page_failing_in_the_same_request_keeps_earlier_pages_and_the_retry_completes() {
		[ 'A' => $a, 'B' => $b, 'C' => $c ] = $this->ids;
		$this->pages                        = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'disapprovedCountries', [ 'new_A' => [ 'US' ] ] ) ], 'p2' ),
			'p2' => new Exception( 'Google is unavailable.' ),
		];

		// Pages 1 and 2 run in the same request, on the same instances.
		$job  = $this->job();
		$next = $this->run_page( null, $job );
		try {
			$this->run_page( $next, $job );
			$this->fail( 'The failed page should throw.' );
		} catch ( JobException $e ) {
			$this->assertSame( [ 'new_A' ], $this->issue_codes( $a ) );
			$this->assertSame( [ 'old_B' ], $this->issue_codes( $b ) );
			$this->assertSame( MCStatus::APPROVED, $this->mc_status( $c ) );
		}

		// Action Scheduler retries page 2 later, and it succeeds.
		$this->pages['p2'] = $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] );
		$this->assertNull( $this->run_page( $next ) );

		$this->assertSame( MCStatus::DISAPPROVED, $this->mc_status( $a ) );
		$this->assertSame( [ 'new_A' ], $this->issue_codes( $a ) );
		$this->assertSame( [], $this->issue_codes( $b ) );
		$this->assertSame( '', $this->mc_status( $c ) );
	}

	public function test_a_page_in_a_process_started_before_the_run_keeps_its_issues() {
		[ 'A' => $a, 'B' => $b ] = $this->ids;
		$this->pages             = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'p2' ),
			'p2' => $this->page_of( [ $this->mapi_product( $b, 'disapprovedCountries', [ 'new_B' => [ 'US' ] ] ) ] ),
		];
		$next                    = $this->run_page( null );

		// Another queue runner, started 20 seconds before the run, runs page 2.
		$this->assertNull( $this->run_page( $next, $this->job( $next['run_started_at'] - 20 ) ) );

		$this->assertSame( MCStatus::DISAPPROVED, $this->mc_status( $b ) );
		$this->assertSame( [ 'new_B' ], $this->issue_codes( $b ) );
	}

	public function test_a_run_in_a_process_older_than_the_previous_run_replaces_it() {
		[ 'A' => $a ] = $this->ids;
		$this->pages  = [ '' => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ] ) ];

		// A long-lived runner, started before the previous run wrote its rows.
		$this->assertNull( $this->run_page( null, $this->job( time() - 3 * DAY_IN_SECONDS ) ) );

		$this->assertSame( [], $this->issue_codes( $a ) );
		$this->assertSame( [ 'new_account_issue' ], $this->issue_codes( 0 ) );
		$this->assertSame( '', $this->mc_status( $this->ids['B'] ) );
	}

	public function test_two_runs_in_the_same_process_each_replace_the_previous_one() {
		[ 'A' => $a, 'B' => $b ] = $this->ids;
		$job                     = $this->job();

		$this->pages = [ '' => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries', [ 'new_A' => [ 'US' ] ] ), $this->mapi_product( $b, 'approvedCountries' ) ] ) ];
		$this->assertNull( $this->run_page( null, $job ) );
		$this->pages = [ '' => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ] ) ];
		$this->assertNull( $this->run_page( null, $job ) );

		$this->assertSame( [], $this->issue_codes( $a ) );
		$this->assertSame( '', $this->mc_status( $b ) );
		$this->assertSame( [ 'new_account_issue' ], $this->issue_codes( 0 ) );
	}

	public function test_a_superseded_run_stops_and_the_newer_run_completes() {
		[ 'A' => $a, 'B' => $b ] = $this->ids;
		$this->pages             = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'p2' ),
			'p2' => $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] ),
		];

		$older = $this->run_page( null );
		$newer = $this->run_page( null );
		$this->assertGreaterThan( $older['run_started_at'], $newer['run_started_at'] );

		// The older run's page 2 neither writes nor schedules anything.
		$this->assertNull( $this->run_page( $older ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $this->ids['C'] ) );

		$this->assertNull( $this->run_page( $newer ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $a ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $b ) );
		$this->assertSame( '', $this->mc_status( $this->ids['C'] ) );
	}

	public function test_an_issue_on_several_pages_keeps_the_countries_of_this_run_only() {
		[ 'A' => $a ] = $this->ids;
		update_post_meta(
			$a,
			$this->prefix_meta_key( ProductMetaHandler::KEY_GOOGLE_IDS ),
			[
				'US' => "en~US~gla_{$a}",
				'CA' => "en~CA~gla_{$a}",
			]
		);
		$this->insert_issue( $a, 'shared', MerchantStatuses::TYPE_PRODUCT, [ 'US', 'GB' ] );

		$this->pages = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries', [ 'shared' => [ 'US' ] ] ) ], 'p2' ),
			'p2' => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries', [ 'shared' => [ 'CA' ] ], 'en~CA' ) ] ),
		];
		$this->assertNull( $this->run_page( $this->run_page( null ) ) );

		global $wpdb;
		$countries = $wpdb->get_col(
			$wpdb->prepare( 'SELECT applicable_countries FROM %i WHERE product_id = %d', $this->issue_table->get_name(), $a )
		);
		$this->assertSame( [ '["CA","US"]' ], $countries );
	}

	public function test_custom_issues_are_validated_and_stamped_with_the_run() {
		$filter   = function () {
			return [
				[
					'code'       => 'custom_issue',
					'issue'      => 'Custom issue',
					'type'       => MerchantStatuses::TYPE_ACCOUNT,
					'source'     => 'filter',
					// A callback's own date must not get the row treated as the previous run's.
					'created_at' => '2001-01-01 00:00:00',
				],
				[ 'code' => 'no_issue_text' ],
				'not a row',
			];
		};
		$messages = [];
		$debug    = function ( $message ) use ( &$messages ) {
			$messages[] = $message;
		};
		add_filter( 'woocommerce_gla_custom_merchant_issues', $filter );
		add_action( 'woocommerce_gla_debug_message', $debug );
		$this->pages = [ '' => $this->page_of( [] ) ];

		try {
			$this->run_page( null );
		} finally {
			remove_filter( 'woocommerce_gla_custom_merchant_issues', $filter );
			remove_action( 'woocommerce_gla_debug_message', $debug );
		}

		$this->assertContains( '2 custom merchant issue(s) were ignored because they have no issue text.', $messages );

		global $wpdb;
		$rows       = $wpdb->get_results(
			$wpdb->prepare( 'SELECT code, created_at FROM %i WHERE source = %s', $this->issue_table->get_name(), 'filter' ),
			ARRAY_A
		);
		$started_at = (int) woogle_get_container()->get( OptionsInterface::class )->get( OptionsInterface::MC_STATUSES_RUN_STARTED_AT );
		$this->assertSame(
			[
				[
					'code'       => 'custom_issue',
					'created_at' => gmdate( 'Y-m-d H:i:s', $started_at ),
				],
			],
			$rows
		);
	}

	public function test_statuses_are_kept_when_the_run_could_not_stamp_them() {
		[ 'A' => $a, 'C' => $c ] = $this->ids;
		$this->pages             = [ '' => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ] ) ];

		global $wpdb;
		$break_stamps = function ( $query ) {
			return 0 === strpos( ltrim( $query ), 'INSERT' ) && false !== strpos( $query, ProductMetaHandler::KEY_MC_STATUS_RUN ) ? 'INSERT INTO gla_no_such_table VALUES (1)' : $query;
		};
		add_filter( 'query', $break_stamps );
		$suppress = $wpdb->suppress_errors( true );
		try {
			$this->assertNull( $this->run_page( null ) );
		} finally {
			remove_filter( 'query', $break_stamps );
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertSame( 1, did_action( 'woocommerce_gla_error' ) );
		// Without stamps, completion cannot tell which products are gone, so it keeps
		// their statuses and the issues that explain them.
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $c ) );
		$this->assertSame( [ 'old_C' ], $this->issue_codes( $c ) );
	}

	public function test_a_run_started_before_the_upgrade_completes_without_removing_statuses() {
		[ 'B' => $b, 'C' => $c ] = $this->ids;
		$this->pages             = [ 'p2' => $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] ) ];

		// Its first page wiped the previous results under the old code; the later page
		// carries no run start, so this run cannot tell what it refreshed.
		$this->assertNull( $this->run_page( [ 'next_page_token' => 'p2' ] ) );

		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $b ) );
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $c ) );
	}

	public function test_a_long_lived_process_sees_that_a_newer_run_started() {
		[ 'A' => $a, 'B' => $b, 'C' => $c ] = $this->ids;
		$this->pages                        = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'x2' ),
			'x2' => $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ], 'x3' ),
			'x3' => $this->page_of( [] ),
		];
		// One long-lived runner processes run X, keeping its options cached in memory.
		$runner = $this->job( null, $this->process_options() );
		$older  = $this->run_page( $this->run_page( null, $runner ), $runner );

		// Another process starts run Y.
		$this->pages['']   = $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ), $this->mapi_product( $c, 'approvedCountries' ) ], 'y2' );
		$this->pages['y2'] = $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] );
		$newer             = $this->run_page( null, $this->job( null, $this->process_options() ) );

		$this->fetched = [];
		$this->assertNull( $this->run_page( $older, $runner ) );
		$this->assertSame( [], $this->fetched, 'The superseded page stops before fetching.' );

		$this->assertNull( $this->run_page( $newer ) );
		$this->assertSame( [ MCStatus::APPROVED, MCStatus::APPROVED, MCStatus::APPROVED ], [ $this->mc_status( $a ), $this->mc_status( $b ), $this->mc_status( $c ) ] );
	}

	public function test_a_run_superseded_while_its_last_page_is_fetched_leaves_the_newer_run_alone() {
		[ 'A' => $a, 'B' => $b, 'C' => $c ] = $this->ids;
		$this->pages                        = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'x2' ),
			'x2' => $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] ),
		];
		$older                              = $this->run_page( null );

		// While run X fetches its last page, another process starts run Y.
		$newer                    = null;
		$this->during_fetch['x2'] = function () use ( &$newer, $a, $b, $c ) {
			$pages           = $this->pages;
			$this->pages     = [ '' => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ), $this->mapi_product( $b, 'approvedCountries' ), $this->mapi_product( $c, 'approvedCountries' ) ], 'y2' ) ];
			$scheduled       = $this->scheduled;
			$newer           = $this->run_page( null, $this->job( null, $this->process_options() ) );
			$this->scheduled = $scheduled;
			$this->pages     = $pages + [ 'y2' => $this->page_of( [] ) ];
		};
		$this->assertNull( $this->run_page( $older ) );

		// X neither wrote its page nor removed what Y wrote.
		$this->assertSame( MCStatus::APPROVED, $this->mc_status( $c ) );
		$this->assertSame( (string) $newer['run_started_at'], get_post_meta( $c, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS_RUN ), true ) );

		$this->assertNull( $this->run_page( $newer ) );
		$this->assertSame( [ MCStatus::APPROVED, MCStatus::APPROVED, MCStatus::APPROVED ], [ $this->mc_status( $a ), $this->mc_status( $b ), $this->mc_status( $c ) ] );
	}

	public function test_a_page_failing_to_fetch_keeps_the_counts_of_earlier_pages_for_the_retry() {
		[ 'A' => $a, 'B' => $b ] = $this->ids;
		$this->pages             = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'p2' ),
			'p2' => new Exception( 'Google is unavailable.' ),
		];
		$next                    = $this->run_page( null );

		try {
			$this->run_page( $next );
			$this->fail( 'The failed page should throw.' );
		} catch ( JobException $e ) {
			$counts = woogle_get_container()->get( OptionsInterface::class )->get( OptionsInterface::PRODUCT_STATUSES_COUNT_INTERMEDIATE_DATA );
			$this->assertSame( 1, $counts[ MCStatus::APPROVED ] );
		}

		$this->pages['p2'] = $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] );
		$this->assertNull( $this->run_page( $next ) );

		$this->assertSame( 2, $this->transients[ TransientsInterface::MC_STATUSES ]['statistics'][ MCStatus::APPROVED ] );
	}

	public function test_a_custom_issue_without_a_source_is_replaced_on_the_first_page() {
		$filter = function ( $issues ) {
			$issues[] = [ 'issue' => 'Third-party issue without type, source or code' ];
			return $issues;
		};
		add_filter( 'woocommerce_gla_custom_merchant_issues', $filter );
		$this->pages = [
			''   => $this->page_of( [], 'p2' ),
			'p2' => $this->page_of( [] ),
		];

		try {
			$this->assertNull( $this->run_page( $this->run_page( null ) ) );
			$next = $this->run_page( null );
			$this->assertSame( 1, $this->count_issues( 'Third-party issue without type, source or code' ), 'Not duplicated mid-run.' );
			$this->assertNull( $this->run_page( $next ) );
		} finally {
			remove_filter( 'woocommerce_gla_custom_merchant_issues', $filter );
		}

		$this->assertSame( 1, $this->count_issues( 'Third-party issue without type, source or code' ) );
	}

	public function test_an_older_runs_cleanup_spares_a_newer_run_started_just_before_it() {
		[ 'A' => $a, 'B' => $b, 'C' => $c ] = $this->ids;

		$this->pages = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'x2' ),
			'x2' => $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ] ),
		];
		$older_job   = $this->job( null, $this->process_options() );
		$older       = $this->run_page( null, $older_job );

		// Run X's last page passes every check; then, as X starts removing what it did
		// not refresh, run Y's first page runs in another process.
		$newer       = null;
		$start_newer = function ( $query ) use ( &$newer, &$start_newer, $c ) {
			$is_cleanup = false !== strpos( $query, 'WHERE `created_at` <' ) && false === strpos( $query, 'AND NOT' );
			if ( null === $newer && $is_cleanup ) {
				remove_filter( 'query', $start_newer );
				$pages           = $this->pages;
				$scheduled       = $this->scheduled;
				$this->pages     = [ '' => $this->page_of( [ $this->mapi_product( $c, 'disapprovedCountries', [ 'new_C' => [ 'US' ] ] ) ], 'y2' ) ];
				$newer           = $this->run_page( null, $this->job( null, $this->process_options() ) );
				$this->scheduled = $scheduled;
				$this->pages     = $pages;
			}
			return $query;
		};
		add_filter( 'query', $start_newer );
		try {
			$this->assertNull( $this->run_page( $older, $older_job ) );
		} finally {
			remove_filter( 'query', $start_newer );
		}

		// Y's status, stamp, issues and counts survive X's completion.
		$this->assertNotNull( $newer );
		$this->assertSame( MCStatus::DISAPPROVED, $this->mc_status( $c ) );
		$this->assertSame( (string) $newer['run_started_at'], get_post_meta( $c, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS_RUN ), true ) );
		$this->assertSame( [ 'new_C' ], $this->issue_codes( $c ) );
		$this->assertSame( [ 'new_account_issue' ], $this->issue_codes( 0 ) );

		$this->pages['y2'] = $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ] );
		$this->assertNull( $this->run_page( $newer ) );
		$this->assertSame( 1, $this->transients[ TransientsInterface::MC_STATUSES ]['statistics'][ MCStatus::DISAPPROVED ] );
		$this->assertSame( 1, $this->transients[ TransientsInterface::MC_STATUSES ]['statistics'][ MCStatus::APPROVED ] );
	}

	public function test_a_long_lived_process_counts_the_pages_other_processes_ran() {
		[ 'A' => $a, 'B' => $b, 'C' => $c ] = $this->ids;

		$this->pages = [
			''   => $this->page_of( [ $this->mapi_product( $a, 'approvedCountries' ) ], 'p2' ),
			'p2' => $this->page_of( [ $this->mapi_product( $b, 'approvedCountries' ) ], 'p3' ),
			'p3' => $this->page_of( [ $this->mapi_product( $c, 'approvedCountries' ) ] ),
		];
		// A long-lived runner runs pages 1 and 3 with its options cached in memory;
		// another process runs page 2 in between.
		$runner_options = $this->process_options();
		$page_2         = $this->run_page( null, $this->job( null, $runner_options ) );
		$page_3         = $this->run_page( $page_2, $this->job( null, $this->process_options() ) );
		$this->assertNull( $this->run_page( $page_3, $this->job( null, $runner_options ) ) );

		$this->assertSame( 3, $this->transients[ TransientsInterface::MC_STATUSES ]['statistics'][ MCStatus::APPROVED ] );
	}

	/**
	 * The job and MerchantStatuses of one request.
	 *
	 * @param int|null              $process_started_at When the PHP process built its MerchantStatuses, if not now.
	 * @param OptionsInterface|null $options            The process's own options service (and its in-memory cache).
	 *
	 * @return UpdateMerchantProductStatuses
	 */
	protected function job( ?int $process_started_at = null, ?OptionsInterface $options = null ): UpdateMerchantProductStatuses {
		$merchant_statuses = new MerchantStatuses();
		$merchant_statuses->set_container( $this->container );
		$merchant_statuses->set_options_object( $options ?? woogle_get_container()->get( OptionsInterface::class ) );

		if ( null !== $process_started_at ) {
			$created = new ReflectionProperty( MerchantStatuses::class, 'cache_created_time' );
			$created->setAccessible( true );
			$created->setValue( $merchant_statuses, ( new DateTime() )->setTimestamp( $process_started_at ) );
		}

		$action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		$action_scheduler->method( 'schedule_immediate' )->willReturnCallback(
			function ( $hook, $args ) {
				$this->scheduled[] = $args[0];
				return 1;
			}
		);

		$merchant_center = $this->createMock( MerchantCenterService::class );
		$merchant_center->method( 'is_connected' )->willReturn( true );

		$products = $this->createMock( MapiProductsService::class );
		$products->method( 'list_page' )->willReturnCallback(
			function ( $token ) {
				$this->fetched[] = $token ?? '';
				if ( isset( $this->during_fetch[ $token ?? '' ] ) ) {
					$during = $this->during_fetch[ $token ?? '' ];
					unset( $this->during_fetch[ $token ?? '' ] );
					$during();
				}

				$page = $this->pages[ $token ?? '' ];
				if ( $page instanceof Exception ) {
					throw $page;
				}
				return $page;
			}
		);

		return new UpdateMerchantProductStatuses(
			$action_scheduler,
			$this->createMock( ActionSchedulerJobMonitor::class ),
			$merchant_center,
			$products,
			$merchant_statuses
		);
	}

	/**
	 * The options service of another PHP process, with its own in-memory cache.
	 *
	 * @return OptionsInterface
	 */
	protected function process_options(): OptionsInterface {
		$options = new Options();
		$options->set_wp_proxy_object( new WP() );

		return $options;
	}

	/**
	 * Process one page.
	 *
	 * @param array|null                         $items Page items, null for the first page.
	 * @param UpdateMerchantProductStatuses|null $job   The request's job, or a new request.
	 *
	 * @return array|null Items of the next page the job scheduled, or null.
	 */
	protected function run_page( ?array $items, ?UpdateMerchantProductStatuses $job = null ): ?array {
		$this->scheduled = [];
		( $job ?? $this->job() )->process_items( $items ?? [] );

		return $this->scheduled ? end( $this->scheduled ) : null;
	}

	/**
	 * @param Product[]   $products
	 * @param string|null $next_page_token
	 *
	 * @return array
	 */
	protected function page_of( array $products, ?string $next_page_token = null ): array {
		return [
			'products'        => $products,
			'next_page_token' => $next_page_token,
		];
	}

	/**
	 * A Merchant API product with one destination status and its issues.
	 *
	 * @param int      $id
	 * @param string   $countries_key approvedCountries, pendingCountries or disapprovedCountries.
	 * @param string[] $issues        Countries keyed by issue code.
	 * @param string   $feed          Language and feed label of the entry.
	 *
	 * @return Product
	 */
	protected function mapi_product( int $id, string $countries_key, array $issues = [], string $feed = 'en~US' ): Product {
		$item_level_issues = [];
		foreach ( $issues as $code => $countries ) {
			$item_level_issues[] = [
				'code'                => $code,
				'description'         => "Issue {$code}",
				'resolution'          => 'merchant_action',
				'severity'            => 'DISAPPROVED',
				'applicableCountries' => $countries,
			];
		}

		return Product::from_array(
			[
				'name'          => "accounts/1/products/{$feed}~gla_{$id}",
				'productStatus' => [
					'destinationStatuses' => [ [ $countries_key => [ 'US' ] ] ],
					'itemLevelIssues'     => $item_level_issues,
				],
			]
		);
	}

	/**
	 * Mark a product synced and approved by the previous run.
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	protected function synced_product( int $id ): int {
		update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ), SyncStatus::SYNCED );
		update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_GOOGLE_IDS ), [ 'US' => "en~US~gla_{$id}" ] );
		update_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), MCStatus::APPROVED );

		return $id;
	}

	/**
	 * Store an issue written by the previous run, two days ago.
	 *
	 * @param int      $product_id
	 * @param string   $code
	 * @param string   $type
	 * @param string[] $countries
	 */
	protected function insert_issue( int $product_id, string $code, string $type = MerchantStatuses::TYPE_PRODUCT, array $countries = [ 'US' ] ): void {
		global $wpdb;

		$wpdb->insert(
			$this->issue_table->get_name(),
			[
				'product_id'           => $product_id,
				'code'                 => $code,
				'issue'                => "Issue {$code}",
				'severity'             => 'error',
				'product'              => 'Product',
				'action'               => '',
				'action_url'           => '',
				'applicable_countries' => wp_json_encode( $countries ),
				'source'               => 'mc',
				'type'                 => $type,
				'created_at'           => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ),
			]
		);
	}

	/**
	 * @param string $issue
	 *
	 * @return int Rows stored with this issue text.
	 */
	protected function count_issues( string $issue ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE issue = %s', $this->issue_table->get_name(), $issue )
		);
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	protected function mc_status( int $id ): string {
		return (string) get_post_meta( $id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), true );
	}

	/**
	 * Issue codes stored for a product (0 for account issues), sorted.
	 *
	 * @param int $product_id
	 *
	 * @return string[]
	 */
	protected function issue_codes( int $product_id ): array {
		global $wpdb;

		return array_values(
			(array) $wpdb->get_col(
				$wpdb->prepare( 'SELECT code FROM %i WHERE product_id = %d ORDER BY code', $this->issue_table->get_name(), $product_id )
			)
		);
	}
}
