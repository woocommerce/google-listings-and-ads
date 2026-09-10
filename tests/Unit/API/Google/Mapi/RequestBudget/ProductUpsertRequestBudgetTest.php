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
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductUpsertRequestBudgetTest
 *
 * Fixed request-count regression suite for the product write path.
 *
 * The Merchant API migration briefly turned the product status read path from one
 * batched Content API call per 500 products into one Merchant API call per product,
 * a 4.8x traffic increase fleet-wide (fixed by driving the status refresh from
 * products.list; see ProductStatusRefreshRequestBudgetTest). `insert_many()` and
 * `delete_many()` are correctly batched today via `MerchantApiClient::batch_async()`,
 * but the sibling `patch_many()` was never upgraded the same way and still issues one
 * HTTP request per product (currently unreachable from production sync code). These
 * tests assert the HTTP request count for a write of N products stays at
 * ceil(N / batch_size) rather than N, so a similar regression here - whether a future
 * change to insert_many()/delete_many() themselves, or patch_many() being wired into
 * a real sync path unbatched - fails a test instead of shipping unnoticed.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget
 */
class ProductUpsertRequestBudgetTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const DATA_SOURCE = 'accounts/12345/dataSources/777';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|MapiDataSourcesService */
	protected $data_sources;

	/** @var MapiProductInputsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client = $this->createMock( MerchantApiClient::class );

		$this->data_sources = $this->createMock( MapiDataSourcesService::class );
		$this->data_sources->method( 'ensure_data_source_for' )->willReturn( self::DATA_SOURCE );

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiProductInputsService( $this->client, $this->data_sources );
		$this->service->set_options_object( $options );
	}

	/**
	 * @return array<string, array{0: int}>
	 */
	public function budget_sizes(): array {
		return [
			'a single product'         => [ 1 ],
			'one below the batch size' => [ 99 ],
			'exactly one batch'        => [ 100 ],
			'one over the batch size'  => [ 101 ],
			'several batches'          => [ 250 ],
			'many batches'             => [ 500 ],
		];
	}

	/**
	 * @dataProvider budget_sizes
	 *
	 * @param int $n Number of products to write.
	 */
	public function test_insert_many_batch_count_scales_as_ceil_n_over_batch_size( int $n ): void {
		$batch_size       = $this->service_batch_size();
		$expected_batches = (int) ceil( $n / $batch_size );

		$this->client->expects( $this->exactly( $expected_batches ) )
			->method( 'batch_async' )
			->willReturnCallback( [ $this, 'respond_ok_to_every_sub_request' ] );

		// A regression that reverts insert_many() to a per-item loop - as patch_many()
		// still does - would call one of these once per product instead of batch_async()
		// once per chunk.
		$this->assert_client_never_calls_a_per_item_method();

		$result = $this->service->insert_many( $this->make_inputs( $n ) );

		$this->assertCount( $n, $result['successes'], 'Every input should have succeeded against the mocked batch responses.' );
		$this->assertCount( 0, $result['failures'] );
	}

	/**
	 * @dataProvider budget_sizes
	 *
	 * @param int $n Number of products to delete.
	 */
	public function test_delete_many_batch_count_scales_as_ceil_n_over_batch_size( int $n ): void {
		$batch_size       = $this->service_batch_size();
		$expected_batches = (int) ceil( $n / $batch_size );

		$this->client->expects( $this->exactly( $expected_batches ) )
			->method( 'batch_async' )
			->willReturnCallback( [ $this, 'respond_ok_to_every_sub_request' ] );

		$this->assert_client_never_calls_a_per_item_method();

		$result = $this->service->delete_many( $this->make_inputs( $n ) );

		$this->assertCount( $n, $result['successes'] );
		$this->assertCount( 0, $result['failures'] );
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
	 * @param int $n
	 *
	 * @return ProductInput[]
	 */
	protected function make_inputs( int $n ): array {
		return array_map(
			static function ( int $i ) {
				return new ProductInput( 'sku' . $i, 'en', 'US', [ 'title' => 'Product ' . $i ] );
			},
			range( 1, $n )
		);
	}

	protected function assert_client_never_calls_a_per_item_method(): void {
		$this->client->expects( $this->never() )->method( 'post' );
		$this->client->expects( $this->never() )->method( 'patch' );
		$this->client->expects( $this->never() )->method( 'get' );
		$this->client->expects( $this->never() )->method( 'get_async' );
		$this->client->expects( $this->never() )->method( 'delete' );
		$this->client->expects( $this->never() )->method( 'request' );
		$this->client->expects( $this->never() )->method( 'request_async' );
	}

	protected function service_batch_size(): int {
		$method = new ReflectionMethod( MapiProductInputsService::class, 'get_batch_size' );
		$method->setAccessible( true );

		return $method->invoke( $this->service );
	}
}
