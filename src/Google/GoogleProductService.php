<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google\Exception as GoogleException;
use Google_Service_ShoppingContent as GoogleShoppingService;
use Google_Service_ShoppingContent_Product as GoogleProduct;
use Google_Service_ShoppingContent_ProductsCustomBatchRequest as GoogleBatchRequest;
use Google_Service_ShoppingContent_ProductsCustomBatchRequestEntry as GoogleBatchRequestEntry;
use Google_Service_ShoppingContent_ProductsCustomBatchResponse as GoogleBatchResponse;
use Google_Service_ShoppingContent_ProductsCustomBatchResponseEntry as GoogleBatchResponseEntry;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProductService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class GoogleProductService implements Service {

	use ValidateInterface;

	public const INTERNAL_ERROR_REASON = 'internalError';

	/**
	 * This is the maximum batch size recommended by Google
	 *
	 * @link https://developers.google.com/shopping-content/guides/how-tos/batch
	 */
	public const BATCH_SIZE = 1000;

	protected const METHOD_DELETE = 'delete';
	protected const METHOD_GET    = 'get';
	protected const METHOD_INSERT = 'insert';

	/**
	 * @var GoogleShoppingService
	 */
	protected $shopping_service;

	/**
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * GoogleProductService constructor.
	 *
	 * @param GoogleShoppingService $shopping_service
	 * @param Merchant              $merchant
	 */
	public function __construct( GoogleShoppingService $shopping_service, Merchant $merchant ) {
		$this->shopping_service = $shopping_service;
		$this->merchant         = $merchant;
	}

	/**
	 * @param string $product_id Google product ID.
	 *
	 * @return GoogleProduct
	 *
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function get( string $product_id ): GoogleProduct {
		$merchant_id = $this->merchant->get_id();

		return $this->shopping_service->products->get( $merchant_id, $product_id );
	}

	/**
	 * @param GoogleProduct $product
	 *
	 * @return GoogleProduct
	 *
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function insert( GoogleProduct $product ): GoogleProduct {
		$merchant_id = $this->merchant->get_id();

		return $this->shopping_service->products->insert( $merchant_id, $product );
	}

	/**
	 * @param string $product_id Google product ID.
	 *
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function delete( string $product_id ) {
		$merchant_id = $this->merchant->get_id();

		$this->shopping_service->products->delete( $merchant_id, $product_id );
	}

	/**
	 * @param BatchProductIDRequestEntry[] $products
	 *
	 * @return BatchProductResponse
	 *
	 * @throws InvalidValue If any of the provided products are invalid.
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function get_batch( array $products ): BatchProductResponse {
		if ( empty( $products ) ) {
			return new BatchProductResponse( [], [] );
		}
		return $this->custom_batch( $products, self::METHOD_GET );
	}

	/**
	 * @param BatchProductRequestEntry[] $products
	 *
	 * @return BatchProductResponse
	 *
	 * @throws InvalidValue If any of the provided products are invalid.
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function insert_batch( array $products ): BatchProductResponse {
		if ( empty( $products ) ) {
			return new BatchProductResponse( [], [] );
		}
		return $this->custom_batch( $products, self::METHOD_INSERT );
	}

	/**
	 * @param BatchProductIDRequestEntry[] $products
	 *
	 * @return BatchProductResponse
	 *
	 * @throws InvalidValue If any of the provided products are invalid.
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function delete_batch( array $products ): BatchProductResponse {
		if ( empty( $products ) ) {
			return new BatchProductResponse( [], [] );
		}
		return $this->custom_batch( $products, self::METHOD_DELETE );
	}

	/**
	 * @param BatchProductRequestEntry[]|BatchProductIDRequestEntry[] $products
	 * @param string                                                  $method
	 *
	 * @return BatchProductResponse
	 *
	 * @throws InvalidValue    If any of the products' type is invalid for the batch method.
	 * @throws GoogleException If there are any Google API errors.
	 */
	protected function custom_batch( array $products, string $method ): BatchProductResponse {
		$merchant_id     = $this->merchant->get_id();
		$request_entries = [];

		// An array of WooCommerce product IDs mapped to each batch ID. Used to parse Google's batch response.
		$batch_id_product_map = [];

		$batch_id = 0;
		foreach ( $products as $product_entry ) {
			$this->validate_batch_request_entry( $product_entry, $method );

			$request_entry = new GoogleBatchRequestEntry(
				[
					'batchId'    => $batch_id,
					'merchantId' => $merchant_id,
					'method'     => $method,
				]
			);

			if ( $product_entry instanceof BatchProductRequestEntry ) {
				$request_entry['product'] = $product_entry->get_product();
			} else {
				$request_entry['product_id'] = $product_entry->get_product_id();
			}
			$request_entries[] = $request_entry;

			$batch_id_product_map[ $batch_id ] = $product_entry->get_wc_product_id();

			$batch_id++;
		}

		$responses = $this->shopping_service->products->custombatch( new GoogleBatchRequest( [ 'entries' => $request_entries ] ) );

		return $this->parse_batch_responses( $responses, $batch_id_product_map );
	}

	/**
	 * @param GoogleBatchResponse $responses
	 * @param int[]               $batch_id_product_map An array of WooCommerce product IDs mapped to each batch ID. Used to parse Google's batch response.
	 *
	 * @return BatchProductResponse
	 */
	protected function parse_batch_responses( GoogleBatchResponse $responses, array $batch_id_product_map ): BatchProductResponse {
		$result_products = [];
		$errors          = [];

		/**
		 * @var GoogleBatchResponseEntry $response
		 */
		foreach ( $responses as $response ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			// WooCommerce product ID is mapped to batchId when sending the request
			$wc_product_id = $batch_id_product_map[ $response->batchId ];

			if ( empty( $response->getErrors() ) ) {
				$result_products[] = new BatchProductEntry( $wc_product_id, $response->getProduct() );
			} else {
				$errors[] = new BatchInvalidProductEntry( $wc_product_id, self::get_batch_response_error_messages( $response ) );
			}
		}

		return new BatchProductResponse( $result_products, $errors );
	}

	/**
	 * @param BatchProductRequestEntry|BatchProductIDRequestEntry $request_entry
	 * @param string                                              $method
	 *
	 * @throws InvalidValue If the product type is invalid for the batch method.
	 */
	protected function validate_batch_request_entry( $request_entry, string $method ) {
		if ( self::METHOD_INSERT === $method ) {
			$this->validate_instanceof( $request_entry, BatchProductRequestEntry::class );
		} else {
			$this->validate_instanceof( $request_entry, BatchProductIDRequestEntry::class );
		}
	}

	/**
	 * @param GoogleBatchResponseEntry $batch_response_entry
	 *
	 * @return string[]
	 */
	protected static function get_batch_response_error_messages( GoogleBatchResponseEntry $batch_response_entry ): array {
		$errors = [];
		foreach ( $batch_response_entry->getErrors()->getErrors() as $error ) {
			$errors[ $error->getReason() ] = $error->getMessage();
		}

		return $errors;
	}
}
