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
 * Class MapiAccountUsersService
 *
 * Reads the authenticated user's access to the connected account from the
 * Merchant API accounts.users resource (users/me).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountUsersService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountUsersService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Retrieve the authenticated user's resource for the connected merchant account.
	 *
	 * @return array User resource decoded as an array (name, state, accessRights).
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function get_current_user(): array {
		return $this->client->get( $this->build_path() );
	}

	/**
	 * Build the users/me resource path for the connected merchant account.
	 *
	 * @return string
	 */
	protected function build_path(): string {
		return sprintf(
			'%s/accounts/%s/users/me',
			MapiPaths::ACCOUNTS,
			$this->options->get_merchant_id()
		);
	}
}
