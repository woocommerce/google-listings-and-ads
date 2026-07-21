<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
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
	 */
	public function __construct(
		ProductMetaHandler $meta_handler,
		ProductHelper $product_helper,
		ValidatorInterface $validator,
		ProductFactory $product_factory,
		AttributeMappingRulesQuery $attribute_mapping_rules_query,
		MarketService $market_service,
		WPML $wpml,
		AttributeManager $attribute_manager
	) {
		$this->meta_handler                  = $meta_handler;
		$this->product_helper                = $product_helper;
		$this->validator                     = $validator;
		$this->product_factory               = $product_factory;
		$this->attribute_mapping_rules_query = $attribute_mapping_rules_query;
		$this->market_service                = $market_service;
		$this->wpml                          = $wpml;
		$this->attribute_manager             = $attribute_manager;
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

				// Stage entries per product so a throw or validation failure in
				// any secondary market discards the whole product's entries
				// preventing the primary feed from receiving the product while
				// a secondary feed silently misses it.
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

					if ( ! $this->can_skip_unchanged_product( $product, $primary_hash ) ) {
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

							continue 2;
						}

						$primary_currency_input = $this->generate_product_input( $product, $main_feed_label, $primary_currency_label, $this->market_service->get_all_countries(), $mapping_rules, $product_language, $primary_currency );
						$primary_currency_hash  = $this->product_input_hash( $primary_currency_input );

						if ( $this->can_skip_unchanged_product( $product, $primary_currency_hash ) ) {
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

					// The derived label carries the language the entry syncs
					// under and the currency its prices are submitted in
					// (both falling back to site defaults inside the
					// derivation). A market with no configured languages
					// accepts every product under its site-language label.
					// Every participating currency produces its own entry.
					$market_language = empty( $market['language'] ) ? '' : $product_language;

					foreach ( $this->market_service->get_participating_currencies( $market ) as $market_currency ) {
						if ( ! $this->product_priced_in_currency( $product, $market_currency ) ) {
							do_action(
								'woocommerce_gla_debug_message',
								sprintf( 'Skipping the %s entry of secondary market %s for product (ID: %s): its price cannot be converted into that currency.', $market_currency, $market_id, $product->get_id() ),
								__METHOD__
							);

							continue;
						}

						$market_feed_label = $this->market_service->get_market_feed_label( $market['feed_label'], $market_language, $market_currency );

						// Store-currency entries need no conversion, so they
						// carry no currency override and price exactly as a
						// single-currency market's entries always have.
						$currency_override = get_woocommerce_currency() === $market_currency ? '' : $market_currency;

						$secondary_validation = $this->validate_product(
							$this->product_factory->create( $product, $market['country'], $mapping_rules, $market_feed_label, $product_language, $currency_override )
						);
						if ( $secondary_validation instanceof BatchInvalidProductEntry ) {
							$this->mark_as_invalid( $secondary_validation );

							do_action(
								'woocommerce_gla_debug_message',
								sprintf( 'Skipping product (ID: %s) because it does not pass validation for secondary market %s: %s', $product->get_id(), $market_id, wp_json_encode( $secondary_validation ) ),
								__METHOD__
							);

							continue 3;
						}

						// Secondary market shipping is scoped to the market's own country.
						$secondary_input = $this->generate_product_input( $product, $market['country'], $market_feed_label, [ $market['country'] ], $mapping_rules, $product_language, $currency_override );
						$secondary_hash  = $this->product_input_hash( $secondary_input );

						if ( $this->can_skip_unchanged_product( $product, $secondary_hash ) ) {
							continue;
						}

						$product_entries[] = [
							'product' => $product,
							'country' => $market['country'],
							'input'   => $secondary_input,
							'hash'    => $secondary_hash,
						];
					}
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
	 * Build the Merchant API ProductInput for a product and market.
	 *
	 * @param WC_Product $product
	 * @param string     $target_country     Country used for shipping and tax rules.
	 * @param string     $feed_label         Feed label for the product input.
	 * @param string[]   $shipping_countries Countries to add shipping entries for.
	 * @param array      $mapping_rules      Attribute mapping rules to apply.
	 * @param string     $language           Optional ISO 639-1 language override.
	 * @param string     $currency_override  Optional ISO 4217 currency code overriding the store currency.
	 *
	 * @return ProductInput
	 */
	protected function generate_product_input( WC_Product $product, string $target_country, string $feed_label, array $shipping_countries, array $mapping_rules, string $language = '', string $currency_override = '' ): ProductInput {
		$parent = $product instanceof WC_Product_Variation
			? $this->product_helper->get_wc_product( $product->get_parent_id() )
			: null;

		$attributes = $this->attribute_manager->get_all_values( $product );
		if ( null !== $parent ) {
			$attributes = array_merge( $this->attribute_manager->get_all_values( $parent ), $attributes );
		}

		$adapter = new WCProductInputAdapter( $product, $target_country, $parent, $shipping_countries, $attributes, $mapping_rules, $feed_label, $language, $currency_override, $this->wpml );

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
	 * A stable hash of the ProductInput payload, used to skip re-syncing products
	 * whose Merchant API payload is unchanged since the last successful sync.
	 *
	 * @param ProductInput $input
	 *
	 * @return string
	 */
	protected function product_input_hash( ProductInput $input ): string {
		return md5( (string) wp_json_encode( $input->to_array() ) );
	}

	/**
	 * Whether a product can be skipped because its payload is unchanged since the last
	 * successful sync. Products old enough to be due for expiry resubmission are never
	 * skipped, and woocommerce_gla_force_product_resync forces a full re-sync.
	 *
	 * @param WC_Product $product
	 * @param string     $hash    The current ProductInput hash.
	 *
	 * @return bool
	 */
	protected function can_skip_unchanged_product( WC_Product $product, string $hash ): bool {
		if ( apply_filters( 'woocommerce_gla_force_product_resync', false, $product ) ) {
			return false;
		}

		if ( $this->meta_handler->get_sync_hash( $product ) !== $hash ) {
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
				$identity = $this->parse_mapi_identity( (string) $google_id );
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
				$identity = $this->parse_mapi_identity( (string) $google_id );
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
