<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Connection
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube
 */
class Connection implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * @param string $return_url The return URL.
	 *
	 * @return string
	 * @throws Exception When a ClientException is caught or the response doesn't contain the oauthUrl.
	 */
	public function connect( string $return_url ): string {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_connection_url(),
				[
					'body' => wp_json_encode(
						[
							'returnUrl' => $return_url,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			if ( 200 === $result->getStatusCode() && ! empty( $response['oauthUrl'] ) ) {
				return $response['oauthUrl'];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			throw new Exception( __( 'Unable to connect YouTube account', 'google-listings-and-ads' ) );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( __( 'Unable to connect YouTube account', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Disconnect from the YouTube account.
	 *
	 * @return string
	 */
	public function disconnect(): string {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->delete( $this->get_connection_url() );

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
	 * Get the YouTube channel details.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_channels() {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_data_url() . '/channels?part=snippet&mine=true' );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				return $response;
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$message = $response['message'] ?? __( 'Invalid response when retrieving channels', 'google-listings-and-ads' );
			throw new Exception( $message, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( $this->client_exception_message( $e, __( 'Error retrieving channels', 'google-listings-and-ads' ) ) );
		}
	}

	/**
	 * Setup third-party linking.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function third_party_link(): array {
		try {
			$merchant_id = $this->options->get_merchant_id();

			if ( empty( $merchant_id ) ) {
				throw new Exception(
					__( 'Merchant Center account is not configured.', 'google-listings-and-ads' ),
					400
				);
			}

			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_data_url() . '/thirdPartyLinks?part=snippet',
				[
					'json' => [
						'snippet' => [
							'type'               => 'channelToStoreLink',
							'channelToStoreLink' => [
								'storeName'  => get_bloginfo( 'name' ),
								'storeUrl'   => $this->get_site_url(),
								'merchantId' => (string) $merchant_id,
							],
						],
					],
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				return $response;
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$message = $response['message'] ?? __( 'Unable to complete YouTube setup.', 'google-listings-and-ads' );
			throw new ExceptionWithResponseData( $message, $result->getStatusCode(), null, $response );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			$response = json_decode( $e->getResponse()->getBody()->getContents(), true );

			$message = $response['error']['message'] ?? $response['message'] ?? __( 'Unable to complete YouTube setup.', 'google-listings-and-ads' );

			throw new ExceptionWithResponseData( $message, $e->getCode(), $e, $response );
		}
	}

	/**
	 * Get the YouTube connection URL.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/connection/youtube";
	}

	/**
	 * Get the YouTube data proxy URL.
	 *
	 * @return string
	 */
	protected function get_data_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/youtube/v3";
	}

	/**
	 * Get the YouTube Shopping conversion report URL.
	 *
	 * @return string
	 */
	protected function get_shopping_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/youtube/shopping/report/conversion/";
	}

	/**
	 * Upload conversion report CSV files to the WCS endpoint.
	 *
	 * @param array  $file_paths Array of full paths to CSV files to upload.
	 * @param string $date       The date for the report in Y-m-d format.
	 *
	 * @return array Results array with 'success', 'uploaded', 'failed', and 'errors' keys.
	 *
	 * @throws Exception When file cannot be read or upload fails.
	 */
	public function upload_reports( array $file_paths, string $date ): array {
		$results = [
			'success'  => true,
			'uploaded' => 0,
			'failed'   => 0,
			'errors'   => [],
		];

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		foreach ( $file_paths as $file_path ) {
			try {
				// Extract part number from filename.
				// Pattern: youtube-merchant-conversion-report-2026-01-05-1.csv
				// Must match part number AFTER the date (YYYY-MM-DD), not the day from the date itself.
				$part = null;
				if ( preg_match( '/youtube-merchant-conversion-report-\d{4}-\d{2}-\d{2}-(\d+)\.csv$/', basename( $file_path ), $matches ) ) {
					$part = (int) $matches[1];
				}

				// Build endpoint URL.
				$endpoint = $this->get_shopping_url() . $date;
				if ( null !== $part ) {
					$endpoint .= '/' . $part;
				}

				// Read file contents.
				$file_contents = $wp_filesystem->get_contents( $file_path );

				if ( false === $file_contents ) {
					throw new Exception(
						sprintf(
							/* translators: %s: file path */
							__( 'Unable to read file: %s', 'google-listings-and-ads' ),
							$file_path
						)
					);
				}

				// Send PUT request.
				/** @var Client $client */
				$client = $this->container->get( Client::class );
				$result = $client->put(
					$endpoint,
					[
						'body'    => $file_contents,
						'headers' => [
							'Content-Type' => 'text/csv',
						],
					]
				);

				$status_code = $result->getStatusCode();
				if ( 200 === $status_code || 201 === $status_code ) {
					++$results['uploaded'];
				} else {
					$results['success'] = false;
					++$results['failed'];
					$results['errors'][] = sprintf(
						/* translators: 1: file path, 2: HTTP status code */
						__( 'Upload failed for %1$s: HTTP %2$s', 'google-listings-and-ads' ),
						$file_path,
						$status_code
					);
				}
			} catch ( ClientExceptionInterface $e ) {
				$results['success'] = false;
				++$results['failed'];
				$results['errors'][] = sprintf(
					/* translators: 1: file path, 2: error message */
					__( 'Error uploading %1$s: %2$s', 'google-listings-and-ads' ),
					$file_path,
					$e->getMessage()
				);
				do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );
			} catch ( Exception $e ) {
				$results['success'] = false;
				++$results['failed'];
				$results['errors'][] = sprintf(
					/* translators: 1: file path, 2: error message */
					__( 'Error uploading %1$s: %2$s', 'google-listings-and-ads' ),
					$file_path,
					$e->getMessage()
				);
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			}
		}

		return $results;
	}
}
