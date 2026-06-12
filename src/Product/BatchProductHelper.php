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
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
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
	 * @var TargetAudience
	 */
	protected $target_audience;

	/**
	 * @var AttributeMappingRulesQuery
	 */
	protected $attribute_mapping_rules_query;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * BatchProductHelper constructor.
	 *
	 * @param ProductMetaHandler         $meta_handler
	 * @param ProductHelper              $product_helper
	 * @param TargetAudience             $target_audience
	 * @param AttributeMappingRulesQuery $attribute_mapping_rules_query
	 * @param AttributeManager           $attribute_manager
	 */
	public function __construct(
		ProductMetaHandler $meta_handler,
		ProductHelper $product_helper,
		TargetAudience $target_audience,
		AttributeMappingRulesQuery $attribute_mapping_rules_query,
		AttributeManager $attribute_manager
	) {
		$this->meta_handler                  = $meta_handler;
		$this->product_helper                = $product_helper;
		$this->target_audience               = $target_audience;
		$this->attribute_mapping_rules_query = $attribute_mapping_rules_query;
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
	 * products into variations, skips products that are not sync-ready, and builds
	 * a ProductInput per product targeting the main target country.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{product: WC_Product, country: string, input: ProductInput}>
	 */
	public function generate_mapi_update_entries( array $products ): array {
		$entries          = [];
		$country          = $this->target_audience->get_main_target_country();
		$target_countries = $this->target_audience->get_target_countries();
		$mapping_rules    = $this->attribute_mapping_rules_query->get_results();

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

				$parent = $product instanceof WC_Product_Variation
					? $this->product_helper->get_wc_product( $product->get_parent_id() )
					: null;

				$attributes = $this->attribute_manager->get_all_values( $product );
				if ( null !== $parent ) {
					$attributes = array_merge( $this->attribute_manager->get_all_values( $parent ), $attributes );
				}

				$entries[] = [
					'product' => $product,
					'country' => $country,
					'input'   => ( new WCProductInputAdapter( $product, $country, $parent, $target_countries, $attributes, $mapping_rules ) )->get_product_input(),
				];
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
	 * countries that are no longer in the target audience.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	public function generate_stale_products_delete_entries( array $products ): array {
		$target_audience = $this->target_audience->get_target_countries();

		return $this->build_stale_entries( $products, $target_audience );
	}

	/**
	 * Generate MAPI delete entries for products whose stored google_ids target
	 * countries other than the main target country.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Product[] $products
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	public function generate_stale_countries_delete_entries( array $products ): array {
		return $this->build_stale_entries( $products, [ $this->target_audience->get_main_target_country() ] );
	}

	/**
	 * Build MAPI delete entries from products by keeping only the google_ids whose
	 * country is NOT in $keep_countries. Malformed ids are skipped.
	 *
	 * @param WC_Product[] $products
	 * @param string[]     $keep_countries
	 *
	 * @return array<int, array{wc_product_id: int, google_id: string, input: ProductInput}>
	 */
	protected function build_stale_entries( array $products, array $keep_countries ): array {
		$entries = [];

		foreach ( $products as $product ) {
			$google_ids = $this->meta_handler->get_google_ids( $product ) ?: [];
			$stale_ids  = array_diff_key( $google_ids, array_flip( $keep_countries ) );

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
