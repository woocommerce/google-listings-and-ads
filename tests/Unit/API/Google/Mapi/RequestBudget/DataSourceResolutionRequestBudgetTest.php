<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\Create;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class DataSourceResolutionRequestBudgetTest
 *
 * Fixed request-count regression suite for data source resolution during a product
 * write.
 *
 * ProductUpsertRequestBudgetTest proves insert_many()/delete_many() batch the write
 * itself at ceil(N / batch_size), but it stubs MapiDataSourcesService out entirely, so
 * it says nothing about the traffic `ensure_data_source_for()` generates - and that
 * method is called once per input, upfront, inside both of those loops (see
 * MapiProductInputsService::insert_many()/delete_many()). MapiDataSourcesService only
 * keeps that flat because of two caches: a persisted option (survives across a job's
 * batches) and an in-memory "verified this request" set (survives across a single
 * insert_many()/delete_many() call). Weakening either - e.g. no longer sharing the
 * service as a singleton, or re-verifying an already-verified name - would turn a
 * single-market write of N products back into N `dataSources.get`/`.list` calls
 * without either existing suite noticing. These tests wire the real
 * MapiDataSourcesService (backed by a mocked MerchantApiClient) into a real
 * MapiProductInputsService and assert resolution traffic tracks the number of
 * distinct (contentLanguage, feedLabel) pairs touched, not the number of products.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget
 */
class DataSourceResolutionRequestBudgetTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const LIST_PATH   = 'datasources/v1/accounts/12345/dataSources';
	protected const SOURCE_NAME = 'accounts/12345/dataSources/777';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiDataSourcesService */
	protected $data_sources;

	/** @var MapiProductInputsService */
	protected $service;

	/** @var array Backing store for the stateful options mock, keyed like a real wp_option. */
	protected $stored_options;

	public function setUp(): void {
		parent::setUp();

		$this->stored_options = [];

		$this->client = $this->createMock( MerchantApiClient::class );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );
		// Stateful, so a resolution's cache write is visible to the next resolution in
		// the same test - exactly like the real wp_options-backed implementation.
		$this->options->method( 'get' )->willReturnCallback(
			function ( string $key, $fallback = false ) {
				return $this->stored_options[ $key ] ?? $fallback;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( string $key, $value ) {
				$this->stored_options[ $key ] = $value;
				return true;
			}
		);

		// The real resolver, not a mock: this suite exists to exercise its caching,
		// not to assume it.
		$this->data_sources = new MapiDataSourcesService( $this->client );
		$this->data_sources->set_options_object( $this->options );

		$this->service = new MapiProductInputsService( $this->client, $this->data_sources );
		$this->service->set_options_object( $this->options );

		// Default fallback for any get()/post() this test doesn't explicitly assert
		// on (e.g. a warm-up insert_many() call before the real assertions are set
		// up): every market "discovers" no existing data source and creates a fresh
		// one. Every test that also places an expects() count assertion on get()/
		// post() re-chains this same non-empty return explicitly, so which
		// configured matcher PHPUnit picks for the return value never matters - a
		// resolved name is never empty, which matters because ensure_data_source()
		// treats a cached empty string as "not cached" and would silently re-resolve.
		$this->client->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->method( 'post' )->willReturn( [ 'name' => self::SOURCE_NAME ] );
		$this->client->method( 'batch_async' )->willReturnCallback( [ $this, 'respond_ok_to_every_sub_request' ] );
	}

	/**
	 * @return array<string, array{0: int}>
	 */
	public function product_counts(): array {
		return [
			'a single product'  => [ 1 ],
			'a small batch'     => [ 50 ],
			'exactly one batch' => [ 100 ],
			'several batches'   => [ 250 ],
			'many batches'      => [ 500 ],
		];
	}

	/**
	 * @dataProvider product_counts
	 *
	 * @param int $n Number of products to write, all in the same (language, feed) market.
	 */
	public function test_resolution_request_count_stays_flat_as_product_count_scales_for_one_market( int $n ): void {
		// One market touched, however many products: discovery (list) and creation
		// happen exactly once each, verified by the in-memory cache for every input
		// after the first.
		$this->client->expects( $this->once() )->method( 'get' )->with( self::LIST_PATH )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->once() )->method( 'post' )->with( self::LIST_PATH )->willReturn( [ 'name' => self::SOURCE_NAME ] );
		$this->assert_client_never_calls_a_per_item_write_method();

		$result = $this->service->insert_many( $this->make_inputs( $n, 'en', 'US' ) );

		$this->assertCount( $n, $result['successes'] );
		$this->assertCount( 0, $result['failures'] );
	}

	/**
	 * @return array<string, array{0: int, 1: int}>
	 */
	public function market_and_product_counts(): array {
		return [
			'one market, a handful of products'  => [ 1, 3 ],
			'one market, many products'          => [ 1, 300 ],
			'three markets, one product each'    => [ 3, 3 ],
			'three markets, many products each'  => [ 3, 300 ],
			'ten markets, a handful of products' => [ 10, 20 ],
			'ten markets, many products'         => [ 10, 500 ],
		];
	}

	/**
	 * @dataProvider market_and_product_counts
	 *
	 * @param int $market_count   Number of distinct (contentLanguage, feedLabel) pairs.
	 * @param int $total_products Total products to write, split evenly across the markets.
	 */
	public function test_resolution_request_count_scales_with_market_count_not_product_count( int $market_count, int $total_products ): void {
		// Resolution traffic must track the number of distinct markets touched, never
		// the number of products in them: one list + one create per market. Every
		// market is stubbed to discover nothing and create the same fixture name -
		// harmless here, since each market still owes its own list+create on first
		// touch regardless of what any other market's create call returns.
		$this->client->expects( $this->exactly( $market_count ) )->method( 'get' )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->exactly( $market_count ) )->method( 'post' )->willReturn( [ 'name' => self::SOURCE_NAME ] );
		$this->assert_client_never_calls_a_per_item_write_method();

		$result = $this->service->insert_many( $this->make_inputs_across_markets( $total_products, $market_count ) );

		$this->assertCount( $total_products, $result['successes'] );
		$this->assertCount( 0, $result['failures'] );
	}

	/**
	 * @dataProvider product_counts
	 *
	 * @param int $n Number of products to delete, all in the same (language, feed) market.
	 */
	public function test_delete_many_resolution_request_count_stays_flat_for_one_market( int $n ): void {
		$this->client->expects( $this->once() )->method( 'get' )->with( self::LIST_PATH )->willReturn( [ 'dataSources' => [] ] );
		$this->client->expects( $this->once() )->method( 'post' )->with( self::LIST_PATH )->willReturn( [ 'name' => self::SOURCE_NAME ] );
		$this->assert_client_never_calls_a_per_item_write_method();

		$result = $this->service->delete_many( $this->make_inputs( $n, 'en', 'US' ) );

		$this->assertCount( $n, $result['successes'] );
		$this->assertCount( 0, $result['failures'] );
	}

	/**
	 * Two separate batches of the same job/request (e.g. two paged chunks of one
	 * sync run) sharing the DI container's one MapiDataSourcesService instance -
	 * as GoogleServiceProvider::share() guarantees in production - must not
	 * re-verify a market resolved by the first batch when the second batch writes
	 * into it. This is the in-memory `verified_data_sources` cache; see the sibling
	 * test below for the persisted option cache that protects a later request.
	 */
	public function test_a_second_batch_in_the_same_run_never_re_verifies_a_resolved_market(): void {
		$this->service->insert_many( $this->make_inputs( 1, 'en', 'US' ) );

		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->never() )->method( 'post' );

		$result = $this->service->insert_many( $this->make_inputs( 200, 'en', 'US' ) );

		$this->assertCount( 200, $result['successes'] );
	}

	/**
	 * A market resolved by a prior request/job run is only found via the persisted
	 * option cache on a fresh MapiDataSourcesService instance - as the container
	 * would construct on the next request. Without an in-memory verified-this-run
	 * hit yet, that fresh instance still owes exactly one verifying
	 * `dataSources.get`, but never re-lists or re-creates the market, however many
	 * products the new run writes into it.
	 */
	public function test_a_fresh_instance_sharing_the_persisted_cache_only_pays_one_verification(): void {
		$this->service->insert_many( $this->make_inputs( 1, 'en', 'US' ) );

		// A fresh resolver and service, as the container would build for the next
		// request, wired to the same (now warm) persisted option cache.
		$fresh_data_sources = new MapiDataSourcesService( $this->client );
		$fresh_data_sources->set_options_object( $this->options );
		$fresh_service = new MapiProductInputsService( $this->client, $fresh_data_sources );
		$fresh_service->set_options_object( $this->options );

		// The verifying get()'s response content is irrelevant (it only needs to not
		// throw), but a valid return is chained anyway so no ambiguity about which
		// configured matcher supplies it can matter.
		$this->client->expects( $this->once() )->method( 'get' )->willReturn( [ 'name' => self::SOURCE_NAME ] );
		$this->client->expects( $this->never() )->method( 'post' );

		$result = $fresh_service->insert_many( $this->make_inputs( 200, 'en', 'US' ) );

		$this->assertCount( 200, $result['successes'] );
	}

	/**
	 * Mocked batch_async() handler: every sub-request in the batch succeeds.
	 *
	 * @param array<int, array{method: string, path: string, body?: array}> $requests
	 *
	 * @return \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\PromiseInterface
	 */
	public function respond_ok_to_every_sub_request( array $requests ) {
		$results = [];
		foreach ( $requests as $index => $sub ) {
			$offer_id          = $sub['body']['offerId'] ?? ( 'item' . $index );
			$results[ $index ] = [
				'status' => 200,
				'body'   => [
					'name'    => 'accounts/' . self::MERCHANT_ID . '/productInputs/' . $offer_id,
					'offerId' => $offer_id,
				],
			];
		}

		return Create::promiseFor( $results );
	}

	/**
	 * @param int    $n                N products to create.
	 * @param string $content_language
	 * @param string $feed_label
	 *
	 * @return ProductInput[]
	 */
	protected function make_inputs( int $n, string $content_language, string $feed_label ): array {
		return array_map(
			static function ( int $i ) use ( $content_language, $feed_label ) {
				return new ProductInput( 'sku' . $content_language . $feed_label . $i, $content_language, $feed_label, [ 'title' => 'Product ' . $i ] );
			},
			range( 1, $n )
		);
	}

	/**
	 * Build $total_products products split as evenly as possible across
	 * $market_count distinct (contentLanguage, feedLabel) pairs.
	 *
	 * @param int $total_products
	 * @param int $market_count
	 *
	 * @return ProductInput[]
	 */
	protected function make_inputs_across_markets( int $total_products, int $market_count ): array {
		$inputs = [];
		for ( $i = 0; $i < $total_products; $i++ ) {
			$market            = $i % $market_count;
			$content_language  = 'l' . $market;
			$feed_label        = 'F' . $market;
			$inputs[]          = new ProductInput( 'sku' . $market . '-' . $i, $content_language, $feed_label, [ 'title' => 'Product ' . $i ] );
		}

		return $inputs;
	}

	protected function assert_client_never_calls_a_per_item_write_method(): void {
		$this->client->expects( $this->never() )->method( 'patch' );
		$this->client->expects( $this->never() )->method( 'get_async' );
		$this->client->expects( $this->never() )->method( 'delete' );
		$this->client->expects( $this->never() )->method( 'request' );
		$this->client->expects( $this->never() )->method( 'request_async' );
	}
}
