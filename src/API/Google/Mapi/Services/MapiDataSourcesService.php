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
		'cache_prefix' => '',
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
			return (string) $cache[ $cache_key ];
		}

		$name = $this->find_existing_data_source( $type, $content_language, $match_value )
			?? $this->create_data_source( $type, $content_language, $match_value );

		$cache[ $cache_key ] = $name;
		$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );

		return $name;
	}

	/**
	 * List existing data sources and return the resource name of the one of the given
	 * type matching the (contentLanguage, match) pair, if any.
	 *
	 * @param array  $type             One of the *_SOURCE descriptors.
	 * @param string $content_language Language code.
	 * @param string $match_value      Secondary identity value (feed label or target country).
	 *
	 * @return string|null
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	private function find_existing_data_source( array $type, string $content_language, string $match_value ): ?string {
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
					return $source['name'];
				}
			}

			$page_token = $response['nextPageToken'] ?? '';
		} while ( '' !== $page_token );

		return null;
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
				'displayName'         => sprintf( '%s (%s/%s)', self::DATA_SOURCE_DISPLAY_NAME, $content_language, $match_value ),
				$type['source_field'] => [
					'contentLanguage'    => $content_language,
					$type['match_field'] => $match_value,
				],
			]
		);

		return $response['name'] ?? '';
	}
}
