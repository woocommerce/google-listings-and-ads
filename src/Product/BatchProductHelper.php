<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Product;
use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchProductHelper
 *
 * Contains helper methods for batch processing products.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class BatchProductHelper implements Service {

	use ValidateInterface;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var ValidatorInterface
	 */
	protected $validator;

	/**
	 * @var ProductFactory
	 */
	protected $product_factory;

	/**
	 * @var AttributeMappingRulesQuery
	 */
	protected $attribute_mapping_rules_query;

	/**
	 * @var MarketService
	 */
	protected $market_service;

	/**
	 * BatchProductHelper constructor.
	 *
	 * @param ProductMetaHandler         $meta_handler
	 * @param ProductHelper              $product_helper
	 * @param ValidatorInterface         $validator
	 * @param ProductFactory             $product_factory
	 * @param AttributeMappingRulesQuery $attribute_mapping_rules_query
	 * @param MarketService              $market_service
	 */
	public function __construct(
		ProductMetaHandler $meta_handler,
		ProductHelper $product_helper,
		ValidatorInterface $validator,
		ProductFactory $product_factory,
		AttributeMappingRulesQuery $attribute_mapping_rules_query,
		MarketService $market_service
	) {
		$this->meta_handler                  = $meta_handler;
		$this->product_helper                = $product_helper;
		$this->validator                     = $validator;
		$this->product_factory               = $product_factory;
		$this->attribute_mapping_rules_query = $attribute_mapping_rules_query;
		$this->market_service                = $market_service;
	}

	/**
	 * Filters and returns only the products already synced with Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return WC_Product[] The synced products.
	 */
	public function filter_synced_products( array $products ): array {
		return array_filter( $products, [ $this->product_helper, 'is_product_synced' ] );
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	public function mark_as_synced( BatchProductEntry $product_entry ) {
		$wc_product     = $this->product_helper->get_wc_product( $product_entry->get_wc_product_id() );
		$google_product = $product_entry->get_google_product();

		$this->validate_instanceof( $google_product, GoogleProduct::class );

		$this->product_helper->mark_as_synced( $wc_product, $google_product );
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	public function mark_as_unsynced( BatchProductEntry $product_entry ) {
		try {
			$wc_product = $this->product_helper->get_wc_product( $product_entry->get_wc_product_id() );
		} catch ( InvalidValue $exception ) {
			return;
		}

		$this->product_helper->mark_as_unsynced( $wc_product );
	}

	/**
	 * Mark a batch of WooCommerce product IDs as unsynced.
	 * Invalid products will be skipped.
	 *
	 * @since 1.12.0
	 *
	 * @param array $product_ids
	 */
	public function mark_batch_as_unsynced( array $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			try {
				$product = $this->product_helper->get_wc_product( $product_id );
			} catch ( InvalidValue $exception ) {
				continue;
			}

			$this->product_helper->mark_as_unsynced( $product );
		}
	}

	/**
	 * Marks a WooCommerce product as invalid and stores the errors in a meta data key.
	 *
	 * Note: If a product variation is invalid then the parent product is also marked as invalid.
	 *
	 * @param BatchInvalidProductEntry $product_entry
	 */
	public function mark_as_invalid( BatchInvalidProductEntry $product_entry ) {
		$wc_product = $this->product_helper->get_wc_product( $product_entry->get_wc_product_id() );
		$errors     = $product_entry->get_errors();

		$this->product_helper->mark_as_invalid( $wc_product, $errors );
	}

	/**
	 * Generates an array map containing the Google product IDs as key and the WooCommerce product IDs as values.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductIDRequestEntry[]
	 */
	public function generate_delete_request_entries( array $products ): array {
		$request_entries = [];
		foreach ( $products as $product ) {
			$this->validate_instanceof( $product, WC_Product::class );

			if ( $product instanceof WC_Product_Variable ) {
				$request_entries = array_merge( $request_entries, $this->generate_delete_request_entries( $product->get_available_variations( 'objects' ) ) );
				continue;
			}

			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			foreach ( $google_ids as $google_id ) {
				$request_entries[ $google_id ] = new BatchProductIDRequestEntry(
					$product->get_id(),
					$google_id
				);
			}
		}

		return $request_entries;
	}

	/**
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductRequestEntry[]
	 */
	public function validate_and_generate_update_request_entries( array $products ): array {
		$request_entries = [];
		$mapping_rules   = $this->attribute_mapping_rules_query->get_results();

		foreach ( $products as $product ) {
			$this->validate_instanceof( $product, WC_Product::class );

			try {
				if ( ! $this->product_helper->is_sync_ready( $product ) ) {
					do_action(
						'woocommerce_gla_debug_message',
						sprintf( 'Skipping product (ID: %s) because it is not ready to be synced.', $product->get_id() ),
						__METHOD__
					);

					continue;
				}

				if ( $product instanceof WC_Product_Variable ) {
					$request_entries = array_merge( $request_entries, $this->validate_and_generate_update_request_entries( $product->get_available_variations( 'objects' ) ) );
					continue;
				}

				$primary_market = $this->market_service->get_primary_market();

				// validate the product
				$adapted_product   = $this->product_factory->create(
					$product,
					$primary_market['country'],
					$mapping_rules,
					$primary_market['feed_label'],
					$this->extract_language( $primary_market )
				);
				$validation_result = $this->validate_product( $adapted_product );
				if ( $validation_result instanceof BatchInvalidProductEntry ) {
					$this->mark_as_invalid( $validation_result );

					do_action(
						'woocommerce_gla_debug_message',
						sprintf( 'Skipping product (ID: %s) because it does not pass validation: %s', $product->get_id(), wp_json_encode( $validation_result ) ),
						__METHOD__
					);

					continue;
				}

				// add shipping for all countries across all markets
				$all_countries = $this->market_service->get_all_countries();
				array_walk( $all_countries, [ $adapted_product, 'add_shipping_country' ] );

				// Stage entries per product so a throw or validation failure in
				// any secondary market discards the whole product's entries
				// preventing the primary feed from receiving the product while
				// a secondary feed silently misses it.
				$product_entries   = [];
				$product_entries[] = new BatchProductRequestEntry(
					$product->get_id(),
					$adapted_product
				);

				foreach ( $this->market_service->get_markets() as $market_id => $market ) {
					if ( 'primary' === $market_id ) {
						continue;
					}

					$secondary_adapter = $this->product_factory->create(
						$product,
						$market['country'],
						$mapping_rules,
						$market['feed_label'],
						$this->extract_language( $market ),
						$this->extract_currency( $market )
					);

					$secondary_validation = $this->validate_product( $secondary_adapter );
					if ( $secondary_validation instanceof BatchInvalidProductEntry ) {
						$this->mark_as_invalid( $secondary_validation );

						do_action(
							'woocommerce_gla_debug_message',
							sprintf( 'Skipping product (ID: %s) because it does not pass validation for secondary market %s: %s', $product->get_id(), $market_id, wp_json_encode( $secondary_validation ) ),
							__METHOD__
						);

						continue 2;
					}

					$secondary_adapter->add_shipping_country( $market['country'] );

					$product_entries[] = new BatchProductRequestEntry(
						$product->get_id(),
						$secondary_adapter
					);
				}

				array_push( $request_entries, ...$product_entries );
			} catch ( GoogleListingsAndAdsException $exception ) {
				do_action(
					'woocommerce_gla_error',
					sprintf( 'Skipping product (ID: %s) due to exception: %s', $product->get_id(), $exception->getMessage() ),
					__METHOD__
				);

				continue;
			}
		}

		return $request_entries;
	}

	/**
	 * Extracts a single language code from a market config.
	 *
	 * Each MC product carries exactly one contentLanguage. Markets store
	 * language as an array of codes; the first entry is used.
	 *
	 * @param array $market A market config array as returned by MarketService.
	 *
	 * @return string The single language code, or empty string when none is set.
	 */
	protected function extract_language( array $market ): string {
		return is_array( $market['language'] ?? null )
			? (string) ( $market['language'][0] ?? '' )
			: (string) ( $market['language'] ?? '' );
	}

	/**
	 * Extracts a single currency code from a market config.
	 *
	 * Each MC product carries exactly one price.currency. Markets store currency
	 * as an array of codes; the first entry is used.
	 *
	 * @param array $market A market config array as returned by MarketService.
	 *
	 * @return string The single currency code, or empty string when none is set.
	 */
	private static function extract_currency( array $market ): string {
		return is_array( $market['currency'] ?? null )
			? (string) ( $market['currency'][0] ?? '' )
			: (string) ( $market['currency'] ?? '' );
	}

	/**
	 * @param WCProductAdapter $product
	 *
	 * @return BatchInvalidProductEntry|true
	 */
	protected function validate_product( WCProductAdapter $product ) {
		$violations = $this->validator->validate( $product );

		if ( 0 !== count( $violations ) ) {
			$invalid_product = new BatchInvalidProductEntry( $product->get_wc_product()->get_id() );
			$invalid_product->map_validation_violations( $violations );

			return $invalid_product;
		}

		return true;
	}

	/**
	 * Filters and returns an array of request entries for Google products that should no longer be submitted for the selected target audience.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductIDRequestEntry[]
	 */
	public function generate_stale_products_request_entries( array $products ): array {
		$feed_labels     = $this->market_service->get_all_feed_labels();
		$request_entries = [];
		foreach ( $products as $product ) {
			$google_ids = $this->meta_handler->get_google_ids( $product ) ?: [];
			$stale_ids  = array_diff_key( $google_ids, array_flip( $feed_labels ) );
			foreach ( $stale_ids as $stale_id ) {
				$request_entries[ $stale_id ] = new BatchProductIDRequestEntry(
					$product->get_id(),
					$stale_id
				);
			}
		}

		return $request_entries;
	}

	/**
	 * Returns an array of request entries for Google products that should no
	 * longer be submitted for every target country.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductIDRequestEntry[]
	 */
	public function generate_stale_countries_request_entries( array $products ): array {
		$main_feed_label = $this->market_service->get_main_feed_label();

		$request_entries = [];
		foreach ( $products as $product ) {
			$google_ids = $this->meta_handler->get_google_ids( $product ) ?: [];
			$stale_ids  = array_diff_key( $google_ids, array_flip( [ $main_feed_label ] ) );
			foreach ( $stale_ids as $stale_id ) {
				$request_entries[ $stale_id ] = new BatchProductIDRequestEntry(
					$product->get_id(),
					$stale_id
				);
			}
		}

		return $request_entries;
	}
}
