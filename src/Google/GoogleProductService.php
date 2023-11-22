<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsCustomBatchRequest as GoogleBatchRequest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsCustomBatchRequestEntry as GoogleBatchRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsCustomBatchResponse as GoogleBatchResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductsCustomBatchResponseEntry as GoogleBatchResponseEntry;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProductService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class GoogleProductService implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;
	use ValidateInterface;

	public const INTERNAL_ERROR_REASON  = 'internalError';
	public const NOT_FOUND_ERROR_REASON = 'notFound';

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
	 * @var ShoppingContent
	 */
	protected $shopping_service;

	/**
	 * GoogleProductService constructor.
	 *
	 * @param ShoppingContent $shopping_service
	 */
	public function __construct( ShoppingContent $shopping_service ) {
		$this->shopping_service = $shopping_service;
	}

	/**
	 * @param string $product_id Google product ID.
	 *
	 * @return GoogleProduct
	 *
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function get( string $product_id ): GoogleProduct {
		$merchant_id = $this->options->get_merchant_id();

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
		$merchant_id = $this->options->get_merchant_id();

		return $this->shopping_service->products->insert( $merchant_id, $product );
	}

	/**
	 * @param string $product_id Google product ID.
	 *
	 * @throws GoogleException If there are any Google API errors.
	 */
	public function delete( string $product_id ) {
		$merchant_id = $this->options->get_merchant_id();

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
		if ( empty( $products ) ) {
			return new BatchProductResponse( [], [] );
		}

		$merchant_id     = $this->options->get_merchant_id();
		$request_entries = [];

		// An array of product entries mapped to each batch ID. Used to parse Google's batch response.
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

			$batch_id_product_map[ $batch_id ] = $product_entry;

			++$batch_id;
		}

		$responses = $this->shopping_service->products->custombatch( new GoogleBatchRequest( [ 'entries' => $request_entries ] ) );

		return $this->parse_batch_responses( $responses, $batch_id_product_map );
	}

	/**
	 * @param GoogleBatchResponse                                     $responses
	 * @param BatchProductRequestEntry[]|BatchProductIDRequestEntry[] $batch_id_product_map An array of product entries mapped to each batch ID. Used to parse Google's batch response.
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
			// Product entry is mapped to batchId when sending the request
			$product_entry = $batch_id_product_map[ $response->batchId ]; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$wc_product_id = $product_entry->get_wc_product_id();
			if ( $product_entry instanceof BatchProductRequestEntry ) {
				$google_product_id = $product_entry->get_product()->getId();
			} else {
				$google_product_id = $product_entry->get_product_id();
			}

			if ( empty( $response->getErrors() ) ) {
				$result_products[] = new BatchProductEntry( $wc_product_id, $response->getProduct() );
			} else {
				$errors[] = new BatchInvalidProductEntry( $wc_product_id, $google_product_id, self::get_batch_response_error_messages( $response ) );
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
