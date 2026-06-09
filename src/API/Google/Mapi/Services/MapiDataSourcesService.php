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
 * Resolves the plugin's product data source per (contentLanguage, feedLabel) pair.
 * Reuses an existing primary data source matching the pair when one is already on
 * the merchant, otherwise creates one. Resolved sources are cached in options as
 * a map keyed by "lang|feed". Resolution is lazy: the first product write into a
 * given market triggers the lookup/create.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiDataSourcesService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** Display name prefix used when creating the plugin's data sources. */
	public const DATA_SOURCE_DISPLAY_NAME = 'Google for WooCommerce';

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
	 * @param string $content_language language code
	 * @param string $feed_label       Feed label
	 *
	 * @return string Data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function ensure_data_source_for( string $content_language, string $feed_label ): string {
		$cache_key = $this->cache_key( $content_language, $feed_label );
		$cache     = (array) $this->options->get( OptionsInterface::MAPI_DATA_SOURCES, [] );

		if ( isset( $cache[ $cache_key ] ) && '' !== $cache[ $cache_key ] ) {
			return (string) $cache[ $cache_key ];
		}

		$name = $this->find_existing_data_source( $content_language, $feed_label )
			?? $this->create_data_source( $content_language, $feed_label );

		$cache[ $cache_key ] = $name;
		$this->options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );

		return $name;
	}

	/**
	 * Build the cache key for a (language, feed) pair.
	 *
	 * @param string $content_language
	 * @param string $feed_label
	 *
	 * @return string
	 */
	protected function cache_key( string $content_language, string $feed_label ): string {
		return $content_language . '|' . $feed_label;
	}

	/**
	 * List existing data sources and return the resource name of the primary
	 * product data source matching the given (language, feed) pair, if any.
	 *
	 * @param string $content_language
	 * @param string $feed_label
	 *
	 * @return string|null
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	protected function find_existing_data_source( string $content_language, string $feed_label ): ?string {
		$page_token = '';

		do {
			$response = $this->client->get( $this->build_list_path( $page_token ) );

			foreach ( $response['dataSources'] ?? [] as $source ) {
				$primary = $source['primaryProductDataSource'] ?? null;
				if ( ! is_array( $primary ) || ! isset( $source['name'] ) ) {
					continue;
				}

				if (
					$content_language === ( $primary['contentLanguage'] ?? '' )
					&& $feed_label === ( $primary['feedLabel'] ?? '' )
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
	protected function build_list_path( string $page_token ): string {
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
	 * Create a new primary product data source for the given (language, feed) pair.
	 *
	 * @param string $content_language
	 * @param string $feed_label
	 *
	 * @return string The created data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	protected function create_data_source( string $content_language, string $feed_label ): string {
		$response = $this->client->post(
			sprintf( '%s/accounts/%s/dataSources', MapiPaths::DATASOURCES, $this->options->get_merchant_id() ),
			[
				'displayName'              => sprintf( '%s (%s/%s)', self::DATA_SOURCE_DISPLAY_NAME, $content_language, $feed_label ),
				'primaryProductDataSource' => [
					'contentLanguage' => $content_language,
					'feedLabel'       => $feed_label,
				],
			]
		);

		return $response['name'] ?? '';
	}
}
