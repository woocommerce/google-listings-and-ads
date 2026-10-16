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
 * Class MapiAccountBusinessInfoService
 *
 * Reads the business information (address, phone, customer service) for the
 * connected account from the Merchant API accounts.businessInfo resource.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountBusinessInfoService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountBusinessInfoService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Retrieve the business information for the connected merchant account.
	 *
	 * @return array BusinessInfo resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function get_business_info(): array {
		return $this->client->get( $this->build_path() );
	}

	/**
	 * Update the business information for the connected merchant account.
	 *
	 * @param array  $business_info BusinessInfo fields to write.
	 * @param string $update_mask   Comma-separated list of fields to update.
	 *
	 * @return array The updated BusinessInfo.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function update_business_info( array $business_info, string $update_mask ): array {
		// Encode each field name individually so the commas separating a
		// multi-field mask are preserved rather than percent-encoded.
		$encoded_mask = implode( ',', array_map( 'rawurlencode', explode( ',', $update_mask ) ) );

		return $this->client->patch(
			$this->build_path() . '?updateMask=' . $encoded_mask,
			$business_info
		);
	}

	/**
	 * Build the businessInfo resource path for the connected merchant account.
	 *
	 * @return string
	 */
	protected function build_path(): string {
		return sprintf(
			'%s/accounts/%s/businessInfo',
			MapiPaths::ACCOUNTS,
			$this->options->get_merchant_id()
		);
	}
}
