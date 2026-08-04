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
 * Class MapiAccountHomepageService
 *
 * Reads and claims the store homepage via the Merchant API accounts.homepage
 * resource, which exposes the website uri and claimed status plus the claim action.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountHomepageService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountHomepageService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Retrieve the homepage resource for a merchant account.
	 *
	 * @param int $merchant_id Optional - the account to read. Defaults to the connected account.
	 *
	 * @return array Homepage resource decoded as an array (uri, claimed).
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function get_homepage( int $merchant_id = 0 ): array {
		return $this->client->get( $this->build_path( $merchant_id ) );
	}

	/**
	 * Claim the store homepage for the connected merchant account.
	 *
	 * @param bool $overwrite Whether to remove an existing claim from another account.
	 *
	 * @return array Homepage resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function claim( bool $overwrite = false ): array {
		return $this->client->post( $this->build_path() . ':claim', [ 'overwrite' => $overwrite ] );
	}

	/**
	 * Build the homepage resource path for a merchant account.
	 *
	 * @param int $merchant_id Optional - the account to target. Defaults to the connected account.
	 *
	 * @return string
	 */
	protected function build_path( int $merchant_id = 0 ): string {
		$merchant_id = $merchant_id ?: $this->options->get_merchant_id();

		return sprintf(
			'%s/accounts/%s/homepage',
			MapiPaths::ACCOUNTS,
			$merchant_id
		);
	}
}
