<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInputPatch;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\EachPromise;
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiProductInputsService
 *
 * Manages products on the Merchant API through the `accounts.productInputs.*`
 * endpoints.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiProductInputsService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/** @var MapiDataSourcesService */
	protected $data_sources;

	/**
	 * MapiProductInputsService constructor.
	 *
	 * @param MerchantApiClient      $client
	 * @param MapiDataSourcesService $data_sources
	 */
	public function __construct( MerchantApiClient $client, MapiDataSourcesService $data_sources ) {
		$this->client       = $client;
		$this->data_sources = $data_sources;
	}

	/**
	 * Insert a single product. The target data source is resolved from the
	 * input's contentLanguage + feedLabel.
	 *
	 * @param ProductInput $input
	 *
	 * @return ProductInput The hydrated response.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function insert( ProductInput $input ): ProductInput {
		$data_source = $this->data_sources->ensure_data_source_for(
			$input->get_content_language(),
			$input->get_feed_label()
		);

		$body = $this->client->post(
			$this->build_path( $data_source ),
			$input->to_array()
		);

		return ProductInput::from_array( $body );
	}

	/**
	 * Insert multiple products in parallel. Each input is routed to the data
	 * source matching its (contentLanguage, feedLabel).
	 *
	 * @param ProductInput[] $inputs
	 * @param int            $concurrency
	 *
	 * @return array{successes: array<int, ProductInput>, failures: array<int, MerchantApiException>}
	 * @throws MerchantApiException On a non-2xx MAPI response while resolving a data source.
	 */
	public function insert_many( array $inputs, int $concurrency = 10 ): array {
		// Resolve all unique (language, feed) pairs upfront so the async batch
		// starts with every data source known and cached.
		$paths_by_index = [];
		foreach ( $inputs as $index => $input ) {
			$data_source              = $this->data_sources->ensure_data_source_for(
				$input->get_content_language(),
				$input->get_feed_label()
			);
			$paths_by_index[ $index ] = $this->build_path( $data_source );
		}

		return $this->run_in_batches(
			$inputs,
			$concurrency,
			__METHOD__,
			function ( int $index, ProductInput $input ) use ( $paths_by_index ): array {
				return [
					'method' => 'POST',
					'path'   => $paths_by_index[ $index ],
					'body'   => $input->to_array(),
				];
			},
			function ( array $body ): ProductInput {
				return ProductInput::from_array( $body );
			}
		);
	}

	/**
	 * Patch a single product.
	 *
	 * @param ProductInputPatch $patch
	 *
	 * @return ProductInput The hydrated response.
	 * @throws InvalidArgumentException When the update mask is empty.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function patch( ProductInputPatch $patch ): ProductInput {
		$input = $patch->get_input();
		$mask  = $patch->get_update_mask();

		if ( empty( $mask ) ) {
			throw new InvalidArgumentException( 'A product patch requires a non-empty update mask.' );
		}

		$data_source = $this->data_sources->ensure_data_source_for(
			$input->get_content_language(),
			$input->get_feed_label()
		);

		$body = $this->client->patch(
			$this->build_patch_path( $input, $mask, $data_source ),
			$input->to_array()
		);

		return ProductInput::from_array( $body );
	}

	/**
	 * Patch multiple products in parallel.
	 *
	 * @param ProductInputPatch[] $patches
	 * @param int                 $concurrency
	 *
	 * @return array{successes: array<int, ProductInput>, failures: array<int, MerchantApiException>}
	 * @throws InvalidArgumentException When any update mask is empty.
	 * @throws MerchantApiException On a non-2xx MAPI response while resolving a data source.
	 */
	public function patch_many( array $patches, int $concurrency = 10 ): array {
		// Resolve all data sources upfront so the async batch
		// starts with every data source known and cached.
		$paths_by_index = [];
		foreach ( $patches as $index => $patch ) {
			$input = $patch->get_input();
			$mask  = $patch->get_update_mask();

			if ( empty( $mask ) ) {
				throw new InvalidArgumentException( 'A product patch requires a non-empty update mask.' );
			}

			$data_source              = $this->data_sources->ensure_data_source_for(
				$input->get_content_language(),
				$input->get_feed_label()
			);
			$paths_by_index[ $index ] = $this->build_patch_path( $input, $mask, $data_source );
		}

		$client = $this->client;

		$requests = function () use ( $patches, $client, $paths_by_index ) {
			foreach ( $patches as $index => $patch ) {
				yield $index => $client->request_async( 'PATCH', $paths_by_index[ $index ], $patch->get_input()->to_array() );
			}
		};

		$successes = [];
		$failures  = [];

		( new EachPromise(
			$requests(),
			[
				'concurrency' => $concurrency,
				'fulfilled'   => function ( array $body, int $index ) use ( &$successes ) {
					$successes[ $index ] = ProductInput::from_array( $body );
				},
				'rejected'    => function ( $reason, int $index ) use ( &$failures ) {
					$failures[ $index ] = $reason;
				},
			]
		) )->promise()->wait();

		return [
			'successes' => $successes,
			'failures'  => $failures,
		];
	}

	/**
	 * Delete a single product. The target data source is resolved from the input's
	 * contentLanguage + feedLabel.
	 *
	 * @param ProductInput $input Product to delete.
	 *
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function delete( ProductInput $input ): void {
		$data_source = $this->data_sources->ensure_data_source_for(
			$input->get_content_language(),
			$input->get_feed_label()
		);

		$this->client->delete( $this->build_delete_path( $input, $data_source ) );
	}

	/**
	 * Delete multiple products in parallel. Each input is routed to the data source
	 * matching its (contentLanguage, feedLabel).
	 *
	 * @param ProductInput[] $inputs
	 * @param int            $concurrency
	 *
	 * @return array{successes: array<int, ProductInput>, failures: array<int, MerchantApiException>}
	 * @throws MerchantApiException On a non-2xx MAPI response while resolving a data source.
	 */
	public function delete_many( array $inputs, int $concurrency = 10 ): array {
		$paths_by_index = [];
		foreach ( $inputs as $index => $input ) {
			$data_source              = $this->data_sources->ensure_data_source_for(
				$input->get_content_language(),
				$input->get_feed_label()
			);
			$paths_by_index[ $index ] = $this->build_delete_path( $input, $data_source );
		}

		return $this->run_in_batches(
			$inputs,
			$concurrency,
			__METHOD__,
			function ( int $index ) use ( $paths_by_index ): array {
				return [
					'method' => 'DELETE',
					'path'   => $paths_by_index[ $index ],
				];
			},
			function ( array $body, ProductInput $input ): ProductInput {
				return $input;
			}
		);
	}

	/**
	 * Run product sub-requests in concurrent multipart batches, demuxing each
	 * sub-response back to a per-input success (ProductInput) or failure
	 * (MerchantApiException). A whole-batch failure marks every input in it as failed.
	 *
	 * @param ProductInput[] $inputs
	 * @param int            $concurrency   Concurrent batch requests.
	 * @param string         $operation     Method label used on sub-request failures.
	 * @param callable       $build_request fn(int $index, ProductInput $input): array{method: string, path: string, body?: array}
	 * @param callable       $on_success    fn(array $body, ProductInput $input): ProductInput
	 *
	 * @return array{successes: array<int, ProductInput>, failures: array<int, MerchantApiException>}
	 */
	private function run_in_batches( array $inputs, int $concurrency, string $operation, callable $build_request, callable $on_success ): array {
		$client        = $this->client;
		$index_batches = array_chunk( array_keys( $inputs ), $this->get_batch_size() );

		$successes = [];
		$failures  = [];

		$promises = function () use ( $index_batches, $inputs, $client, $build_request ) {
			foreach ( $index_batches as $batch_num => $indices ) {
				$requests = [];
				foreach ( $indices as $index ) {
					$requests[ $index ] = $build_request( $index, $inputs[ $index ] );
				}

				yield $batch_num => $client->batch_async( $requests );
			}
		};

		( new EachPromise(
			$promises(),
			[
				'concurrency' => $concurrency,
				'fulfilled'   => function ( array $batch_results, $batch_num ) use ( &$successes, &$failures, $inputs, $index_batches, $on_success, $operation ) {
					foreach ( $batch_results as $index => $result ) {
						// Ignore a sub-response for an id that was not in this batch (guards
						// against a malformed or duplicate Content-ID in the response).
						if ( ! isset( $inputs[ $index ] ) ) {
							continue;
						}

						if ( $result['status'] >= 200 && $result['status'] < 300 ) {
							$successes[ $index ] = $on_success( $result['body'], $inputs[ $index ] );
						} else {
							$failures[ $index ] = new MerchantApiException( $result['status'], $result['body'], $operation );
						}
					}

					// A requested sub-request missing from the parsed response is a retryable
					// failure, never a silent no-op.
					foreach ( array_diff( $index_batches[ $batch_num ], array_keys( $batch_results ) ) as $index ) {
						$failures[ $index ] = new MerchantApiException(
							500,
							[ 'error' => [ 'message' => 'No sub-response returned for this product in the batch response.' ] ],
							$operation
						);
					}
				},
				'rejected'    => function ( $reason, $batch_num ) use ( &$failures, $index_batches ) {
					foreach ( $index_batches[ $batch_num ] as $index ) {
						$failures[ $index ] = $reason;
					}
				},
			]
		) )->promise()->wait();

		return [
			'successes' => $successes,
			'failures'  => $failures,
		];
	}

	/**
	 * Number of product sub-requests to pack into a single batch HTTP request.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return max( 1, (int) apply_filters( 'woocommerce_gla_mapi_batch_size', 50 ) );
	}

	/**
	 * Build the productInputs.insert path with the resolved data source.
	 *
	 * @param string $data_source Data source resource name.
	 *
	 * @return string
	 */
	protected function build_path( string $data_source ): string {
		return sprintf(
			'%s/accounts/%s/productInputs:insert?dataSource=%s',
			MapiPaths::PRODUCTS,
			$this->options->get_merchant_id(),
			rawurlencode( $data_source )
		);
	}

	/**
	 * Build the productInputs.patch path with the resolved data source and mask.
	 *
	 * @param ProductInput $input       The product resource name.
	 * @param string[]     $update_mask Field names to update.
	 * @param string       $data_source Data source resource name.
	 *
	 * @return string
	 */
	protected function build_patch_path( ProductInput $input, array $update_mask, string $data_source ): string {
		return sprintf(
			'%s/accounts/%s/productInputs/%s~%s~%s?dataSource=%s&updateMask=%s',
			MapiPaths::PRODUCTS,
			$this->options->get_merchant_id(),
			$input->get_content_language(),
			$input->get_feed_label(),
			rawurlencode( $input->get_offer_id() ),
			rawurlencode( $data_source ),
			rawurlencode( implode( ',', $update_mask ) )
		);
	}

	/**
	 * Build the productInputs.delete path with the resolved data source.
	 *
	 * @param ProductInput $input       Products to delete.
	 * @param string       $data_source Data source resource name.
	 *
	 * @return string
	 */
	protected function build_delete_path( ProductInput $input, string $data_source ): string {
		return sprintf(
			'%s/accounts/%s/productInputs/%s~%s~%s?dataSource=%s',
			MapiPaths::PRODUCTS,
			$this->options->get_merchant_id(),
			$input->get_content_language(),
			$input->get_feed_label(),
			rawurlencode( $input->get_offer_id() ),
			rawurlencode( $data_source )
		);
	}
}
