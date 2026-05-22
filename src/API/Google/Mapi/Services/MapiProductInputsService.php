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

		$client = $this->client;

		$requests = function () use ( $inputs, $client, $paths_by_index ) {
			foreach ( $inputs as $index => $input ) {
				yield $index => $client->request_async( 'POST', $paths_by_index[ $index ], $input->to_array() );
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
}
