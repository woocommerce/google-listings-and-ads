<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AvailabilityDate;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithValueOptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use DateTimeZone;
use WC_DateTime;
use WC_Product;
use WC_Product_Variation;
use WP_Error;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductAbilityFields
 *
 * Adds the Google data of a product to WooCommerce's product abilities: fields under
 * `extensions.google`, query filters under `filters.extensions.google`, and a validator.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 *
 * @since x.x.x
 */
class ProductAbilityFields implements Service, Registerable {

	use PluginHelper;

	public const ABILITY_NAMESPACE = 'google';

	/**
	 * Maximum lengths that WCProductAdapter::loadValidatorMetadata allows, keyed by attribute ID.
	 */
	protected const MAX_LENGTHS = [
		'mpn'      => 70,
		'size'     => 100,
		'color'    => 100,
		'material' => 200,
		'pattern'  => 100,
	];

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var MerchantIssueTable
	 */
	protected $issue_table;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * ProductAbilityFields constructor.
	 *
	 * @param AttributeManager      $attribute_manager
	 * @param ProductHelper         $product_helper
	 * @param ProductMetaHandler    $meta_handler
	 * @param MerchantCenterService $merchant_center
	 * @param MerchantIssueTable    $issue_table
	 * @param wpdb                  $wpdb
	 */
	public function __construct(
		AttributeManager $attribute_manager,
		ProductHelper $product_helper,
		ProductMetaHandler $meta_handler,
		MerchantCenterService $merchant_center,
		MerchantIssueTable $issue_table,
		wpdb $wpdb
	) {
		$this->attribute_manager = $attribute_manager;
		$this->product_helper    = $product_helper;
		$this->meta_handler      = $meta_handler;
		$this->merchant_center   = $merchant_center;
		$this->issue_table       = $issue_table;
		$this->wpdb              = $wpdb;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_extensible_abilities_init',
			function () {
				$this->register_with_abilities();
			}
		);
	}

	/**
	 * Register the fields, the filters and the validator.
	 */
	protected function register_with_abilities(): void {
		$common = [
			'resource'  => 'product',
			'namespace' => self::ABILITY_NAMESPACE,
		];

		foreach ( $this->get_fields() as $field ) {
			ExtensibleAbilities::register_field( $common + $field );
		}
		foreach ( $this->get_filters() as $filter ) {
			ExtensibleAbilities::register_query_filter( $common + $filter );
		}
		ExtensibleAbilities::register_validator(
			$common + [
				'callback' => function ( WC_Product $product ) {
					return $this->validate_availability_date( $product );
				},
			]
		);
		ExtensibleAbilities::register_sync(
			$common + [
				'callback' => function ( array $saved, $ability, array $context ): void {
					$this->sync_hidden_product_issues( $saved, $context['input'] ?? [] );
				},
			]
		);
	}

	/**
	 * The fields of a product: its Google attributes, then its sync data.
	 *
	 * @return array[] Arguments for ExtensibleAbilities::register_field(), without the resource and the namespace.
	 */
	public function get_fields(): array {
		$fields = [];
		foreach ( AttributeManager::get_available_attribute_types() as $attribute_type ) {
			$fields[] = $this->get_attribute_field( $attribute_type );
		}

		$product_types = ProductSyncer::get_supported_product_types();

		$fields[] = [
			'field'           => 'visibility',
			'object_types'    => $product_types,
			'schema'          => [
				'type'        => 'string',
				'enum'        => ChannelVisibility::ALLOWED_VALUES,
				'title'       => __( 'Google visibility', 'google-listings-and-ads' ),
				'description' => __( 'Whether the product syncs to Google. A variation follows its parent, so write it on the parent. A null value resets it to sync-and-show.', 'google-listings-and-ads' ),
			],
			'get_callback'    => function ( WC_Product $product ): string {
				return $this->product_helper->get_channel_visibility( $product ) ?: ChannelVisibility::SYNC_AND_SHOW;
			},
			'update_callback' => function ( WC_Product $product, ?string $value ) {
				return $this->stage_visibility( $product, $value );
			},
		];
		$fields[] = [
			'field'        => 'sync_status',
			'object_types' => $product_types,
			'schema'       => [
				'type'        => 'string',
				'enum'        => SyncStatus::ALLOWED_VALUES,
				'title'       => __( 'Google sync status', 'google-listings-and-ads' ),
				'description' => __( 'The state of the last sync. WooCommerce schedules a sync on every product save when Merchant Center is connected. The field is left out for a product that never synced.', 'google-listings-and-ads' ),
			],
			'get_callback' => function ( WC_Product $product ): ?string {
				return $this->product_helper->get_sync_status( $product );
			},
		];
		$fields[] = [
			'field'        => 'mc_status',
			'object_types' => $product_types,
			'schema'       => [
				'type'        => 'string',
				'enum'        => MCStatus::ALLOWED_VALUES,
				'title'       => __( 'Merchant Center status', 'google-listings-and-ads' ),
				'description' => __( 'The status that Google reports. A variation reports the status of its parent.', 'google-listings-and-ads' ),
			],
			'get_callback' => function ( WC_Product $product ): ?string {
				return $this->product_helper->get_mc_status( $product );
			},
		];
		$fields[] = [
			'field'        => 'synced_at',
			'object_types' => $product_types,
			'schema'       => [
				'type'        => 'integer',
				'title'       => __( 'Last synced at', 'google-listings-and-ads' ),
				'description' => __( 'Unix timestamp of the last successful sync.', 'google-listings-and-ads' ),
			],
			'get_callback' => function ( WC_Product $product ): ?int {
				return $this->meta_handler->get_synced_at( $product ) ? (int) $this->meta_handler->get_synced_at( $product ) : null;
			},
		];
		$fields[] = [
			'field'        => 'sync_errors',
			'object_types' => $product_types,
			'schema'       => [
				'type'        => 'array',
				'items'       => [ 'type' => 'string' ],
				'title'       => __( 'Google sync errors', 'google-listings-and-ads' ),
				'description' => __( 'Why the last sync failed. The field is left out when there are none.', 'google-listings-and-ads' ),
			],
			'get_callback' => function ( WC_Product $product ): ?array {
				$errors = $this->product_helper->get_validation_errors( $product );
				return empty( $errors ) ? null : array_map( 'strval', array_values( $errors ) );
			},
		];
		$fields[] = [
			'field'        => 'merchant_issues',
			'object_types' => $product_types,
			'schema'       => [
				'type'        => 'array',
				'title'       => __( 'Merchant Center issues', 'google-listings-and-ads' ),
				'description' => __( 'What Google reports against the product, from the last status refresh. A variation reports the issues of its parent. The field is left out when there are none.', 'google-listings-and-ads' ),
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'issue'      => [ 'type' => 'string' ],
						'code'       => [ 'type' => 'string' ],
						'severity'   => [ 'type' => 'string' ],
						'action'     => [ 'type' => 'string' ],
						'action_url' => [ 'type' => 'string' ],
					],
				],
			],
			'get_callback' => function ( WC_Product $product ): ?array {
				return $this->get_merchant_issues( $product );
			},
		];

		return $fields;
	}

	/**
	 * The query filters of the products-query ability.
	 *
	 * @return array[] Arguments for ExtensibleAbilities::register_query_filter(), without the resource and the namespace.
	 */
	public function get_filters(): array {
		$attribute_names = array_map( [ $this, 'get_field_name' ], array_values( AttributeManager::get_available_attribute_ids() ) );

		return [
			[
				'filter'   => 'sync_status',
				'schema'   => [
					'type'        => 'string',
					'enum'        => SyncStatus::ALLOWED_VALUES,
					'description' => __( 'Limit results to products with this Google sync status. Add `type` to find variations. `not-synced` also finds the products that never synced.', 'google-listings-and-ads' ),
				],
				'callback' => function ( string $value ): array {
					return $this->get_meta_clause( ProductMetaHandler::KEY_SYNC_STATUS, $value, SyncStatus::NOT_SYNCED );
				},
			],
			[
				'filter'   => 'mc_status',
				'schema'   => [
					'type'        => 'string',
					'enum'        => MCStatus::ALLOWED_VALUES,
					'description' => __( 'Limit results to products with this Merchant Center status. `not_synced` also finds the products without a status.', 'google-listings-and-ads' ),
				],
				'callback' => function ( string $value ): array {
					return $this->get_meta_clause( ProductMetaHandler::KEY_MC_STATUS, $value, MCStatus::NOT_SYNCED );
				},
			],
			[
				'filter'   => 'visibility',
				'schema'   => [
					'type'        => 'string',
					'enum'        => ChannelVisibility::ALLOWED_VALUES,
					'description' => __( 'Limit results to products with this Google visibility. `sync-and-show` also finds the products without a value.', 'google-listings-and-ads' ),
				],
				'callback' => function ( string $value ): array {
					return $this->get_meta_clause( ProductMetaHandler::KEY_VISIBILITY, $value, ChannelVisibility::SYNC_AND_SHOW );
				},
			],
			[
				'filter'   => 'has_issues',
				'schema'   => [
					'type'        => 'boolean',
					'description' => __( 'Limit results to products that Merchant Center reports issues for (true) or not (false).', 'google-listings-and-ads' ),
				],
				'callback' => function ( bool $value ): array {
					return [ $value ? 'include_ids' : 'exclude_ids' => $this->get_product_ids_with_issues() ];
				},
			],
			[
				'filter'   => 'missing_attributes',
				'schema'   => [
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
						'enum' => $attribute_names,
					],
					'description' => __( 'Limit results to products with no value for all of these Google attributes. Add `type` to skip the product types an attribute does not apply to. Attribute mapping rules can still supply a value at sync time.', 'google-listings-and-ads' ),
				],
				'callback' => function ( array $value ): array {
					return [ 'meta_query' => array_map( [ $this, 'get_missing_clause' ], $value ) ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				},
			],
		];
	}

	/**
	 * Refuse a change that leaves a product that syncs on backorder with no availability date,
	 * because the sync would then fail with an error that the caller never sees.
	 *
	 * A variation that a product-create call builds has no parent yet, so it cannot be checked here.
	 *
	 * @param WC_Product $product The changed product, before the save.
	 *
	 * @return WP_Error|null
	 */
	public function validate_availability_date( WC_Product $product ): ?WP_Error {
		$attribute_id = AvailabilityDate::get_id();
		$is_orphan    = $product instanceof WC_Product_Variation && $product->get_parent_id() <= 0;
		$has_date     = in_array( $product->get_type(), $this->attribute_manager->get_product_types_for_attribute( $attribute_id ), true );
		if ( $is_orphan || ! $has_date || ! $this->merchant_center->is_connected() ) {
			return null;
		}

		if ( ! empty( $this->attribute_manager->get_value( $product, $attribute_id ) ) || ! $product->is_on_backorder( 1 ) || ! $this->product_helper->is_sync_ready( $product ) ) {
			return null;
		}

		$stock_changed = ! empty( array_intersect_key( $product->get_changes(), array_flip( [ 'stock_status', 'stock_quantity', 'manage_stock', 'backorders' ] ) ) );
		$date_removed  = $product->get_id() > 0 && metadata_exists( 'post', $product->get_id(), $this->prefix_meta_key( $attribute_id ) );
		if ( ! $stock_changed && ! $date_removed ) {
			return null;
		}

		return new WP_Error(
			'availability_date_required',
			__( 'Google requires an availability date for a product on backorder. Send extensions.google.availability_date in the same call, or set extensions.google.visibility to dont-sync-and-show.', 'google-listings-and-ads' ),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Delete the issues of a product that the call hid from Google, because a hidden product no longer reports them.
	 *
	 * @param WC_Product[] $saved Saved objects, the primary object first.
	 * @param array        $input Ability input.
	 */
	public function sync_hidden_product_issues( array $saved, array $input ): void {
		$visibility = $input['extensions'][ self::ABILITY_NAMESPACE ]['visibility'] ?? null;
		if ( ChannelVisibility::DONT_SYNC_AND_SHOW !== $visibility || empty( $saved ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- $this->wpdb is the injected wpdb instance.
		$this->wpdb->delete(
			$this->issue_table->get_name(),
			[
				'type'       => MerchantStatuses::TYPE_PRODUCT,
				'product_id' => $saved[0]->get_id(),
			]
		);
	}

	/**
	 * The field of a Google attribute.
	 *
	 * @param string $attribute_type Attribute class name.
	 *
	 * @return array
	 */
	protected function get_attribute_field( string $attribute_type ): array {
		$attribute_id = call_user_func( [ $attribute_type, 'get_id' ] );
		$is_gtin      = GTIN::get_id() === $attribute_id;

		return [
			'field'           => $this->get_field_name( $attribute_id ),
			'object_types'    => $this->attribute_manager->get_product_types_for_attribute( $attribute_id ),
			'schema'          => $this->get_attribute_schema( $attribute_type, $attribute_id ),
			'get_callback'    => function ( WC_Product $product ) use ( $attribute_id, $is_gtin ) {
				return $is_gtin ? $this->get_gtin( $product ) : $this->attribute_manager->get_value( $product, $attribute_id );
			},
			'update_callback' => function ( WC_Product $product, $value ) use ( $attribute_type, $attribute_id, $is_gtin ) {
				return $is_gtin ? $this->stage_gtin( $product, $value ) : $this->stage_attribute( $product, $attribute_type, $attribute_id, $value );
			},
		];
	}

	/**
	 * The schema of a Google attribute.
	 *
	 * @param string $attribute_type Attribute class name.
	 * @param string $attribute_id   Attribute ID.
	 *
	 * @return array
	 */
	protected function get_attribute_schema( string $attribute_type, string $attribute_id ): array {
		$types = [
			'int'     => 'integer',
			'integer' => 'integer',
			'bool'    => 'boolean',
			'boolean' => 'boolean',
		];

		$schema = [
			'type'  => $types[ call_user_func( [ $attribute_type, 'get_value_type' ] ) ] ?? 'string',
			'title' => method_exists( $attribute_type, 'get_name' ) ? call_user_func( [ $attribute_type, 'get_name' ] ) : $attribute_id,
		];

		if ( is_subclass_of( $attribute_type, WithValueOptionsInterface::class ) ) {
			$schema['enum'] = array_map( 'strval', array_keys( call_user_func( [ $attribute_type, 'get_value_options' ] ) ) );
		}
		if ( isset( self::MAX_LENGTHS[ $attribute_id ] ) ) {
			$schema['maxLength'] = self::MAX_LENGTHS[ $attribute_id ];
		}
		if ( 'integer' === $schema['type'] ) {
			$schema['minimum'] = 0;
		}
		if ( AvailabilityDate::get_id() === $attribute_id ) {
			$schema['format']      = 'date-time';
			$schema['description'] = __( 'The date a product on backorder becomes available. Without an offset, the time is UTC.', 'google-listings-and-ads' );
		}
		if ( GTIN::get_id() === $attribute_id ) {
			$schema['description'] = __( 'GTIN, UPC, EAN or ISBN of 8, 12, 13 or 14 digits. It is stored in the product\'s global unique ID.', 'google-listings-and-ads' );
		}

		return $schema;
	}

	/**
	 * Write a Google attribute to the product in memory.
	 *
	 * @param WC_Product $product        The product.
	 * @param string     $attribute_type Attribute class name.
	 * @param string     $attribute_id   Attribute ID.
	 * @param mixed      $value          The value, or null to delete it.
	 */
	protected function stage_attribute( WC_Product $product, string $attribute_type, string $attribute_id, $value ): void {
		if ( null === $value ) {
			$this->attribute_manager->stage_delete( $product, $attribute_id );
			return;
		}

		if ( AvailabilityDate::get_id() === $attribute_id ) {
			$date = new WC_DateTime( $value, new DateTimeZone( 'UTC' ) );
			$date->setTimezone( new DateTimeZone( 'UTC' ) );
			$value = (string) $date;
		}

		$this->attribute_manager->stage_update( $product, new $attribute_type( $value ) );
	}

	/**
	 * The GTIN that a sync sends: the digits of the product's global unique ID, else the older GTIN attribute.
	 *
	 * @see WCProductAdapter::map_gtin
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return string|null
	 */
	protected function get_gtin( WC_Product $product ): ?string {
		$gtin = preg_replace( '/[^0-9]/', '', (string) $product->get_global_unique_id() );

		return '' !== $gtin ? $gtin : $this->attribute_manager->get_value( $product, GTIN::get_id() );
	}

	/**
	 * Write the GTIN to the product's global unique ID in memory. A sync reads it from there first.
	 *
	 * @param WC_Product  $product The product.
	 * @param string|null $value   The GTIN, or null to delete it.
	 *
	 * @return WP_Error|null
	 */
	protected function stage_gtin( WC_Product $product, ?string $value ): ?WP_Error {
		if ( null === $value ) {
			$product->set_global_unique_id( '' );
			$this->attribute_manager->stage_delete( $product, GTIN::get_id() );
			return null;
		}

		if ( ! preg_match( WCProductAdapter::GTIN_PATTERN, $value ) ) {
			return new WP_Error(
				'invalid_gtin',
				__( 'A GTIN has 8, 12, 13 or 14 digits and nothing else.', 'google-listings-and-ads' ),
				[ 'status' => 400 ]
			);
		}

		$product->set_global_unique_id( $value );

		return null;
	}

	/**
	 * Write the visibility to the product in memory. A variation has none of its own, so it only
	 * accepts the value it already reports, which lets a client send a read result back.
	 *
	 * @param WC_Product  $product The product.
	 * @param string|null $value   The visibility, or null to delete it.
	 *
	 * @return WP_Error|null
	 */
	protected function stage_visibility( WC_Product $product, ?string $value ): ?WP_Error {
		if ( $product instanceof WC_Product_Variation ) {
			if ( ( $this->product_helper->get_channel_visibility( $product ) ?: ChannelVisibility::SYNC_AND_SHOW ) === $value ) {
				return null;
			}

			return new WP_Error(
				'visibility_on_parent',
				__( 'A variation follows the Google visibility of its parent. Write it on the parent product.', 'google-listings-and-ads' ),
				[ 'status' => 400 ]
			);
		}

		if ( null === $value ) {
			$this->meta_handler->stage_delete( $product, ProductMetaHandler::KEY_VISIBILITY );
			return null;
		}

		$this->meta_handler->stage_update( $product, ProductMetaHandler::KEY_VISIBILITY, ChannelVisibility::cast( $value )->get() );

		return null;
	}

	/**
	 * The issues that Merchant Center reports against a product. They are stored on the parent of a variation.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return array[]|null Null when there are none.
	 */
	protected function get_merchant_issues( WC_Product $product ): ?array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT `issue`, `code`, `severity`, `action`, `action_url` FROM %i WHERE `type` = %s AND `product_id` = %d ORDER BY `issue`',
				$this->issue_table->get_name(),
				MerchantStatuses::TYPE_PRODUCT,
				$product->get_parent_id() ?: $product->get_id()
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return empty( $rows ) ? null : $rows;
	}

	/**
	 * The IDs of the products that Merchant Center reports issues for.
	 *
	 * @return int[]
	 */
	protected function get_product_ids_with_issues(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		return array_map(
			'intval',
			$this->wpdb->get_col(
				$this->wpdb->prepare(
					'SELECT DISTINCT `product_id` FROM %i WHERE `type` = %s AND `product_id` > 0',
					$this->issue_table->get_name(),
					MerchantStatuses::TYPE_PRODUCT
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * A meta query for a value. The value that a product without the meta reports also matches a missing meta.
	 *
	 * @param string $key         Meta key, without the prefix.
	 * @param string $value       The value to match.
	 * @param string $unset_value The value that a product without the meta reports.
	 *
	 * @return array
	 */
	protected function get_meta_clause( string $key, string $value, string $unset_value ): array {
		$meta_key = $this->prefix_meta_key( $key );
		$clause   = [
			'key'   => $meta_key,
			'value' => $value,
		];
		if ( $value !== $unset_value ) {
			return [ 'meta_query' => [ $clause ] ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		return [
			'meta_query' => [
				'relation' => 'OR',
				$clause,
				[
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				],
			],
		];
	}

	/**
	 * A meta query for products with no value for an attribute. A GTIN also needs an empty global unique ID.
	 *
	 * @param string $field_name The field name of the attribute.
	 *
	 * @return array
	 */
	protected function get_missing_clause( string $field_name ): array {
		$missing = static function ( string $meta_key ): array {
			return [
				'relation' => 'OR',
				[
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'   => $meta_key,
					'value' => '',
				],
			];
		};

		foreach ( AttributeManager::get_available_attribute_ids() as $attribute_id ) {
			if ( $this->get_field_name( $attribute_id ) !== $field_name ) {
				continue;
			}
			$clause = $missing( $this->prefix_meta_key( $attribute_id ) );
			if ( GTIN::get_id() === $attribute_id ) {
				return [ $clause, $missing( '_global_unique_id' ) ];
			}
			return $clause;
		}

		return [];
	}

	/**
	 * The field name of an attribute. A field name is a lowercase slug, and some attribute IDs are in camel case.
	 *
	 * @param string $attribute_id Attribute ID.
	 *
	 * @return string
	 */
	protected function get_field_name( string $attribute_id ): string {
		return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $attribute_id ) );
	}
}
