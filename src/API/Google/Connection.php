<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Exception;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientExceptionInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Connection
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Connection {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Connection constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * @param string $return_url The return URL.
	 *
	 * @return string
	 * @throws Exception When we don't receive the oauthUrl value in the response.
	 */
	public function connect( string $return_url ): string {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->get(
				$this->get_connection_url(),
				[
					'body' => json_encode( [ 'returnUrl' => $return_url ] ),
				]
			);

			$body = json_decode( $result->getBody()->getContents(), true );
			if ( ! isset( $body['oauthUrl'] ) ) {
				throw new Exception( 'Did not receive oauthUrl value.' );
			}

			return $body['oauthUrl'];
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return '';
		} catch ( Exception $e ) {
			throw $e;
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

			return $result->getBody()->getContents();
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'gla_guzzle_client_exception', $e, __METHOD__ );

			return $e->getMessage();
		} catch ( Exception $e ) {
			do_action( 'gla_exception', $e, __METHOD__ );

			return $e->getMessage();
		}
	}

	/**
	 * Get the Google connection URL.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}connection/google-mc";
	}
}
