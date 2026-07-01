<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountShippingSettingsService
 *
 * Reads and writes the shipping settings for the connected account through the
 * Merchant API accounts.shippingSettings resource.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountShippingSettingsService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountShippingSettingsService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Retrieve the shipping settings for the connected merchant account.
	 *
	 * The Merchant API returns a 404 when the account has no shipping settings
	 * yet; that is mapped to an empty array so callers can treat it as "none".
	 *
	 * @return array ShippingSettings resource decoded as an array, empty when none exist.
	 * @throws MerchantApiException On a non-2xx MAPI response other than 404.
	 */
	public function get_shipping_settings(): array {
		try {
			return $this->client->get( $this->build_path() );
		} catch ( MerchantApiException $e ) {
			if ( 404 === $e->get_http_status() ) {
				return [];
			}

			throw $e;
		}
	}

	/**
	 * Insert (create or replace) the shipping settings for the connected merchant account.
	 *
	 * @param array $shipping_settings ShippingSettings resource to write.
	 *
	 * @return array The stored ShippingSettings resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function insert_shipping_settings( array $shipping_settings ): array {
		return $this->client->post( $this->build_path() . ':insert', $shipping_settings );
	}

	/**
	 * Build the shippingSettings resource path for the connected merchant account.
	 *
	 * @return string
	 */
	protected function build_path(): string {
		return sprintf(
			'%s/accounts/%s/shippingSettings',
			MapiPaths::ACCOUNTS,
			$this->options->get_merchant_id()
		);
	}
}
