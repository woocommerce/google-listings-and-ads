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
	 * Insert (create or replace) the shipping settings for the connected merchant account.
	 *
	 * The shippingSettings:insert custom method is an unconditional full replacement,
	 * so it takes no etag / If-Match precondition (the resource's etag only guards a
	 * read-modify-write, which this write path does not perform).
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
