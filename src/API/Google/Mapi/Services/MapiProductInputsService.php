<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\EachPromise;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiProductInputsService
 *
 * Writes products to the Merchant API via `accounts.productInputs.insert`.
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
	 * Insert a single product.
	 *
	 * @param ProductInput $input
	 *
	 * @return ProductInput
	 * @throws MerchantApiException
	 */
	public function insert( ProductInput $input ): ProductInput {
		$body = $this->client->post(
			$this->build_path( $this->data_sources->ensure_primary_data_source() ),
			$input->to_array()
		);

		return ProductInput::from_array( $body );
	}

	/**
	 * Insert multiple products in parallel.
	 *
	 * @param ProductInput[] $inputs
	 * @param int            $concurrency
	 *
	 * @return array{successes: array<int, ProductInput>, failures: array<int, MerchantApiException>}
	 * @throws MerchantApiException
	 */
	public function insert_many( array $inputs, int $concurrency = 10 ): array {
		$path   = $this->build_path( $this->data_sources->ensure_primary_data_source() );
		$client = $this->client;

		$requests = function () use ( $inputs, $client, $path ) {
			foreach ( $inputs as $index => $input ) {
				yield $index => $client->request_async( 'POST', $path, $input->to_array() );
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
	 * Build the productInputs.insert path.
	 *
	 * @param string $data_source
	 *
	 * @return string
	 */
	protected function build_path( string $data_source ): string {
		return sprintf(
			'products/v1/accounts/%s/productInputs:insert?dataSource=%s',
			$this->options->get_merchant_id(),
			rawurlencode( $data_source )
		);
	}
}
