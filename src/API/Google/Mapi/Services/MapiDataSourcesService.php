<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiDataSourcesService
 *
 * Resolves the plugin's data sources: the product data source per (contentLanguage,
 * feedLabel) pair and the promotion data source per (contentLanguage, targetCountry)
 * pair. Both flavours share one discover-or-create routine parameterised by a type
 * descriptor; they differ only in the data source field, the secondary identity field,
 * and the cache-key prefix. Reuses an existing matching data source when one is already
 * on the merchant, otherwise creates one. Resolved names are cached in options.
 * Resolution is lazy: the first write into a given market triggers the lookup/create.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiDataSourcesService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** Display name prefix used when creating the plugin's data sources. */
	public const DATA_SOURCE_DISPLAY_NAME = 'Google for WooCommerce';

	/** Descriptor for the primary product data source. */
	private const PRODUCT_SOURCE = [
		'source_field'            => 'primaryProductDataSource',
		'match_field'             => 'feedLabel',
		// Namespaced (was '') to invalidate resolutions cached before the resolver began adopting
		// and renaming a pre-existing primary source (e.g. the legacy "Content API" one), so the
		// one-time rename runs on existing installs.
		'cache_prefix'            => 'product|',
		// Only a primaryProductDataSource has a `destinations` field in the Merchant API,
		// so only product creation requests online-only destinations.
		'set_online_destinations' => true,
	];

	/** Descriptor for the promotion data source. */
	private const PROMOTION_SOURCE = [
		'source_field' => 'promotionDataSource',
		'match_field'  => 'targetCountry',
		'cache_prefix' => 'promotion|',
	];

	/**
	 * Destinations requested on a newly created product data source. Without an explicit
	 * `destinations` list, Google inherits the account's local-inventory participation at
	 * creation time, which can produce a local-only source (see is_local_only_product_source()).
	 * Matches the marketing methods of the plugin's historical "Content API" source.
	 */
	private const ONLINE_PRODUCT_DESTINATIONS = [ 'SHOPPING_ADS', 'FREE_LISTINGS' ];

	/** Destinations that only serve local (not online) product listings. */
	private const LOCAL_PRODUCT_DESTINATIONS = [ 'LOCAL_INVENTORY_ADS', 'FREE_LOCAL_LISTINGS' ];

	/**
	 * Every destination known to serve online products, used as positive evidence that a source
	 * is not local-only. Deliberately wider than ONLINE_PRODUCT_DESTINATIONS, which is the
	 * narrower set the plugin *requests* when creating a source: a source the merchant set up
	 * themselves may serve online products through a destination the plugin would never ask for.
	 *
	 * Detection matches against this list rather than treating "not local" as online, so a
	 * destination Google adds in future is not silently read as proof of online capability.
	 * Erring toward local-only costs one redundant data source; erring the other way re-adopts
	 * an unusable source and kills product sync outright (GOOWOO-921).
	 */
	private const ONLINE_CAPABLE_DESTINATIONS = [ 'SHOPPING_ADS', 'FREE_LISTINGS', 'DISPLAY_ADS', 'YOUTUBE_SHOPPING' ];

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * Resource names confirmed to exist on the account during this request, so repeat
	 * resolutions of the same data source do not re-issue a dataSources.get.
	 *
	 * @var array<string, true>
	 */
	private $verified_data_sources = [];

	/**
	 * MapiDataSourcesService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Return the resource name of the primary product data source for the given
	 * (contentLanguage, feedLabel) pair. Discovers or creates one as needed and
	 * caches the result.
	 *
	 * @param string $content_language Language code.
	 * @param string $feed_label       Feed label.
	 *
	 * @return string Data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function ensure_data_source_for( string $content_language, string $feed_label ): string {
		return $this->ensure_data_source( self::PRODUCT_SOURCE, $content_language, $feed_label );
	}

	/**
	 * Return the resource name of the promotion data source for the given
	 * (contentLanguage, targetCountry) pair. Discovers or creates one as needed
	 * and caches the result.
	 *
	 * @param string $content_language Language code.
	 * @param string $target_country   Target country code.
	 *
	 * @return string Data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function ensure_promotion_data_source_for( string $content_language, string $target_country ): string {
		return $this->ensure_data_source( self::PROMOTION_SOURCE, $content_language, $target_country );
	}

	/**
	 * Return (from cache, else discover, else create) the resource name of the data
	 * source of the given type for a (contentLanguage, match) pair.
	 *
	 * @param array  $type             One of the *_SOURCE descriptors.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 *
	 * @return string Data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	private function ensure_data_source( array $type, string $content_language, string $match_value ): string {
		$cache_key = $type['cache_prefix'] . $content_language . '|' . $match_value;
		$cache     = (array) $this->options->get( OptionsInterface::MAPI_DATA_SOURCES, [] );

		if ( isset( $cache[ $cache_key ] ) && '' !== $cache[ $cache_key ] ) {
			$name = (string) $cache[ $cache_key ];

			if ( isset( $this->verified_data_sources[ $name ] ) || $this->verify_cached_source( $type, $name ) ) {
				$this->verified_data_sources[ $name ] = true;
				return $name;
			}

			// The cached data source is gone, or it turns out to be unusable (fileInput set, or
			// local-only): drop it and re-resolve below.
			unset( $cache[ $cache_key ] );
			$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );
		}

		$existing = $this->find_existing_data_source( $type, $content_language, $match_value );
		$name     = null !== $existing
			? $this->adopt_data_source( $existing, $content_language, $match_value )
			: $this->create_data_source( $type, $content_language, $match_value );

		return $this->cache_resolved_source( $type, $content_language, $match_value, $name );
	}

	/**
	 * Force-create a fresh product data source for the given (contentLanguage, feedLabel) pair,
	 * bypassing discovery entirely, and cache it in place of whatever was cached before.
	 *
	 * Used to recover from a channel-mismatch 400 (see is_channel_mismatch_failure()): unlike a
	 * "data source not found" 404, simply forgetting the cache and re-resolving would deterministically
	 * re-list and re-adopt the exact same source, since is_unusable_data_source() cannot detect it
	 * as local-only from this response shape (see is_local_only_product_source()). Creating a fresh
	 * source with explicit online destinations is the only response-shape-independent way to make
	 * forward progress.
	 *
	 * @param string $content_language Language code.
	 * @param string $feed_label       Feed label.
	 *
	 * @return string The freshly created data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function recreate_data_source_for( string $content_language, string $feed_label ): string {
		$name = $this->create_data_source( self::PRODUCT_SOURCE, $content_language, $feed_label );

		return $this->cache_resolved_source( self::PRODUCT_SOURCE, $content_language, $feed_label, $name );
	}

	/**
	 * Write a resolved data source name into the option cache and mark it verified for the rest
	 * of this request, so a repeat resolution of the same pair does not re-issue a dataSources.get.
	 *
	 * @param array  $type             One of the *_SOURCE descriptors.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 * @param string $name             The resolved data source resource name.
	 *
	 * @return string $name, unchanged, so callers can return the result of this call directly.
	 */
	private function cache_resolved_source( array $type, string $content_language, string $match_value, string $name ): string {
		$cache_key = $type['cache_prefix'] . $content_language . '|' . $match_value;
		$cache     = (array) $this->options->get( OptionsInterface::MAPI_DATA_SOURCES, [] );

		$cache[ $cache_key ] = $name;
		$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );

		// Just observed on the account (discovered or created), so it exists without a further request.
		$this->verified_data_sources[ $name ] = true;

		return $name;
	}

	/**
	 * Whether a cached data source resource name is still safe to use: it exists on the account
	 * and is not unusable for writes (see is_unusable_data_source()).
	 *
	 * Only a 404 proves absence, so any other error (auth, transient 5xx) keeps the cached name:
	 * discarding it there would force a needless list-or-create, and a genuinely missing source
	 * still surfaces on the insert that follows.
	 *
	 * @param array  $type One of the *_SOURCE descriptors.
	 * @param string $name Data source resource name.
	 *
	 * @return bool
	 */
	private function verify_cached_source( array $type, string $name ): bool {
		try {
			$source = $this->client->get( sprintf( '%s/%s', MapiPaths::DATASOURCES, $name ) );
		} catch ( MerchantApiException $exception ) {
			return 404 !== $exception->get_http_status();
		}

		return ! $this->is_unusable_data_source( $type, $source );
	}

	/**
	 * Whether a data source can never be adopted or trusted as the plugin's data source, because
	 * MAPI item inserts into it are permanently rejected. Composes every known disqualifying
	 * property found in production so far:
	 *  - fileInput set (400 "API data sources cannot have a fileInput field set"), or
	 *  - (product sources only) local-only, per is_local_only_product_source() (400 "The provided
	 *    data source channel does not match product channel").
	 *
	 * @param array $type   One of the *_SOURCE descriptors.
	 * @param array $source A data source, as returned by the MAPI.
	 *
	 * @return bool
	 */
	private function is_unusable_data_source( array $type, array $source ): bool {
		if ( $this->resource_uses_file_input( $source ) ) {
			return true;
		}

		$descriptor = $source[ $type['source_field'] ] ?? null;

		return is_array( $descriptor ) && $this->is_local_only_product_source( $descriptor );
	}

	/**
	 * Whether a data source (as returned by the MAPI) has fileInput set: an API item
	 * insert into a file-input data source is rejected with a 400 ("API data sources cannot
	 * have a fileInput field set").
	 *
	 * @param array $data_source A data source, as returned by the MAPI.
	 *
	 * @return bool
	 */
	private function resource_uses_file_input( array $data_source ): bool {
		return ! empty( $data_source['fileInput'] ) && is_array( $data_source['fileInput'] );
	}

	/**
	 * Whether a primaryProductDataSource/promotionDataSource descriptor is local-only: MAPI item
	 * inserts into such a source are rejected with a 400 ("The provided data source channel does
	 * not match product channel"), so it can never be adopted or trusted as the plugin's product
	 * data source. Always false for a promotion descriptor, since PromotionDataSource has neither
	 * a legacyLocal flag nor a destinations list.
	 *
	 * A descriptor is local-only when either:
	 *  - its legacyLocal flag is set (Google's own "only targets local destinations" marker), or
	 *  - it has an explicit destinations list in which a local destination is enabled and no
	 *    destination known to serve online products (ONLINE_CAPABLE_DESTINATIONS) is enabled.
	 *    An enabled destination in neither list is no evidence either way, so it is ignored
	 *    rather than read as online.
	 *
	 * An absent or empty destinations list is not treated as local-only: Google's inference in
	 * that case is unobservable from this response, and every existing (non-local) data source in
	 * this service's own test fixtures omits it, so treating absence as local-only would make the
	 * resolver reject sources it currently adopts correctly.
	 *
	 * @param array $descriptor The primaryProductDataSource/promotionDataSource object of a data source.
	 *
	 * @return bool
	 */
	private function is_local_only_product_source( array $descriptor ): bool {
		if ( ! empty( $descriptor['legacyLocal'] ) ) {
			return true;
		}

		$destinations = $descriptor['destinations'] ?? [];
		if ( ! is_array( $destinations ) || empty( $destinations ) ) {
			return false;
		}

		$online_enabled = false;
		$local_enabled  = false;

		foreach ( $destinations as $destination ) {
			if ( 'ENABLED' !== ( $destination['state'] ?? '' ) ) {
				continue;
			}

			$name = $destination['destination'] ?? '';

			if ( in_array( $name, self::LOCAL_PRODUCT_DESTINATIONS, true ) ) {
				$local_enabled = true;
			} elseif ( in_array( $name, self::ONLINE_CAPABLE_DESTINATIONS, true ) ) {
				$online_enabled = true;
			}
		}

		return $local_enabled && ! $online_enabled;
	}

	/**
	 * Drop the cached product data source for a (contentLanguage, feedLabel) pair, so the next
	 * ensure_data_source_for() call re-resolves it. Used to recover from a product insert
	 * rejected with "data source not found".
	 *
	 * @param string $content_language Language code.
	 * @param string $feed_label       Feed label.
	 */
	public function forget_data_source_for( string $content_language, string $feed_label ): void {
		$this->forget_data_source( self::PRODUCT_SOURCE, $content_language, $feed_label );
	}

	/**
	 * Drop the cached promotion data source for a (contentLanguage, targetCountry) pair, so the
	 * next ensure_promotion_data_source_for() call re-resolves it.
	 *
	 * @param string $content_language Language code.
	 * @param string $target_country   Target country code.
	 */
	public function forget_promotion_data_source_for( string $content_language, string $target_country ): void {
		$this->forget_data_source( self::PROMOTION_SOURCE, $content_language, $target_country );
	}

	/**
	 * Drop a cached data source from both the option cache and the verified-this-request set.
	 *
	 * @param array  $type             One of the *_SOURCE descriptors.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 */
	private function forget_data_source( array $type, string $content_language, string $match_value ): void {
		$cache_key = $type['cache_prefix'] . $content_language . '|' . $match_value;
		$cache     = (array) $this->options->get( OptionsInterface::MAPI_DATA_SOURCES, [] );

		if ( ! isset( $cache[ $cache_key ] ) ) {
			return;
		}

		$name = (string) $cache[ $cache_key ];
		unset( $cache[ $cache_key ], $this->verified_data_sources[ $name ] );
		$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );
	}

	/**
	 * Whether a failure is a "data source not found" rejection: a 404 whose message names the
	 * data source. A write cannot 404 on the item it is creating, so a 404 is the data source.
	 *
	 * @param mixed $failure
	 *
	 * @return bool
	 */
	public static function is_missing_data_source_failure( $failure ): bool {
		if ( ! $failure instanceof MerchantApiException || 404 !== $failure->get_http_status() ) {
			return false;
		}

		$message = $failure->getMessage();

		return false !== stripos( $message, 'data source' ) || false !== stripos( $message, 'datasource' );
	}

	/**
	 * Whether a failure is a channel-mismatch rejection: a 400 whose message reports that the
	 * data source's channel does not match the product's. This is the failure this whole service
	 * exists to prevent (see is_local_only_product_source()), but a source whose response omits
	 * both legacyLocal and destinations cannot be detected as local-only in advance — this is the
	 * fallback signal for that narrower case, discovered only once Google's own insert validation
	 * rejects it.
	 *
	 * @param mixed $failure
	 *
	 * @return bool
	 */
	public static function is_channel_mismatch_failure( $failure ): bool {
		if ( ! $failure instanceof MerchantApiException || 400 !== $failure->get_http_status() ) {
			return false;
		}

		return false !== stripos( $failure->getMessage(), 'does not match product channel' );
	}

	/**
	 * The displayName the plugin gives its own data sources, used when creating one and when
	 * adopting a pre-existing primary source, so a source fed by the plugin is labelled
	 * consistently in Merchant Center regardless of how it was originally created.
	 *
	 * @param string $content_language
	 * @param string $match_value
	 *
	 * @return string
	 */
	private function build_display_name( string $content_language, string $match_value ): string {
		return sprintf( '%s (%s/%s)', self::DATA_SOURCE_DISPLAY_NAME, $content_language, $match_value );
	}

	/**
	 * List existing data sources and return the one of the given type matching the
	 * (contentLanguage, match) pair, if any. A matching source that is unusable for writes (see
	 * is_unusable_data_source()) — e.g. fileInput set, or a local-only product source — is
	 * skipped rather than adopted: a new API source is created instead, and the unusable one is
	 * left in place (cleaning it up is out of scope for this resolver).
	 *
	 * @param array  $type             One of the *_SOURCE descriptors.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 *
	 * @return array|null The matched data source, or null when none matches.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	private function find_existing_data_source( array $type, string $content_language, string $match_value ): ?array {
		$page_token = '';

		do {
			$response = $this->client->get( $this->build_list_path( $page_token ) );

			foreach ( $response['dataSources'] ?? [] as $source ) {
				$descriptor = $source[ $type['source_field'] ] ?? null;
				if ( ! is_array( $descriptor ) || ! isset( $source['name'] ) ) {
					continue;
				}

				if (
					$content_language === ( $descriptor['contentLanguage'] ?? '' )
					&& $match_value === ( $descriptor[ $type['match_field'] ] ?? '' )
				) {
					if ( $this->is_unusable_data_source( $type, $source ) ) {
						continue;
					}

					return $source;
				}
			}

			$page_token = $response['nextPageToken'] ?? '';
		} while ( '' !== $page_token );

		return null;
	}

	/**
	 * Adopt a pre-existing data source: when its displayName differs (e.g. a legacy "Content API"
	 * source), rename it to the plugin's name so its products stay in place and are re-attributed
	 * to the Merchant API in Merchant Center rather than duplicated into a new source. A failed
	 * rename is logged and swallowed rather than propagated, so a transient MAPI error doesn't
	 * fail the sync for an otherwise-usable data source.
	 *
	 * @param array  $source           The matched data source.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 *
	 * @return string The data source resource name.
	 */
	private function adopt_data_source( array $source, string $content_language, string $match_value ): string {
		$display_name = $this->build_display_name( $content_language, $match_value );

		if ( $display_name !== ( $source['displayName'] ?? '' ) ) {
			try {
				$this->client->patch(
					sprintf( '%s/%s?updateMask=displayName', MapiPaths::DATASOURCES, $source['name'] ),
					[ 'displayName' => $display_name ]
				);
			} catch ( MerchantApiException $exception ) {
				do_action(
					'woocommerce_gla_error',
					sprintf(
						'Failed to rename data source %s to "%s": %s',
						$source['name'],
						$display_name,
						$exception->getMessage()
					),
					__METHOD__
				);
			}
		}

		return $source['name'];
	}

	/**
	 * Build the resource path for listing data sources.
	 *
	 * @param string $page_token
	 *
	 * @return string
	 */
	private function build_list_path( string $page_token ): string {
		$path = sprintf(
			'%s/accounts/%s/dataSources',
			MapiPaths::DATASOURCES,
			$this->options->get_merchant_id()
		);

		if ( '' !== $page_token ) {
			$path .= '?pageToken=' . rawurlencode( $page_token );
		}

		return $path;
	}

	/**
	 * Create a new data source of the given type for the (contentLanguage, match) pair.
	 *
	 * @param array  $type             One of the *_SOURCE descriptors.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 *
	 * @return string The created data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	private function create_data_source( array $type, string $content_language, string $match_value ): string {
		$descriptor = [
			'contentLanguage'    => $content_language,
			$type['match_field'] => $match_value,
		];

		if ( ! empty( $type['set_online_destinations'] ) ) {
			// Without an explicit destinations list, Google inherits the account's local-inventory
			// participation at creation time, which can produce a local-only source (see
			// is_local_only_product_source()). Requesting only the online destinations prevents that.
			$descriptor['destinations'] = array_map(
				static function ( string $destination ): array {
					return [
						'destination' => $destination,
						'state'       => 'ENABLED',
					];
				},
				self::ONLINE_PRODUCT_DESTINATIONS
			);
		}

		$response = $this->client->post(
			sprintf( '%s/accounts/%s/dataSources', MapiPaths::DATASOURCES, $this->options->get_merchant_id() ),
			[
				'displayName'         => $this->build_display_name( $content_language, $match_value ),
				$type['source_field'] => $descriptor,
			]
		);

		return $response['name'] ?? '';
	}
}
