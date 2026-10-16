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
 * Class MapiAccountRegionsService
 *
 * Reads and writes the shipping regions for the connected account through the
 * Merchant API accounts.regions resource. Regions replace the Content API's
 * inline shippingSettings.postalCodeGroups and are referenced from rate-group
 * tables by their region id.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountRegionsService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountRegionsService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Create a region for the connected merchant account.
	 *
	 * @param string $region_id The region id (referenced by rate-group tables).
	 * @param array  $region    The Region resource to write.
	 *
	 * @return array The stored Region resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function insert_region( string $region_id, array $region ): array {
		return $this->client->post(
			$this->build_path() . '?regionId=' . rawurlencode( $region_id ),
			$region
		);
	}

	/**
	 * Update a region for the connected merchant account.
	 *
	 * @param string $region_id   The region id.
	 * @param array  $region      The Region fields to write.
	 * @param string $update_mask Comma-separated list of fields to update.
	 *
	 * @return array The stored Region resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function update_region( string $region_id, array $region, string $update_mask ): array {
		return $this->client->patch(
			$this->build_region_path( $region_id ) . '?updateMask=' . rawurlencode( $update_mask ),
			$region
		);
	}

	/**
	 * Build the regions collection path for the connected merchant account.
	 *
	 * @return string
	 */
	protected function build_path(): string {
		return sprintf(
			'%s/accounts/%s/regions',
			MapiPaths::ACCOUNTS,
			$this->options->get_merchant_id()
		);
	}

	/**
	 * Build the path for a single region.
	 *
	 * @param string $region_id
	 *
	 * @return string
	 */
	protected function build_region_path( string $region_id ): string {
		return $this->build_path() . '/' . rawurlencode( $region_id );
	}
}
