<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\MerchantCenterTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Google_Service_ShoppingContent_Product as GoogleProduct;
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
class BatchProductHelper implements Service, OptionsAwareInterface {

	use MerchantCenterTrait;

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
	 * BatchProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param ProductHelper      $product_helper
	 * @param ValidatorInterface $validator
	 */
	public function __construct( ProductMetaHandler $meta_handler, ProductHelper $product_helper, ValidatorInterface $validator ) {
		$this->meta_handler   = $meta_handler;
		$this->product_helper = $product_helper;
		$this->validator      = $validator;
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
		$wc_product_id  = $product_entry->get_wc_product_id();
		$google_product = $product_entry->get_google_product();

		$this->validate_instanceof( $google_product, GoogleProduct::class );

		$this->meta_handler->update_synced_at( $wc_product_id, time() );

		// merge and update all google product ids
		$current_google_ids = $this->meta_handler->get_google_ids( $wc_product_id );
		$current_google_ids = ! empty( $current_google_ids ) ? $current_google_ids : [];
		$google_ids         = array_unique( array_merge( $current_google_ids, [ $google_product->getTargetCountry() => $google_product->getId() ] ) );
		$this->meta_handler->update_google_ids( $wc_product_id, $google_ids );

		// check if product is synced completely and remove any previous errors if it is
		$synced_countries = array_keys( $google_ids );
		$target_countries = $this->get_target_countries();
		if ( count( $synced_countries ) === count( $target_countries ) && empty( array_diff( $synced_countries, $target_countries ) ) ) {
			$this->meta_handler->delete_errors( $wc_product_id );
			$this->meta_handler->delete_failed_sync_attempts( $wc_product_id );
		}

		// mark the parent product as synced if it's a variation
		$wc_product = wc_get_product( $wc_product_id );
		if ( $wc_product instanceof WC_Product_Variation && ! empty( $wc_product->get_parent_id() ) ) {
			$this->mark_as_synced( new BatchProductEntry( $wc_product->get_parent_id(), $google_product ) );
		}
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	public function mark_as_unsynced( BatchProductEntry $product_entry ) {
		$wc_product_id = $product_entry->get_wc_product_id();
		$this->meta_handler->delete_synced_at( $wc_product_id );
		$this->meta_handler->delete_google_ids( $wc_product_id );
		$this->meta_handler->delete_errors( $wc_product_id );
		$this->meta_handler->delete_failed_sync_attempts( $wc_product_id );

		// mark the parent product as un-synced if it's a variation
		$wc_product = wc_get_product( $wc_product_id );
		if ( $wc_product instanceof WC_Product_Variation && ! empty( $wc_product->get_parent_id() ) ) {
			$this->mark_as_unsynced( new BatchProductEntry( $wc_product->get_parent_id(), null ) );
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
		$wc_product_id = $product_entry->get_wc_product_id();
		$errors        = $product_entry->get_errors();

		// bail if no errors exist
		if ( empty( $errors ) ) {
			return;
		}

		$this->meta_handler->update_errors( $wc_product_id, $errors );

		if ( $this->has_internal_error( $product_entry ) ) {
			// update failed sync attempts count in case of internal errors
			$failed_attempts = ! empty( $this->meta_handler->get_failed_sync_attempts( $wc_product_id ) ) ?
				$this->meta_handler->get_failed_sync_attempts( $wc_product_id ) :
				0;
			$this->meta_handler->update_failed_sync_attempts( $wc_product_id, $failed_attempts + 1 );
		}

		// mark the parent product as invalid if it's a variation
		$wc_product = wc_get_product( $wc_product_id );
		if ( $wc_product instanceof WC_Product_Variation && ! empty( $wc_product->get_parent_id() ) ) {
			$wc_parent_id = $wc_product->get_parent_id();

			$parent_errors = ! empty( $this->meta_handler->get_errors( $wc_parent_id ) ) ?
				$this->meta_handler->get_errors( $wc_parent_id ) :
				[];

			$parent_errors[ $wc_product_id ] = $errors;

			$this->mark_as_invalid( new BatchInvalidProductEntry( $wc_parent_id, $parent_errors ) );
		}
	}

	/**
	 * Generates an array map containing the Google product IDs as key and the WooCommerce product IDs as values.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductRequestEntry[]
	 */
	public function generate_delete_request_entries( array $products ): array {
		$request_entries = [];
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product_Variable ) {
				$request_entries = array_merge( $request_entries, $this->generate_delete_request_entries( $product->get_available_variations( 'objects' ) ) );
				continue;
			}

			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			foreach ( $google_ids as $google_id ) {
				$request_entries[] = new BatchProductRequestEntry(
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
		$request_entries  = [];
		$target_countries = $this->get_target_countries();

		foreach ( $products as $product ) {
			if ( ChannelVisibility::DONT_SYNC_AND_SHOW === $this->product_helper->get_visibility( $product ) ) {
				continue;
			}

			if ( $product instanceof WC_Product_Variable ) {
				$request_entries = array_merge( $request_entries, $this->validate_and_generate_update_request_entries( $product->get_available_variations( 'objects' ) ) );
				continue;
			}

			// check if the product validates for just one of its target countries
			$validation_result = $this->validate_product( $this->product_helper->generate_adapted_product( $product, $target_countries[0] ) );
			if ( $validation_result instanceof BatchInvalidProductEntry ) {
				$this->mark_as_invalid( $validation_result );
				continue;
			}

			// return batch request entries for each target country
			$product_entries = array_map(
				function ( $target_country ) use ( $product ) {
					return new BatchProductRequestEntry(
						$product->get_id(),
						ProductHelper::generate_adapted_product( $product, $target_country )
					);
				},
				$target_countries
			);
			$request_entries = array_merge( $request_entries, $product_entries );
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
	 * Whether the invalid product entry has internal syncing errors
	 *
	 * @param BatchInvalidProductEntry $invalid_product_entry
	 *
	 * @return bool
	 */
	protected function has_internal_error( BatchInvalidProductEntry $invalid_product_entry ): bool {
		$errors = $invalid_product_entry->get_errors();

		return ! empty( $errors[ GoogleProductService::INTERNAL_ERROR_REASON ] );
	}

	/**
	 * Filters the list of invalid product entries and returns an array of WooCommerce product IDs with internal errors
	 *
	 * @param BatchInvalidProductEntry[] $args
	 *
	 * @return int[] An array of WooCommerce product ids.
	 */
	public function get_internal_error_products( array $args ): array {
		$internal_error_ids = [];
		foreach ( $args as $invalid_product ) {
			if ( $this->has_internal_error( $invalid_product ) ) {
				$product_id = $invalid_product->get_wc_product_id();

				$internal_error_ids[ $product_id ] = $product_id;
			}
		}

		return $internal_error_ids;
	}
}
