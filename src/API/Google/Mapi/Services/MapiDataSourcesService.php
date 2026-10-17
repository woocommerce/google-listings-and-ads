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
		'source_field' => 'primaryProductDataSource',
		'match_field'  => 'feedLabel',
		// Namespaced (was '') to invalidate resolutions cached before the resolver began adopting
		// and renaming a pre-existing primary source (e.g. the legacy "Content API" one), so the
		// one-time rename runs on existing installs.
		'cache_prefix' => 'product|',
	];

	/** Descriptor for the promotion data source. */
	private const PROMOTION_SOURCE = [
		'source_field' => 'promotionDataSource',
		'match_field'  => 'targetCountry',
		'cache_prefix' => 'promotion|',
	];

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

			if ( isset( $this->verified_data_sources[ $name ] ) || $this->data_source_exists( $name ) ) {
				$this->verified_data_sources[ $name ] = true;
				return $name;
			}

			// The cached data source is gone: drop it and re-resolve below.
			unset( $cache[ $cache_key ] );
			$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );
		}

		$existing = $this->find_existing_data_source( $type, $content_language, $match_value );
		$name     = null !== $existing
			? $this->adopt_data_source( $existing, $content_language, $match_value )
			: $this->create_data_source( $type, $content_language, $match_value );

		$cache[ $cache_key ] = $name;
		$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );

		// Just observed on the account (discovered or created), so it exists without a further request.
		$this->verified_data_sources[ $name ] = true;

		return $name;
	}

	/**
	 * Whether a cached data source resource name still exists on the account.
	 *
	 * Only a 404 proves absence, so any other error (auth, transient 5xx) keeps the cached name:
	 * discarding it there would force a needless list-or-create, and a genuinely missing source
	 * still surfaces on the insert that follows.
	 *
	 * @param string $name Data source resource name.
	 *
	 * @return bool
	 */
	private function data_source_exists( string $name ): bool {
		try {
			$this->client->get( sprintf( '%s/%s', MapiPaths::DATASOURCES, $name ) );

			return true;
		} catch ( MerchantApiException $exception ) {
			return 404 !== $exception->get_http_status();
		}
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
	 * (contentLanguage, match) pair, if any.
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
		$response = $this->client->post(
			sprintf( '%s/accounts/%s/dataSources', MapiPaths::DATASOURCES, $this->options->get_merchant_id() ),
			[
				'displayName'         => $this->build_display_name( $content_language, $match_value ),
				$type['source_field'] => [
					'contentLanguage'    => $content_language,
					$type['match_field'] => $match_value,
				],
			]
		);

		return $response['name'] ?? '';
	}
}
