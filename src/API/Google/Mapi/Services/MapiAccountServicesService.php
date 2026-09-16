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
 * Class MapiAccountServicesService
 *
 * Reads and writes the account services for the connected account through the
 * Merchant API accounts.services resource. The Google Ads link is an account
 * service provided by providers/GOOGLE_ADS.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountServicesService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	private const GOOGLE_ADS_PROVIDER = 'providers/GOOGLE_ADS';

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountServicesService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * List the account services for the connected merchant account.
	 *
	 * @return array AccountService resources decoded as arrays (empty when none exist).
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function get_services(): array {
		return $this->client->get( $this->build_path() )['accountServices'] ?? [];
	}

	/**
	 * Find the Google Ads link service for a given Ads account id.
	 *
	 * @param int $ads_id The Google Ads account id.
	 *
	 * @return array|null The AccountService for the link, or null when there is none.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function get_google_ads_link( int $ads_id ): ?array {
		$link = null;
		foreach ( $this->get_services() as $service ) {
			if ( self::GOOGLE_ADS_PROVIDER !== ( $service['provider'] ?? '' ) || (string) $ads_id !== (string) ( $service['externalAccountId'] ?? '' ) ) {
				continue;
			}

			// A propose call can leave a rejected or pending duplicate alongside an
			// established link (the Merchant API has no way to delete a service), so prefer
			// an ESTABLISHED match and fall back to the first non-established one otherwise.
			if ( 'ESTABLISHED' === ( $service['handshake']['approvalState'] ?? '' ) ) {
				return $service;
			}

			$link = $link ?? $service;
		}

		return $link;
	}

	/**
	 * Propose a Google Ads account link (the merchant side of the handshake).
	 *
	 * @param int $ads_id The Google Ads account id to link.
	 *
	 * @return array The proposed AccountService resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function propose_google_ads_link( int $ads_id ): array {
		return $this->client->post(
			$this->build_path() . ':propose',
			[
				'provider'       => self::GOOGLE_ADS_PROVIDER,
				'accountService' => [
					'externalAccountId'   => (string) $ads_id,
					'campaignsManagement' => (object) [],
				],
			]
		);
	}

	/**
	 * Build the services resource path for the connected merchant account.
	 *
	 * @return string
	 */
	protected function build_path(): string {
		return sprintf(
			'%s/accounts/%s/services',
			MapiPaths::ACCOUNTS,
			$this->options->get_merchant_id()
		);
	}
}
