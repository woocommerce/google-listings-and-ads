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
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
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
	 * @var TargetAudience
	 */
	protected $target_audience;

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
	 * @param TargetAudience             $target_audience
	 * @param AttributeMappingRulesQuery $attribute_mapping_rules_query
	 * @param MarketService              $market_service
	 */
	public function __construct(
		ProductMetaHandler $meta_handler,
		ProductHelper $product_helper,
		ValidatorInterface $validator,
		ProductFactory $product_factory,
		TargetAudience $target_audience,
		AttributeMappingRulesQuery $attribute_mapping_rules_query,
		MarketService $market_service
	) {
		$this->meta_handler                  = $meta_handler;
		$this->product_helper                = $product_helper;
		$this->validator                     = $validator;
		$this->product_factory               = $product_factory;
		$this->target_audience               = $target_audience;
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
		$request_entries    = [];
		$mapping_rules      = $this->attribute_mapping_rules_query->get_results();
		$is_multilingual    = $this->market_service->has_multilingual_support();

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

				$target_countries    = $this->target_audience->get_target_countries();
				$main_target_country = $this->target_audience->get_main_target_country();

				if ( $is_multilingual ) {
					// Multilingual path: generate one adapter per language × currency pair across all markets.
					$feed_label_pairs = $this->get_unique_feed_label_pairs();

					foreach ( $feed_label_pairs as $pair ) {
						$language   = $pair['language'];
						$currency   = $pair['currency'];
						$feed_label = "{$language}-{$currency}";

						$market_product  = $this->market_service->get_product_in_language( $product, $language ) ?? $product;
						$adapted_product = $this->product_factory->create_for_market( $market_product, $pair['target_country'], $mapping_rules, $feed_label, $language, $currency );

						// When WCML multi-currency is off, create_for_market() returns an adapter
						// with no price (WPML active but WCML inactive). Log and skip rather than
						// letting it fail validation with a cryptic "does not pass validation" message.
						if ( null === $adapted_product->getPrice() ) {
							do_action(
								'woocommerce_gla_debug_message',
								sprintf(
									'Skipping product (ID: %s, feedLabel: %s) because no price could be resolved for currency %s. Ensure WCML multi-currency is enabled.',
									$product->get_id(),
									$feed_label,
									$currency
								),
								__METHOD__
							);
							continue;
						}

						$validation_result = $this->validate_product( $adapted_product );
						if ( $validation_result instanceof BatchInvalidProductEntry ) {
							$this->mark_as_invalid( $validation_result );

							do_action(
								'woocommerce_gla_debug_message',
								sprintf( 'Skipping product (ID: %s, feedLabel: %s) because it does not pass validation: %s', $product->get_id(), $feed_label, wp_json_encode( $validation_result ) ),
								__METHOD__
							);

							continue;
						}

						array_walk( $pair['shipping_countries'], [ $adapted_product, 'add_shipping_country' ] );

						$request_entries[] = new BatchProductRequestEntry(
							$product->get_id(),
							$adapted_product
						);
					}
				} else {
					// Non-multilingual path: existing single-entry behaviour, unchanged.
					$adapted_product   = $this->product_factory->create( $product, $main_target_country, $mapping_rules );
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

					// add shipping for all selected target countries
					array_walk( $target_countries, [ $adapted_product, 'add_shipping_country' ] );

					$request_entries[] = new BatchProductRequestEntry(
						$product->get_id(),
						$adapted_product
					);
				}
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
	 * In multilingual mode, stale detection compares stored google_ids keys against the set of active
	 * feedLabels across all markets. Products whose google_ids still contain only legacy country-code
	 * keys (pre-multilingual schema) are skipped until the normal sync cycle has had a chance to
	 * upload the new feedLabel-keyed entries — this prevents a mass delete-before-reupload when
	 * WPML is first activated on a store with existing synced products.
	 *
	 * In non-multilingual mode, the existing country-key comparison is used.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductIDRequestEntry[]
	 */
	public function generate_stale_products_request_entries( array $products ): array {
		$is_multilingual = $this->market_service->has_multilingual_support();

		if ( $is_multilingual ) {
			$active_keys = $this->product_helper->get_active_feed_labels_from_markets( $this->market_service->get_markets() );
		} else {
			$active_keys = $this->target_audience->get_target_countries();
		}

		$active_keys_flip = array_flip( $active_keys );
		$request_entries  = [];

		foreach ( $products as $product ) {
			$google_ids = $this->meta_handler->get_google_ids( $product ) ?: [];

			// In multilingual mode, skip stale detection for products that have not yet been
			// re-synced with feedLabel keys. Without any active feedLabel in google_ids, deleting
			// the old country-keyed entries now would leave the product temporarily absent from MC.
			if ( $is_multilingual ) {
				$has_feed_label_entry = ! empty( array_intersect_key( $google_ids, $active_keys_flip ) );
				if ( ! $has_feed_label_entry ) {
					continue;
				}
			}

			$stale_ids = array_diff_key( $google_ids, $active_keys_flip );
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
	 * In multilingual mode, stale detection is feedLabel-based (handled by
	 * generate_stale_products_request_entries), so this method is a no-op.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductIDRequestEntry[]
	 */
	public function generate_stale_countries_request_entries( array $products ): array {
		// In multilingual mode, feedLabel cleanup covers staleness — country-based check is not applicable.
		if ( $this->market_service->has_multilingual_support() ) {
			return [];
		}

		$main_target_country = $this->target_audience->get_main_target_country();

		$request_entries = [];
		foreach ( $products as $product ) {
			$google_ids = $this->meta_handler->get_google_ids( $product ) ?: [];
			$stale_ids  = array_diff_key( $google_ids, array_flip( [ $main_target_country ] ) );
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
	 * Returns unique language × currency pairs across all markets for multilingual sync.
	 *
	 * Each entry carries the correct targetCountry and shipping countries for that pair:
	 * - Primary markets (have a 'countries' key): canonical country from TargetAudience; shipping to all target countries.
	 * - Secondary markets (have a 'country' key): that market's single country; shipping to that country only.
	 *
	 * @since 2.9.0
	 *
	 * @return array[] Array of maps with keys: language, currency, target_country, shipping_countries.
	 */
	protected function get_unique_feed_label_pairs(): array {
		$markets          = $this->market_service->get_markets();
		$target_countries = $this->target_audience->get_target_countries();
		$main_country     = $this->target_audience->get_main_target_country();
		$pairs            = [];

		foreach ( $markets as $market_id => $market ) {
			$languages  = $market['language'] ?? [];
			$currencies = $market['currency'] ?? [];

			if ( empty( $languages ) || empty( $currencies ) ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf(
						'Skipping market (ID: %s) in feed-label pair generation: empty language or currency array.',
						$market_id
					),
					__METHOD__
				);
				continue;
			}

			// Primary market is keyed 'primary' in the get_markets() return value.
			// Secondary markets use their own ID as the key.
			if ( 'primary' === $market_id ) {
				$target_country     = $main_country;
				$shipping_countries = $target_countries;
			} elseif ( ! empty( $market['country'] ) ) {
				$target_country     = $market['country'];
				$shipping_countries = [ $market['country'] ];
			} else {
				continue;
			}

			foreach ( $languages as $language ) {
				foreach ( $currencies as $currency ) {
					$key = "{$language}-{$currency}";
					if ( ! isset( $pairs[ $key ] ) ) {
						$pairs[ $key ] = [
							'language'           => $language,
							'currency'           => $currency,
							'target_country'     => $target_country,
							'shipping_countries' => $shipping_countries,
						];
					} else {
						// Multiple markets share this feed-label pair — merge shipping countries
						// so all of them appear in the MC data source (e.g. CM + UG both use fr-EUR).
						$pairs[ $key ]['shipping_countries'] = array_values(
							array_unique(
								array_merge( $pairs[ $key ]['shipping_countries'], $shipping_countries )
							)
						);
					}
				}
			}
		}

		return array_values( $pairs );
	}
}
