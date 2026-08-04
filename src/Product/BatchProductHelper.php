<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

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
	 * @var WPML
	 */
	protected $wpml;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * @var MapiDataSourcesService
	 */
	protected $data_sources;

	/**
	 * BatchProductHelper constructor.
	 *
	 * @param ProductMetaHandler         $meta_handler
	 * @param ProductHelper              $product_helper
	 * @param ValidatorInterface         $validator
	 * @param ProductFactory             $product_factory
	 * @param AttributeMappingRulesQuery $attribute_mapping_rules_query
	 * @param MarketService              $market_service
	 * @param WPML                       $wpml
	 * @param AttributeManager           $attribute_manager
	 * @param MapiDataSourcesService     $data_sources
	 */
	public function __construct(
		ProductMetaHandler $meta_handler,
		ProductHelper $product_helper,
		ValidatorInterface $validator,
		ProductFactory $product_factory,
		AttributeMappingRulesQuery $attribute_mapping_rules_query,
		MarketService $market_service,
		WPML $wpml,
		AttributeManager $attribute_manager,
		MapiDataSourcesService $data_sources
	) {
		$this->meta_handler                  = $meta_handler;
		$this->product_helper                = $product_helper;
		$this->validator                     = $validator;
		$this->product_factory               = $product_factory;
		$this->attribute_mapping_rules_query = $attribute_mapping_rules_query;
		$this->market_service                = $market_service;
		$this->wpml                          = $wpml;
		$this->attribute_manager             = $attribute_manager;
		$this->data_sources                  = $data_sources;
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
	 * Removes a deleted entry's Google ID from its product's tracked IDs.
	 *
	 * Only the entry's own ID is removed, so entries synced for the product's
	 * other markets or languages keep their tracking. When the last tracked ID
	 * is removed the product is marked fully un-synced. Entries carrying no
	 * Google product fall back to marking the product fully un-synced.
	 *
	 * @param BatchProductEntry $product_entry
	 */
	public function remove_google_id_for_entry( BatchProductEntry $product_entry ) {
		try {
			$wc_product = $this->product_helper->get_wc_product( $product_entry->get_wc_product_id() );
		} catch ( InvalidValue $exception ) {
			return;
		}

		$google_product = $product_entry->get_google_product();
		if ( null === $google_product || empty( $google_product->getId() ) ) {
			$this->product_helper->mark_as_unsynced( $wc_product );
			return;
		}

		$this->product_helper->remove_google_id( $wc_product, $google_product->getId() );
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
	 * Generate MAPI ProductInput entries for the given products. Expands variable
	 * products into variations, skips products that are not sync-ready, validates
	 * each market copy locally, and builds a ProductInput per product per matching
	 * market (the primary market plus every secondary market whose language list
	 * accepts the product).
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{product: WC_Product, country: string, input: ProductInput, hash: string}>
	 */
	public function generate_mapi_update_entries( array $products ): array {
		$entries       = [];
		$mapping_rules = $this->attribute_mapping_rules_query->get_results();
		$wpml_active   = $this->market_service->has_multilingual_support();

		foreach ( $products as $product ) {
			$this->validate_instanceof( $product, WC_Product::class );

			try {
				if ( ! $this->product_helper->is_sync_ready( $product ) ) {
					continue;
				}

				if ( $product instanceof WC_Product_Variable ) {
					$entries = array_merge( $entries, $this->generate_mapi_update_entries( $product->get_available_variations( 'objects' ) ) );
					continue;
				}

				$product_language = $wpml_active ? $this->wpml->get_post_language( $product->get_id() ) : '';

				// Stage per product so a throw discards the whole product's entries (see the catch);
				// a per-(language, currency) validation failure skips only that one feed.
				$product_entries = [];

				$primary_market = $this->market_service->get_primary_market();

				if ( $this->product_matches_market( $product_language, $primary_market, $wpml_active ) ) {
					// The primary market never stores scalar country/feed_label values
					// (it is multi-country); its feed label is the main target country,
					// kept bare for every language so existing entries keep their
					// Merchant Center identity.
					$main_feed_label = $this->market_service->get_main_feed_label();

					$validation_result = $this->validate_product(
						$this->product_factory->create( $product, $main_feed_label, $mapping_rules, $main_feed_label, $product_language )
					);
					if ( $validation_result instanceof BatchInvalidProductEntry ) {
						$this->mark_as_invalid( $validation_result );

						do_action(
							'woocommerce_gla_debug_message',
							sprintf( 'Skipping product (ID: %s) because it does not pass validation: %s', $product->get_id(), wp_json_encode( $validation_result ) ),
							__METHOD__
						);

						continue;
					}

					// Add shipping for all countries across all markets.
					$primary_input = $this->generate_product_input( $product, $main_feed_label, $main_feed_label, $this->market_service->get_all_countries(), $mapping_rules, $product_language );
					$primary_hash  = $this->product_input_hash( $primary_input );

					if ( ! $this->can_skip_unchanged_product( $product, $primary_hash, $primary_input->get_content_language(), $primary_input->get_feed_label() ) ) {
						$product_entries[] = [
							'product' => $product,
							'country' => $main_feed_label,
							'input'   => $primary_input,
							'hash'    => $primary_hash,
						];
					}

					// The bare-label entry above carries the store currency;
					// each additional participating primary currency gets its
					// own derived-label entry with converted prices.
					foreach ( $this->market_service->get_participating_currencies( $primary_market ) as $primary_currency ) {
						if ( get_woocommerce_currency() === $primary_currency ) {
							continue;
						}

						if ( ! $this->product_priced_in_currency( $product, $primary_currency ) ) {
							do_action(
								'woocommerce_gla_debug_message',
								sprintf( 'Skipping the %s primary market entry for product (ID: %s): its price cannot be converted into that currency.', $primary_currency, $product->get_id() ),
								__METHOD__
							);

							continue;
						}

						$primary_currency_label = $this->market_service->get_market_feed_label( $main_feed_label, $product_language, $primary_currency );

						$primary_currency_validation = $this->validate_product(
							$this->product_factory->create( $product, $main_feed_label, $mapping_rules, $primary_currency_label, $product_language, $primary_currency )
						);
						if ( $primary_currency_validation instanceof BatchInvalidProductEntry ) {
							$this->mark_as_invalid( $primary_currency_validation );

							do_action(
								'woocommerce_gla_debug_message',
								sprintf( 'Skipping product (ID: %s) because it does not pass validation for the %s primary market feed: %s', $product->get_id(), $primary_currency, wp_json_encode( $primary_currency_validation ) ),
								__METHOD__
							);

							continue;
						}

						$primary_currency_input = $this->generate_product_input( $product, $main_feed_label, $primary_currency_label, $this->market_service->get_all_countries(), $mapping_rules, $product_language, $primary_currency );
						$primary_currency_hash  = $this->product_input_hash( $primary_currency_input );

						if ( $this->can_skip_unchanged_product( $product, $primary_currency_hash, $primary_currency_input->get_content_language(), $primary_currency_input->get_feed_label() ) ) {
							continue;
						}

						$product_entries[] = [
							'product' => $product,
							'country' => $main_feed_label,
							'input'   => $primary_currency_input,
							'hash'    => $primary_currency_hash,
						];
					}
				}

				// Participating markets only: a market priced in a non-store
				// currency sits out while conversion is unavailable, because
				// submitting unconverted prices against its currency-derived
				// label and shipping service is rejected by Google.
				foreach ( $this->market_service->get_participating_markets() as $market_id => $market ) {
					if ( 'primary' === $market_id ) {
						continue;
					}

					if ( ! $this->product_matches_market( $product_language, $market, $wpml_active ) ) {
						continue;
					}

					$product_entries = array_merge(
						$product_entries,
						$this->generate_secondary_market_entries( $product, (string) $market_id, $market, $product_language, $mapping_rules )
					);
				}

				if ( ! empty( $product_entries ) ) {
					array_push( $entries, ...$product_entries );
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

		return $entries;
	}

	/**
	 * One MAPI update entry per (product language, enabled currency) for a secondary market. A pair
	 * that fails validation (e.g. no price in that currency), cannot be priced in the currency
	 * (unless the market's fixed exchange rate converts it), or whose payload is unchanged since
	 * the last sync is skipped; the rest are emitted.
	 *
	 * @param WC_Product $product
	 * @param string     $market_id
	 * @param array      $market
	 * @param string     $product_language
	 * @param array      $mapping_rules
	 *
	 * @return array<int, array{product: WC_Product, country: string, input: ProductInput, hash: string}>
	 */
	protected function generate_secondary_market_entries( WC_Product $product, string $market_id, array $market, string $product_language, array $mapping_rules ): array {
		// The derived label carries the entry's language and currency; a market with no languages
		// uses the site-language label.
		$market_language = empty( $market['language'] ) ? '' : $product_language;
		$currencies      = $this->market_service->get_market_currencies_for_language( $market, $market_language );
		$entries         = [];

		if ( empty( $currencies ) ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf( 'Product (ID: %s) not added to market %s: no currency is enabled for language "%s".', $product->get_id(), $market_id, $market_language ),
				__METHOD__
			);
		}

		// A configured exchange rate converts any product's price at sync time, so it keeps a
		// currency's entries flowing when WPML cannot convert for this product.
		$market_exchange_rate = self::extract_exchange_rate( $market );

		foreach ( $currencies as $market_currency ) {
			if ( ! $this->product_priced_in_currency( $product, $market_currency ) && $market_exchange_rate <= 0 ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Skipping the %s entry of secondary market %s for product (ID: %s): its price cannot be converted into that currency.', $market_currency, $market_id, $product->get_id() ),
					__METHOD__
				);

				continue;
			}

			$market_feed_label = $this->market_service->get_market_feed_label( $market['feed_label'], $market_language, $market_currency );

			// Store-currency entries need no conversion, so they carry no currency override and
			// price exactly as a single-currency market's entries always have.
			$currency_override = get_woocommerce_currency() === $market_currency ? '' : $market_currency;

			$validation = $this->validate_product(
				$this->product_factory->create( $product, $market['country'], $mapping_rules, $market_feed_label, $product_language, $currency_override )
			);
			if ( $validation instanceof BatchInvalidProductEntry ) {
				$this->mark_as_invalid( $validation );

				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Skipping product (ID: %s) for market %s currency %s because it does not pass validation: %s', $product->get_id(), $market_id, $market_currency, wp_json_encode( $validation ) ),
					__METHOD__
				);

				continue;
			}

			// Secondary market shipping is scoped to the market's own country.
			$input = $this->generate_product_input( $product, $market['country'], $market_feed_label, [ $market['country'] ], $mapping_rules, $product_language, $currency_override, $market_exchange_rate );
			$hash  = $this->product_input_hash( $input );

			if ( $this->can_skip_unchanged_product( $product, $hash, $input->get_content_language(), $input->get_feed_label() ) ) {
				continue;
			}

			$entries[] = [
				'product' => $product,
				'country' => $market['country'],
				'input'   => $input,
				'hash'    => $hash,
			];
		}

		return $entries;
	}

	/**
	 * Build the Merchant API ProductInput for a product and market.
	 *
	 * @param WC_Product $product
	 * @param string     $target_country     Country used for shipping and tax rules.
	 * @param string     $feed_label         Feed label for the product input.
	 * @param string[]   $shipping_countries Countries to add shipping entries for.
	 * @param array      $mapping_rules      Attribute mapping rules to apply.
	 * @param string     $language           Optional ISO 639-1 language override.
	 * @param string     $currency_override  Optional ISO 4217 currency code overriding the store currency.
	 * @param float      $exchange_rate      Fixed market exchange rate used to convert prices when WPML conversion is unavailable; 0.0 when unset.
	 *
	 * @return ProductInput
	 */
	protected function generate_product_input( WC_Product $product, string $target_country, string $feed_label, array $shipping_countries, array $mapping_rules, string $language = '', string $currency_override = '', float $exchange_rate = 0.0 ): ProductInput {
		$parent = $product instanceof WC_Product_Variation
			? $this->product_helper->get_wc_product( $product->get_parent_id() )
			: null;

		$attributes = $this->attribute_manager->get_all_values( $product );
		if ( null !== $parent ) {
			$attributes = array_merge( $this->attribute_manager->get_all_values( $parent ), $attributes );
		}

		$adapter = new WCProductInputAdapter( $product, $target_country, $parent, $shipping_countries, $attributes, $mapping_rules, $feed_label, $language, $currency_override, $this->wpml, $exchange_rate );

		return $adapter->get_product_input();
	}

	/**
	 * Determines whether a product's language matches a market's accepted languages.
	 *
	 * When WPML is inactive the matching step is skipped and every product matches every market.
	 * When WPML is active an empty market language list also matches every product (it means
	 * "this market accepts any product"). Otherwise the product's WPML language must be in
	 * the market's language list, after converting any locale-form values ("en_US") to the
	 * short-code form ("en") that WPML uses.
	 *
	 * @param string $product_language The product's WPML language code, or empty string.
	 * @param array  $market           A market config array as returned by MarketService.
	 * @param bool   $wpml_active      Whether WPML is active for this site.
	 *
	 * @return bool
	 */
	private static function product_matches_market( string $product_language, array $market, bool $wpml_active ): bool {
		if ( ! $wpml_active ) {
			return true;
		}

		$market_languages = is_array( $market['language'] ?? null ) ? $market['language'] : [];

		if ( empty( $market_languages ) ) {
			return true;
		}

		$normalised = array_map(
			static function ( $value ) {
				$value = (string) $value;

				return false === strpos( $value, '_' ) ? $value : strtolower( substr( $value, 0, 2 ) );
			},
			$market_languages
		);

		return in_array( $product_language, $normalised, true );
	}

	/**
	 * Whether a product can be priced in the given currency.
	 *
	 * The store currency always qualifies. Any other currency qualifies only
	 * when WPML can produce a converted or manually set price for the product,
	 * so a product is never submitted with a store-currency price under a
	 * non-store-currency feed label.
	 *
	 * @param WC_Product $product
	 * @param string     $currency ISO 4217 currency code.
	 *
	 * @return bool
	 */
	private function product_priced_in_currency( WC_Product $product, string $currency ): bool {
		if ( get_woocommerce_currency() === $currency ) {
			return true;
		}

		return null !== $this->wpml->get_product_price_in_currency( $product, $currency );
	}

	/**
	 * Extracts a market's fixed exchange rate from its config.
	 *
	 * @param array $market A market config array as returned by MarketService.
	 *
	 * @return float The rate, or 0.0 when none is configured.
	 */
	private static function extract_exchange_rate( array $market ): float {
		return isset( $market['exchange_rate'] ) && is_numeric( $market['exchange_rate'] )
			? (float) $market['exchange_rate']
			: 0.0;
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
	 * A stable hash of the ProductInput payload, used to skip re-syncing entries
	 * whose Merchant API payload is unchanged since the last successful sync.
	 *
	 * The entry's resolved data source resource name is mixed into the hash. The
	 * name embeds the Merchant Center account and the data source identity, so
	 * connecting a different account or recreating a deleted data source changes
	 * the hash, and an entry the destination has never received cannot be skipped
	 * as "unchanged". Resolving also guarantees the data source exists before the
	 * entry is submitted. The resolution runs even for entries that are then
	 * skipped as unchanged: the resource name is part of the hashed payload, so
	 * it must be resolved before the skip check can compare hashes.
	 *
	 * @param ProductInput $input
	 *
	 * @return string
	 * @throws MerchantApiException When resolving the entry's data source fails; callers
	 *                              generating entries catch this per product.
	 */
	protected function product_input_hash( ProductInput $input ): string {
		$data_source = $this->data_sources->ensure_data_source_for(
			$input->get_content_language(),
			$input->get_feed_label()
		);

		return md5( $data_source . (string) wp_json_encode( $input->to_array() ) );
	}

	/**
	 * Whether an entry can be skipped because its payload is unchanged since the last
	 * successful sync of the same (content language, feed label) entry. Hashes are
	 * stored keyed per entry; a legacy single-string value never matches, which forces
	 * one full resubmission and migrates the meta to the keyed format. Products old
	 * enough to be due for expiry resubmission are never skipped, and
	 * woocommerce_gla_force_product_resync forces a full re-sync.
	 *
	 * @param WC_Product $product
	 * @param string     $hash             The current ProductInput hash.
	 * @param string     $content_language The entry's content language.
	 * @param string     $feed_label       The entry's feed label.
	 *
	 * @return bool
	 */
	protected function can_skip_unchanged_product( WC_Product $product, string $hash, string $content_language, string $feed_label ): bool {
		if ( apply_filters( 'woocommerce_gla_force_product_resync', false, $product ) ) {
			return false;
		}

		$hashes = $this->meta_handler->get_sync_hash( $product );

		if ( ! is_array( $hashes ) ) {
			return false;
		}

		// The "|" delimiter follows the same convention as the MapiDataSourcesService
		// cache key; never reuse this key format with values that can contain "|".
		if ( ( $hashes[ $content_language . '|' . $feed_label ] ?? null ) !== $hash ) {
			return false;
		}

		// Clamp to the expiry-resubmission window so a filtered freshness can never let an
		// unchanged product be skipped past the point it is due for resubmission.
		$max_freshness = ProductRepository::RESUBMIT_EXPIRY_DAYS * DAY_IN_SECONDS;
		$freshness     = min( (int) apply_filters( 'woocommerce_gla_sync_hash_freshness', $max_freshness ), $max_freshness );
		$synced_at     = (int) $this->meta_handler->get_synced_at( $product );

		return $synced_at > ( time() - $freshness );
	}

	/**
	 * Generate MAPI delete entries for the given products.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	public function generate_mapi_delete_entries( array $products ): array {
		$entries = [];

		foreach ( $products as $product ) {
			$this->validate_instanceof( $product, WC_Product::class );

			if ( $product instanceof WC_Product_Variable ) {
				$entries = array_merge( $entries, $this->generate_mapi_delete_entries( $product->get_available_variations( 'objects' ) ) );
				continue;
			}

			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			foreach ( $google_ids as $google_id ) {
				$identity = $this->parse_deletable_identity( (string) $google_id );
				if ( null === $identity ) {
					continue;
				}

				[ $language, $feed, $offer_id ] = $identity;

				$entries[] = [
					'wc_product_id' => $product->get_id(),
					'google_id'     => (string) $google_id,
					'input'         => new ProductInput( $offer_id, $language, $feed ),
				];
			}
		}

		return $entries;
	}

	/**
	 * Parse a MAPI Google product id (e.g. `en~US~gla_29`) into its identity array
	 * [language, feed, offerId].
	 *
	 * Tilde-only by design: a legacy Content API id (`online:en:US:gla_29`) returns null so the
	 * status-read path skips it (the Merchant API rejects legacy ids as invalid resource names).
	 * Deletion paths that must also accept legacy ids use parse_deletable_identity() instead.
	 *
	 * @param string $google_id
	 *
	 * @return array{0: string, 1: string, 2: string}|null
	 */
	public function parse_mapi_identity( string $google_id ): ?array {
		$parts = explode( '~', $google_id, 3 );
		if ( count( $parts ) !== 3 ) {
			return null;
		}

		return [ $parts[0], $parts[1], $parts[2] ];
	}

	/**
	 * Resolve a stored google product id into a MAPI identity [language, feed, offerId] for
	 * deletion, accepting both the native MAPI id (`en~US~gla_29`) and the legacy Content API id
	 * (`online:en:US:gla_29`) that pre-migration syncs stored. This lets products synced before the
	 * MAPI cutover still be removed from Merchant Center. Returns null if neither format matches.
	 *
	 * @param string $google_id
	 *
	 * @return array{0: string, 1: string, 2: string}|null
	 */
	public function parse_deletable_identity( string $google_id ): ?array {
		$identity = $this->parse_mapi_identity( $google_id );
		if ( null !== $identity ) {
			return $identity;
		}

		// Legacy Content API id: online:language:country:offerId. The target country is the feed
		// for these single-feed products; only the `online` channel was ever stored, so anything
		// else is not a legacy id we can delete.
		$parts = explode( ':', $google_id, 4 );
		if ( count( $parts ) === 4 && 'online' === $parts[0] ) {
			return [ $parts[1], $parts[2], $parts[3] ];
		}

		return null;
	}

	/**
	 * Generate MAPI delete entries for products whose stored google_ids include
	 * feed labels that no longer belong to any configured market.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	public function generate_stale_products_delete_entries( array $products ): array {
		return $this->build_stale_entries( $products, $this->market_service->get_all_feed_labels() );
	}

	/**
	 * Generate MAPI delete entries for products whose tracking keys no longer
	 * belong to any configured market or language.
	 *
	 * The diff runs against every valid derived feed label rather than only the
	 * main feed label, so entries belonging to secondary markets and to
	 * per-language feeds survive while keys from dropped target countries are
	 * still flagged as stale.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	public function generate_stale_countries_delete_entries( array $products ): array {
		return $this->build_stale_entries( $products, $this->market_service->get_all_feed_labels() );
	}

	/**
	 * Build MAPI delete entries from products by keeping only the google_ids whose
	 * feed label is NOT in $keep_feed_labels. Malformed ids are skipped.
	 *
	 * @param WC_Product[] $products
	 * @param string[]     $keep_feed_labels
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	protected function build_stale_entries( array $products, array $keep_feed_labels ): array {
		$entries = [];

		foreach ( $products as $product ) {
			$google_ids = $this->meta_handler->get_google_ids( $product ) ?: [];
			$stale_ids  = array_diff_key( $google_ids, array_flip( $keep_feed_labels ) );

			foreach ( $stale_ids as $google_id ) {
				$identity = $this->parse_deletable_identity( (string) $google_id );
				if ( null === $identity ) {
					continue;
				}

				[ $language, $feed, $offer_id ] = $identity;

				$entries[] = [
					'wc_product_id' => $product->get_id(),
					'google_id'     => (string) $google_id,
					'input'         => new ProductInput( $offer_id, $language, $feed ),
				];
			}
		}

		return $entries;
	}
}
