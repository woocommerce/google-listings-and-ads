<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiDataSourcesService
 *
 * Resolves the plugin's primary product data source. Reuses the plugin's own
 * data source when one already exists on the merchant, otherwise creates one.
 * The result is cached in options. Products in any pre-existing data source
 * are auto-moved by MAPI on the next productInputs.insert under the new source.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiDataSourcesService implements OptionsAwareInterface, InstallableInterface {

	use OptionsAwareTrait;

	/** Display name used when creating the plugin's data source. */
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
	 * Return the resource name of the plugin's primary product data source,
	 * creating or discovering one as needed and caching the result.
	 *
	 * @return string Data source resource name (accounts/{a}/dataSources/{id}).
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function ensure_primary_data_source(): string {
		$cached = (string) $this->options->get( OptionsInterface::MAPI_PRIMARY_DATA_SOURCE, '' );
		if ( '' !== $cached ) {
			return $cached;
		}

		$name = $this->find_existing_data_source() ?? $this->create_data_source();

		$this->options->update( OptionsInterface::MAPI_PRIMARY_DATA_SOURCE, $name );

		return $name;
	}

	/**
	 * Resolve the data source on plugin activation/update so it is ready before
	 * the first write request. Skipped when Merchant Center is not connected
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		if ( empty( $this->options->get_merchant_id() ) ) {
			return;
		}

		try {
			$this->ensure_primary_data_source();
		} catch ( Throwable $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}
	}

	/**
	 * List existing data sources and return the resource name of the plugin's
	 * own primary product data source if one already exists.
	 *
	 * @return string|null
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	protected function find_existing_data_source(): ?string {
		$response = $this->client->get(
			sprintf( 'datasources/v1/accounts/%s/dataSources', $this->options->get_merchant_id() )
		);

		foreach ( $response['dataSources'] ?? [] as $source ) {
			if ( ! isset( $source['primaryProductDataSource'], $source['name'] ) ) {
				continue;
			}

			if ( self::DATA_SOURCE_DISPLAY_NAME === ( $source['displayName'] ?? '' ) ) {
				return $source['name'];
			}
		}

		return null;
	}

	/**
	 * Create a new primary product data source.
	 *
	 * @return string The created data source resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	protected function create_data_source(): string {
		$base = wc_get_base_location();

		$response = $this->client->post(
			sprintf( 'datasources/v1/accounts/%s/dataSources', $this->options->get_merchant_id() ),
			[
				'displayName'              => self::DATA_SOURCE_DISPLAY_NAME,
				'primaryProductDataSource' => [
					'contentLanguage' => substr( (string) get_locale(), 0, 2 ) ?: 'en',
					'feedLabel'       => $base['country'] ?? 'US',
				],
			]
		);

		return $response['name'] ?? '';
	}
}
