<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInputPatch;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\Create;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiProductInputsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiProductInputsServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const DS_EN_US    = 'accounts/12345/dataSources/777';
	protected const DS_FR_CA    = 'accounts/12345/dataSources/888';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|MapiDataSourcesService */
	protected $data_sources;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiProductInputsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client       = $this->createMock( MerchantApiClient::class );
		$this->data_sources = $this->createMock( MapiDataSourcesService::class );
		$this->data_sources->method( 'ensure_data_source_for' )
			->willReturnCallback(
				function ( string $language, string $feed ) {
					if ( 'en' === $language && 'US' === $feed ) {
						return self::DS_EN_US;
					}
					if ( 'fr' === $language && 'CA' === $feed ) {
						return self::DS_FR_CA;
					}
					return 'accounts/12345/dataSources/unknown';
				}
			);

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiProductInputsService( $this->client, $this->data_sources );
		$this->service->set_options_object( $this->options );
	}

	protected function expected_path( string $data_source ): string {
		return 'products/v1/accounts/12345/productInputs:insert?dataSource=' . rawurlencode( $data_source );
	}

	protected function expected_patch_path( ProductInput $input, array $mask, string $data_source ): string {
		return sprintf(
			'products/v1/accounts/12345/productInputs/%s~%s~%s?dataSource=%s&updateMask=%s',
			$input->get_content_language(),
			$input->get_feed_label(),
			rawurlencode( $input->get_offer_id() ),
			rawurlencode( $data_source ),
			rawurlencode( implode( ',', $mask ) )
		);
	}

	protected function expected_delete_path( ProductInput $input, string $data_source ): string {
		return sprintf(
			'products/v1/accounts/12345/productInputs/%s~%s~%s?dataSource=%s',
			$input->get_content_language(),
			$input->get_feed_label(),
			rawurlencode( $input->get_offer_id() ),
			rawurlencode( $data_source )
		);
	}

	protected function make_input( string $offer_id = 'sku42', string $language = 'en', string $feed = 'US' ): ProductInput {
		return new ProductInput( $offer_id, $language, $feed, [ 'title' => 'Test' ] );
	}

	public function test_insert_resolves_data_source_from_input_and_posts() {
		$input = $this->make_input();

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->expected_path( self::DS_EN_US ), $input->to_array() )
			->willReturn(
				[
					'name'    => 'accounts/12345/productInputs/online~en~US~sku42',
					'offerId' => 'sku42',
				]
			);

		$result = $this->service->insert( $input );

		$this->assertInstanceOf( ProductInput::class, $result );
		$this->assertSame( 'accounts/12345/productInputs/online~en~US~sku42', $result->get_name() );
		$this->assertSame( 'sku42', $result->get_offer_id() );
	}

	public function test_insert_logs_the_payload_via_debug_message() {
		// The Merchant API push must be logged so syncs can be verified/troubleshot.
		$input = $this->make_input();
		$this->client->method( 'post' )->willReturn(
			[
				'name'    => 'accounts/12345/productInputs/online~en~US~sku42',
				'offerId' => 'sku42',
			]
		);

		$messages = [];
		$callback = static function ( $message ) use ( &$messages ) {
			$messages[] = $message;
		};
		add_action( 'woocommerce_gla_debug_message', $callback );

		$this->service->insert( $input );

		remove_action( 'woocommerce_gla_debug_message', $callback );

		$logged = implode( "\n", $messages );
		$this->assertStringContainsString( 'productInputs.insert sku42', $logged );
		$this->assertStringContainsString( '"title":"Test"', $logged );
	}

	public function test_insert_routes_different_market_to_a_different_data_source() {
		$input = $this->make_input( 'sku42', 'fr', 'CA' );

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->expected_path( self::DS_FR_CA ), $input->to_array() )
			->willReturn(
				[
					'name'    => 'accounts/12345/productInputs/online~fr~CA~sku42',
					'offerId' => 'sku42',
				]
			);

		$this->service->insert( $input );
	}

	public function test_insert_propagates_merchant_api_exception() {
		$this->client->method( 'post' )
			->willThrowException( new MerchantApiException( 400, [], __METHOD__ ) );

		$this->expectException( MerchantApiException::class );

		$this->service->insert( $this->make_input() );
	}

	public function test_insert_many_keys_successes_and_failures_by_index() {
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) {
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$offer_id = $sub['body']['offerId'];
						if ( 'bad' === $offer_id ) {
							$results[ $index ] = [
								'status' => 500,
								'body'   => [],
							];
						} else {
							$results[ $index ] = [
								'status' => 200,
								'body'   => [
									'name'    => 'accounts/12345/productInputs/' . $offer_id,
									'offerId' => $offer_id,
								],
							];
						}
					}

					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many(
			[
				$this->make_input( 'good1' ),
				$this->make_input( 'bad' ),
				$this->make_input( 'good2' ),
			]
		);

		$this->assertCount( 2, $result['successes'] );
		$this->assertCount( 1, $result['failures'] );
		$this->assertArrayHasKey( 0, $result['successes'] );
		$this->assertArrayHasKey( 2, $result['successes'] );
		$this->assertArrayHasKey( 1, $result['failures'] );
		$this->assertInstanceOf( ProductInput::class, $result['successes'][0] );
		$this->assertInstanceOf( MerchantApiException::class, $result['failures'][1] );
		$this->assertSame( 'good1', $result['successes'][0]->get_offer_id() );
	}

	public function test_insert_many_routes_each_input_to_its_own_data_source() {
		$paths_seen = [];

		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$paths_seen ) {
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$offer_id                = $sub['body']['offerId'];
						$paths_seen[ $offer_id ] = $sub['path'];
						$results[ $index ]       = [
							'status' => 200,
							'body'   => [
								'name'    => 'accounts/12345/productInputs/' . $offer_id,
								'offerId' => $offer_id,
							],
						];
					}

					return Create::promiseFor( $results );
				}
			);

		$this->service->insert_many(
			[
				$this->make_input( 'us_sku', 'en', 'US' ),
				$this->make_input( 'ca_sku', 'fr', 'CA' ),
			]
		);

		$this->assertSame( $this->expected_path( self::DS_EN_US ), $paths_seen['us_sku'] );
		$this->assertSame( $this->expected_path( self::DS_FR_CA ), $paths_seen['ca_sku'] );
	}

	public function test_insert_many_marks_missing_batch_subresponses_as_failures() {
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) {
					// Simulate the parser yielding no sub-response for 'bad'.
					$results = [];
					foreach ( $requests as $index => $sub ) {
						if ( 'bad' === $sub['body']['offerId'] ) {
							continue;
						}
						$results[ $index ] = [
							'status' => 200,
							'body'   => [ 'offerId' => $sub['body']['offerId'] ],
						];
					}

					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many(
			[
				$this->make_input( 'good1' ),
				$this->make_input( 'bad' ),
				$this->make_input( 'good2' ),
			]
		);

		$this->assertCount( 2, $result['successes'] );
		$this->assertCount( 1, $result['failures'] );
		$this->assertArrayHasKey( 1, $result['failures'] );
		// A missing sub-response is a retryable failure (>= 500), never a silent no-op.
		$this->assertGreaterThanOrEqual( 500, $result['failures'][1]->get_http_status() );
	}

	public function test_insert_many_marks_all_inputs_failed_when_the_batch_request_rejects() {
		$this->client->method( 'batch_async' )
			->willReturn( Create::rejectionFor( new MerchantApiException( 503, [], __METHOD__ ) ) );

		$result = $this->service->insert_many(
			[
				$this->make_input( 'a' ),
				$this->make_input( 'b' ),
				$this->make_input( 'c' ),
			]
		);

		$this->assertCount( 0, $result['successes'] );
		$this->assertCount( 3, $result['failures'] );
		$this->assertInstanceOf( MerchantApiException::class, $result['failures'][0] );
		$this->assertSame( 503, $result['failures'][0]->get_http_status() );
	}

	public function test_insert_many_chunks_into_multiple_batches_and_keys_by_original_index() {
		add_filter(
			'woocommerce_gla_mapi_batch_size',
			function () {
				return 2;
			}
		);

		$batch_calls = 0;
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$batch_calls ) {
					++$batch_calls;
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = [
							'status' => 200,
							'body'   => [ 'offerId' => $sub['body']['offerId'] ],
						];
					}

					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many(
			[
				$this->make_input( 's0' ),
				$this->make_input( 's1' ),
				$this->make_input( 's2' ),
				$this->make_input( 's3' ),
				$this->make_input( 's4' ),
			]
		);
		remove_all_filters( 'woocommerce_gla_mapi_batch_size' );

		// 5 inputs at batch_size 2 => 3 batches (2 + 2 + 1), demuxed back to the original indices.
		$this->assertSame( 3, $batch_calls );
		$this->assertCount( 5, $result['successes'] );
		foreach ( range( 0, 4 ) as $i ) {
			$this->assertArrayHasKey( $i, $result['successes'] );
			$this->assertSame( "s{$i}", $result['successes'][ $i ]->get_offer_id() );
		}
	}

	public function test_delete_many_ignores_a_subresponse_for_an_unrequested_id() {
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) {
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = [
							'status' => 200,
							'body'   => [],
						];
					}
					// A stray sub-response for an id that was never requested.
					$results[999] = [
						'status' => 200,
						'body'   => [],
					];

					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->delete_many( [ $this->make_input( 'a' ) ] );

		// The typed delete callback must not receive a null input: the stray id is ignored.
		$this->assertCount( 1, $result['successes'] );
		$this->assertArrayNotHasKey( 999, $result['successes'] );
	}

	public function test_patch_resolves_data_source_and_builds_correct_path() {
		$input = $this->make_input();
		$mask  = [ 'productAttributes.title' ];

		$this->client->expects( $this->once() )
			->method( 'patch' )
			->with( $this->expected_patch_path( $input, $mask, self::DS_EN_US ), $input->to_array() )
			->willReturn(
				[
					'name'    => 'accounts/12345/productInputs/en~US~sku42',
					'offerId' => 'sku42',
				]
			);

		$result = $this->service->patch( new ProductInputPatch( $input, $mask ) );

		$this->assertInstanceOf( ProductInput::class, $result );
		$this->assertSame( 'sku42', $result->get_offer_id() );
	}

	public function test_patch_routes_different_market_to_a_different_data_source() {
		$input = $this->make_input( 'sku42', 'fr', 'CA' );
		$mask  = [ 'productAttributes.title' ];

		$this->client->expects( $this->once() )
			->method( 'patch' )
			->with( $this->expected_patch_path( $input, $mask, self::DS_FR_CA ), $input->to_array() )
			->willReturn( [ 'offerId' => 'sku42' ] );

		$this->service->patch( new ProductInputPatch( $input, $mask ) );
	}

	public function test_patch_throws_on_empty_update_mask_without_http_call() {
		$this->client->expects( $this->never() )->method( 'patch' );
		$this->data_sources->expects( $this->never() )->method( 'ensure_data_source_for' );

		$this->expectException( \InvalidArgumentException::class );

		$this->service->patch( new ProductInputPatch( $this->make_input(), [] ) );
	}

	public function test_patch_propagates_merchant_api_exception() {
		$this->client->method( 'patch' )
			->willThrowException( new MerchantApiException( 404, [], __METHOD__ ) );

		$this->expectException( MerchantApiException::class );

		$this->service->patch( new ProductInputPatch( $this->make_input(), [ 'productAttributes.title' ] ) );
	}

	public function test_patch_many_keys_successes_and_failures_by_index() {
		$this->client->method( 'request_async' )
			->willReturnCallback(
				function ( string $method, string $path, array $body ) {
					if ( 'bad' === $body['offerId'] ) {
						return Create::rejectionFor( new MerchantApiException( 500, [], __METHOD__ ) );
					}

					return Create::promiseFor(
						[
							'name'    => 'accounts/12345/productInputs/' . $body['offerId'],
							'offerId' => $body['offerId'],
						]
					);
				}
			);

		$mask   = [ 'productAttributes.title' ];
		$result = $this->service->patch_many(
			[
				new ProductInputPatch( $this->make_input( 'good1' ), $mask ),
				new ProductInputPatch( $this->make_input( 'bad' ), $mask ),
				new ProductInputPatch( $this->make_input( 'good2' ), $mask ),
			]
		);

		$this->assertCount( 2, $result['successes'] );
		$this->assertCount( 1, $result['failures'] );
		$this->assertArrayHasKey( 0, $result['successes'] );
		$this->assertArrayHasKey( 2, $result['successes'] );
		$this->assertArrayHasKey( 1, $result['failures'] );
		$this->assertInstanceOf( MerchantApiException::class, $result['failures'][1] );
	}

	public function test_patch_many_routes_each_input_to_its_own_data_source() {
		$paths_seen = [];

		$this->client->method( 'request_async' )
			->willReturnCallback(
				function ( string $method, string $path, array $body ) use ( &$paths_seen ) {
					$paths_seen[ $body['offerId'] ] = [ $method, $path ];

					return Create::promiseFor( [ 'offerId' => $body['offerId'] ] );
				}
			);

		$mask     = [ 'productAttributes.title' ];
		$us_input = $this->make_input( 'us_sku', 'en', 'US' );
		$ca_input = $this->make_input( 'ca_sku', 'fr', 'CA' );

		$this->service->patch_many(
			[
				new ProductInputPatch( $us_input, $mask ),
				new ProductInputPatch( $ca_input, $mask ),
			]
		);

		$this->assertSame( 'PATCH', $paths_seen['us_sku'][0] );
		$this->assertSame( $this->expected_patch_path( $us_input, $mask, self::DS_EN_US ), $paths_seen['us_sku'][1] );
		$this->assertSame( $this->expected_patch_path( $ca_input, $mask, self::DS_FR_CA ), $paths_seen['ca_sku'][1] );
	}

	public function test_patch_many_throws_on_empty_update_mask_without_http_call() {
		$this->client->expects( $this->never() )->method( 'request_async' );

		$this->expectException( \InvalidArgumentException::class );

		$this->service->patch_many(
			[
				new ProductInputPatch( $this->make_input( 'good' ), [ 'productAttributes.title' ] ),
				new ProductInputPatch( $this->make_input( 'bad' ), [] ),
			]
		);
	}

	public function test_delete_resolves_data_source_and_calls_expected_path() {
		$input = $this->make_input();

		$this->client->expects( $this->once() )
			->method( 'delete' )
			->with( $this->expected_delete_path( $input, self::DS_EN_US ) )
			->willReturn( [] );

		$this->service->delete( $input );
	}

	public function test_delete_routes_different_market_to_a_different_data_source() {
		$input = $this->make_input( 'sku42', 'fr', 'CA' );

		$this->client->expects( $this->once() )
			->method( 'delete' )
			->with( $this->expected_delete_path( $input, self::DS_FR_CA ) )
			->willReturn( [] );

		$this->service->delete( $input );
	}

	public function test_delete_propagates_merchant_api_exception() {
		$this->client->method( 'delete' )
			->willThrowException( new MerchantApiException( 404, [], __METHOD__ ) );

		$this->expectException( MerchantApiException::class );

		$this->service->delete( $this->make_input() );
	}

	public function test_delete_many_keys_successes_and_failures_by_index() {
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) {
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = false !== strpos( $sub['path'], 'bad' )
							? [
								'status' => 404,
								'body'   => [],
							]
							: [
								'status' => 200,
								'body'   => [],
							];
					}

					return Create::promiseFor( $results );
				}
			);

		$inputs = [
			$this->make_input( 'good1' ),
			$this->make_input( 'bad' ),
			$this->make_input( 'good2' ),
		];

		$result = $this->service->delete_many( $inputs );

		$this->assertCount( 2, $result['successes'] );
		$this->assertCount( 1, $result['failures'] );
		$this->assertArrayHasKey( 0, $result['successes'] );
		$this->assertArrayHasKey( 2, $result['successes'] );
		$this->assertArrayHasKey( 1, $result['failures'] );
		$this->assertInstanceOf( MerchantApiException::class, $result['failures'][1] );
	}

	public function test_delete_many_routes_each_input_to_its_own_data_source() {
		$paths_seen = [];

		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$paths_seen ) {
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$paths_seen[]      = [ $sub['method'], $sub['path'] ];
						$results[ $index ] = [
							'status' => 200,
							'body'   => [],
						];
					}

					return Create::promiseFor( $results );
				}
			);

		$us_input = $this->make_input( 'us_sku', 'en', 'US' );
		$ca_input = $this->make_input( 'ca_sku', 'fr', 'CA' );

		$this->service->delete_many( [ $us_input, $ca_input ] );

		$this->assertSame( 'DELETE', $paths_seen[0][0] );
		$this->assertSame( $this->expected_delete_path( $us_input, self::DS_EN_US ), $paths_seen[0][1] );
		$this->assertSame( $this->expected_delete_path( $ca_input, self::DS_FR_CA ), $paths_seen[1][1] );
	}

	/**
	 * A "data source not found" 404, as run_in_batches records it. The real rejection names the
	 * data source, not the product, so this takes no offer id.
	 *
	 * @return array
	 */
	protected function data_source_404(): array {
		return [
			'status' => 404,
			'body'   => [ 'error' => [ 'message' => '[dataSource] Data source with id 999 was not found.' ] ],
		];
	}

	/**
	 * A successful insert sub-response for the given offer id.
	 *
	 * @param string $offer_id
	 *
	 * @return array
	 */
	protected function insert_ok( string $offer_id ): array {
		return [
			'status' => 200,
			'body'   => [
				'name'    => 'accounts/12345/productInputs/' . $offer_id,
				'offerId' => $offer_id,
			],
		];
	}

	public function test_insert_many_retries_after_data_source_re_resolution() {
		$forgotten = [];
		$this->data_sources->expects( $this->once() )
			->method( 'forget_data_source_for' )
			->with( 'en', 'US' )
			->willReturnCallback(
				function ( string $language, string $feed ) use ( &$forgotten ) {
					$forgotten[] = $language . '|' . $feed;
				}
			);

		$call = 0;
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$call ) {
					++$call;
					$results = [];
					foreach ( $requests as $index => $sub ) {
						// First pass: the data source 404s. Retry pass: it succeeds.
						$results[ $index ] = 1 === $call
							? $this->data_source_404()
							: $this->insert_ok( $sub['body']['offerId'] );
					}
					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many( [ $this->make_input( 'sku42', 'en', 'US' ) ] );

		$this->assertSame( 2, $call, 'One retry round only.' );
		$this->assertSame( [ 'en|US' ], $forgotten );
		$this->assertCount( 1, $result['successes'] );
		$this->assertCount( 0, $result['failures'] );
		$this->assertSame( 'sku42', $result['successes'][0]->get_offer_id() );
	}

	public function test_insert_many_retry_that_fails_again_is_returned_as_failure() {
		$this->data_sources->expects( $this->once() )->method( 'forget_data_source_for' );

		$call = 0;
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$call ) {
					++$call;
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = $this->data_source_404();
					}
					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many( [ $this->make_input( 'sku42', 'en', 'US' ) ] );

		$this->assertSame( 2, $call, 'Exactly one retry, then it stays failed.' );
		$this->assertCount( 0, $result['successes'] );
		$this->assertCount( 1, $result['failures'] );
		$this->assertInstanceOf( MerchantApiException::class, $result['failures'][0] );
	}

	public function test_insert_many_does_not_retry_non_404_failures() {
		$this->data_sources->expects( $this->never() )->method( 'forget_data_source_for' );

		$call = 0;
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$call ) {
					++$call;
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = [
							'status' => 500,
							'body'   => [ 'error' => [ 'message' => 'Internal error' ] ],
						];
					}
					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many( [ $this->make_input( 'sku42', 'en', 'US' ) ] );

		$this->assertSame( 1, $call, 'A 500 is not a data source problem, so no retry.' );
		$this->assertCount( 1, $result['failures'] );
	}

	public function test_insert_many_does_not_retry_404_not_mentioning_data_source() {
		$this->data_sources->expects( $this->never() )->method( 'forget_data_source_for' );

		$call = 0;
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$call ) {
					++$call;
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = [
							'status' => 404,
							'body'   => [ 'error' => [ 'message' => 'Some other resource was not found.' ] ],
						];
					}
					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many( [ $this->make_input( 'sku42', 'en', 'US' ) ] );

		$this->assertSame( 1, $call, 'A 404 that is not about the data source is not retried.' );
		$this->assertCount( 1, $result['failures'] );
	}

	public function test_insert_many_keeps_original_failure_when_re_resolution_throws() {
		$this->data_sources->method( 'forget_data_source_for' );
		// The upfront resolution succeeds so the batch runs and 404s; the retry re-resolution then
		// fails, so the input keeps its original failure and is not retried.
		$resolve_calls = 0;
		$this->data_sources->method( 'ensure_data_source_for' )
			->willReturnCallback(
				function () use ( &$resolve_calls ) {
					if ( 0 === $resolve_calls++ ) {
						return self::DS_EN_US;
					}
					throw new MerchantApiException( 500, [ 'error' => [ 'message' => 'list failed' ] ], 'test' );
				}
			);

		$call = 0;
		$this->client->method( 'batch_async' )
			->willReturnCallback(
				function ( array $requests ) use ( &$call ) {
					++$call;
					$results = [];
					foreach ( $requests as $index => $sub ) {
						$results[ $index ] = $this->data_source_404();
					}
					return Create::promiseFor( $results );
				}
			);

		$result = $this->service->insert_many( [ $this->make_input( 'sku42', 'en', 'US' ) ] );

		$this->assertSame( 1, $call, 'A failed re-resolution means no retry batch.' );
		$this->assertCount( 1, $result['failures'] );
		$this->assertSame( 404, $result['failures'][0]->get_http_status() );
	}
}
