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

		$this->log( sprintf( 'productInputs.insert %s: %s', $input->get_offer_id(), wp_json_encode( $input->to_array() ) ), __METHOD__ );

		$body = $this->client->post(
			$this->build_path( $data_source ),
			$input->to_array()
		);

		$this->log( sprintf( 'productInputs.insert %s succeeded (%s)', $input->get_offer_id(), $body['name'] ?? '' ), __METHOD__ );

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

			// Bulk sync logs a lightweight per-item line; the full payload is logged only by the
			// single-item insert()/patch() used by the debugger, to avoid a JSON dump per product.
			$this->log( sprintf( 'productInputs.insert %s', $input->get_offer_id() ), __METHOD__ );
		}

		$result = $this->run_in_batches(
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

		$this->log( sprintf( 'productInputs.insert batch of %d: %d succeeded, %d failed', count( $inputs ), count( $result['successes'] ), count( $result['failures'] ) ), __METHOD__ );

		return $this->retry_missing_data_sources( $inputs, $result, $concurrency );
	}

	/**
	 * Re-resolve data sources for inserts rejected with "data source not found" and retry them once.
	 *
	 * @param ProductInput[] $inputs      The inputs from the original insert_many() call.
	 * @param array          $result      The first-pass result to merge retries into.
	 * @param int            $concurrency Concurrent batch requests.
	 *
	 * @return array{successes: array<int, ProductInput>, failures: array<int, MerchantApiException>}
	 */
	private function retry_missing_data_sources( array $inputs, array $result, int $concurrency ): array {
		$paths_by_index = [];
		$reresolved     = [];

		foreach ( $result['failures'] as $index => $failure ) {
			if ( ! isset( $inputs[ $index ] ) || ! MapiDataSourcesService::is_missing_data_source_failure( $failure ) ) {
				continue;
			}

			$input = $inputs[ $index ];
			// Local grouping key, so each (language, feed) pair is re-resolved once per retry pass.
			$pair = $input->get_content_language() . '|' . $input->get_feed_label();

			if ( ! array_key_exists( $pair, $reresolved ) ) {
				try {
					$this->data_sources->forget_data_source_for( $input->get_content_language(), $input->get_feed_label() );
					$reresolved[ $pair ] = $this->data_sources->ensure_data_source_for( $input->get_content_language(), $input->get_feed_label() );
				} catch ( MerchantApiException $exception ) {
					// Re-resolution failed: leave the original failure in place for this pair's inputs.
					$reresolved[ $pair ] = null;
				}
			}

			if ( null !== $reresolved[ $pair ] ) {
				$paths_by_index[ $index ] = $this->build_path( $reresolved[ $pair ] );
			}
		}

		if ( empty( $paths_by_index ) ) {
			return $result;
		}

		$retry_inputs = array_intersect_key( $inputs, $paths_by_index );

		$retry_result = $this->run_in_batches(
			$retry_inputs,
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

		foreach ( $retry_result['successes'] as $index => $success ) {
			$result['successes'][ $index ] = $success;
			unset( $result['failures'][ $index ] );
		}
		foreach ( $retry_result['failures'] as $index => $failure ) {
			$result['failures'][ $index ] = $failure;
		}

		$this->log( sprintf( 'productInputs.insert retried %d inputs after data source re-resolution: %d succeeded', count( $retry_inputs ), count( $retry_result['successes'] ) ), __METHOD__ );

		return $result;
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

		$this->log( sprintf( 'productInputs.patch %s (%s): %s', $input->get_offer_id(), implode( ',', $mask ), wp_json_encode( $input->to_array() ) ), __METHOD__ );

		$body = $this->client->patch(
			$this->build_patch_path( $input, $mask, $data_source ),
			$input->to_array()
		);

		$this->log( sprintf( 'productInputs.patch %s succeeded (%s)', $input->get_offer_id(), $body['name'] ?? '' ), __METHOD__ );

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

			$this->log( sprintf( 'productInputs.patch %s (%s)', $input->get_offer_id(), implode( ',', $mask ) ), __METHOD__ );
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

		$this->log( sprintf( 'productInputs.patch batch of %d: %d succeeded, %d failed', count( $patches ), count( $successes ), count( $failures ) ), __METHOD__ );

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

		$this->log( sprintf( 'productInputs.delete %s', $input->get_offer_id() ), __METHOD__ );

		$this->client->delete( $this->build_delete_path( $input, $data_source ) );

		$this->log( sprintf( 'productInputs.delete %s succeeded', $input->get_offer_id() ), __METHOD__ );
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

			$this->log( sprintf( 'productInputs.delete %s', $input->get_offer_id() ), __METHOD__ );
		}

		$result = $this->run_in_batches(
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

		$this->log( sprintf( 'productInputs.delete batch of %d: %d succeeded, %d failed', count( $inputs ), count( $result['successes'] ), count( $result['failures'] ) ), __METHOD__ );

		return $result;
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
	 * Emit a debug-log entry for a Merchant API product write so the payload and outcome of a
	 * push can be verified and troubleshot. Recorded only when GLA debug logging is enabled.
	 *
	 * @param string $message
	 * @param string $method  Calling method, for the debug log's source attribution.
	 */
	private function log( string $message, string $method ): void {
		do_action( 'woocommerce_gla_debug_message', $message, $method );
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
