<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Connection
 *
 * ContainerAware used to access:
 * - Ads
 * - Client
 * - Merchant
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Connection implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use OptionsAwareTrait;

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * @param string $return_url The return URL.
	 * @param string $login_hint Suggested Google account to use for connection.
	 *
	 * @return string
	 * @throws Exception When a ClientException is caught or the response doesn't contain the oauthUrl.
	 */
	public function connect( string $return_url, string $login_hint = '' ): string {
		try {

			$post_body = [ 'returnUrl' => $return_url ];
			if ( ! empty( $login_hint ) ) {
				$post_body['loginHint'] = $login_hint;
			}

			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_connection_url(),
				[
					'body' => wp_json_encode( $post_body ),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			if ( 200 === $result->getStatusCode() && ! empty( $response['oauthUrl'] ) ) {
				$this->options->update( OptionsInterface::GOOGLE_CONNECTED, true );

				return $response['oauthUrl'];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			throw new Exception( __( 'Unable to connect Google account', 'google-listings-and-ads' ) );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( __( 'Unable to connect Google account', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Disconnect from the Google account.
	 *
	 * @return string
	 */
	public function disconnect(): string {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->delete( $this->get_connection_url() );

			$this->options->update( OptionsInterface::GOOGLE_CONNECTED, false );

			return $result->getBody()->getContents();
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			return $e->getMessage();
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

			return $e->getMessage();
		}
	}

	/**
	 * Get the status of the connection.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_status(): array {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_connection_url() );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				$connected = isset( $response['status'] ) && 'connected' === $response['status'];
				$this->options->update( OptionsInterface::GOOGLE_CONNECTED, $connected );

				return $response;
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$message = $response['message'] ?? __( 'Invalid response when retrieving status', 'google-listings-and-ads' );
			throw new Exception( $message, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( $this->client_exception_message( $e, __( 'Error retrieving status', 'google-listings-and-ads' ) ) );
		}
	}

	/**
	 * Get the reconnect status which checks:
	 * - The Google account is connected
	 * - We have access to the connected MC account
	 * - We have access to the connected Ads account
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_reconnect_status(): array {
		$status = $this->get_access_status();

		foreach ( [ 'merchant_access', 'ads_access' ] as $key ) {
			if ( array_key_exists( $key, $status ) ) {
				$status[ $key ] = true === $status[ $key ] ? 'yes' : 'no';
			}
		}

		return $status;
	}

	/**
	 * Get the connection status with an access check for each connected MC and Ads account.
	 *
	 * Each access value is true with access, false without it, or null when the check
	 * failed for a reason other than a missing permission.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_access_status(): array {
		$status = $this->get_status();
		$email  = $status['email'] ?? '';

		if ( ! isset( $status['status'] ) || 'connected' !== $status['status'] ) {
			return $status;
		}

		$merchant_id = $this->options->get_merchant_id();
		if ( $merchant_id ) {
			/** @var Merchant $merchant */
			$merchant = $this->container->get( Merchant::class );

			$status['merchant_account'] = $merchant_id;
			$status['merchant_access']  = $merchant->check_access( $email );

			// Confirmed access ends a sync pause started by an earlier rejection.
			if ( true === $status['merchant_access'] ) {
				$this->container->get( MerchantAccessCircuitBreaker::class )->reset();
			}
		}

		$ads_id = $this->options->get_ads_id();
		if ( $ads_id ) {
			/** @var Ads $ads */
			$ads = $this->container->get( Ads::class );

			$status['ads_account'] = $ads_id;
			$status['ads_access']  = $ads->check_access( $email );
		}

		return $status;
	}

	/**
	 * Get the Google connection URL.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/connection/google-mc";
	}
}
