<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WCS;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WCS
 */
class ConnectionService implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * Return an array of feature flags from the external API.
	 *
	 * @return array
	 *
	 * @throws Exception When an API error occurs.
	 */
	public function get_features(): array {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_connection_url() );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 !== $result->getStatusCode() || ! is_array( $response ) ) {
				throw new Exception( __( 'Unable to connect to the feature flags API', 'google-listings-and-ads' ) );
			}

			return apply_filters( 'woocommerce_gla_wcs_feature_flags', $response );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( __( 'Unable to retrieve feature flags', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Get the Features connection URL.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/plugins/google-for-woocommerce/features";
	}
}
