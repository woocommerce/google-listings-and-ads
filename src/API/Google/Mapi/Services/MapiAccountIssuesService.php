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
 * Class MapiAccountIssuesService
 *
 * Reads account-level Merchant Center issues from the Merchant API
 * accounts.issues.list resource.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiAccountIssuesService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiAccountIssuesService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Return all account-level issues for the connected merchant account.
	 *
	 * @return array List of AccountIssue resources, each decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function get_account_issues(): array {
		$issues     = [];
		$page_token = '';

		do {
			$response = $this->client->get( $this->build_list_path( $page_token ) );

			foreach ( $response['accountIssues'] ?? [] as $issue ) {
				$issues[] = $issue;
			}

			$page_token = $response['nextPageToken'] ?? '';
		} while ( '' !== $page_token );

		return $issues;
	}

	/**
	 * Build the resource path for listing account issues.
	 *
	 * @param string $page_token
	 *
	 * @return string
	 */
	protected function build_list_path( string $page_token ): string {
		$path = sprintf(
			'%s/accounts/%s/issues',
			MapiPaths::ACCOUNTS,
			$this->options->get_merchant_id()
		);

		if ( '' !== $page_token ) {
			$path .= '?pageToken=' . rawurlencode( $page_token );
		}

		return $path;
	}
}
