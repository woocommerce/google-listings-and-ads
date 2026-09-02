<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
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
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager
 */
class Connection implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use OptionsAwareTrait;

	/** @var string The connection is active, but no account/container has been selected yet. */
	public const STATUS_INCOMPLETE = 'incomplete';

	/** @var string An account and container are both selected. */
	public const STATUS_CONNECTED = 'connected';

	/** @var string No connection has been established, or it was explicitly disconnected. */
	public const STATUS_DISCONNECTED = 'disconnected';

	/**
	 * The OAuth scope Woo Connect Server grants for Tag Manager API access,
	 * requested as an additional scope on the shared Google connection.
	 *
	 * Confirmed via a live request against Woo's actual Connect Server:
	 * `additionalScopes` accepts this scope and rejects
	 * `tagmanager.edit.containers` outright ("Unsupported additional scopes") —
	 * not needed now that in-plugin container creation is off-site only.
	 *
	 * @var string
	 */
	public const SCOPE_TAG_MANAGER = 'https://www.googleapis.com/auth/tagmanager.readonly';

	/**
	 * Default shape of the `tag_manager` option.
	 *
	 * @var array
	 */
	protected const DEFAULT_CONNECTION_DATA = [
		'account_id'          => null,
		'account_name'        => null,
		'container_id'        => null,
		'container_name'      => null,
		'container_public_id' => null,
	];

	/** @var TagManagerApiClient */
	protected $client;

	/**
	 * Connection constructor.
	 *
	 * @param TagManagerApiClient $client
	 */
	public function __construct( TagManagerApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get the stored Tag Manager connection data.
	 *
	 * @return array
	 */
	public function get_connection_data(): array {
		return $this->options->get( OptionsInterface::TAG_MANAGER, self::DEFAULT_CONNECTION_DATA );
	}

	/**
	 * Update the stored Tag Manager connection data.
	 *
	 * Merges the given fields onto the existing stored data (or the defaults, if
	 * nothing has been stored yet) so callers can update a subset of fields.
	 *
	 * @param array $data The connection data fields to update.
	 *
	 * @return bool
	 */
	public function update_connection_data( array $data ): bool {
		return $this->options->update(
			OptionsInterface::TAG_MANAGER,
			array_merge( $this->get_connection_data(), $data )
		);
	}

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * Tag Manager has no dedicated OAuth connection of its own — it requests the
	 * `tagmanager.readonly` scope as an additional scope on the shared Google
	 * connection, the same connection Merchant Center/Ads already establish.
	 * Confirmed directly against Woo's live Connect Server, not assumed from
	 * Search Console's equivalent mechanism.
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
							'returnUrl'        => $return_url,
							'additionalScopes' => [ self::SCOPE_TAG_MANAGER ],
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			if ( 200 === $result->getStatusCode() && ! empty( $response['oauthUrl'] ) ) {
				return $response['oauthUrl'];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			throw new Exception( __( 'Unable to connect Tag Manager account', 'google-listings-and-ads' ) );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception( __( 'Unable to connect Tag Manager account', 'google-listings-and-ads' ) );
		}
	}

	/**
	 * Disconnect from the Tag Manager account.
	 *
	 * Purely local. The connection URL is shared with Merchant Center/Ads (see
	 * {@see self::get_connection_url()}), so a remote DELETE here would tear
	 * down that shared connection instead of just Tag Manager's own state —
	 * only the locally stored account/container selection is cleared.
	 *
	 * @return string
	 */
	public function disconnect(): string {
		$this->options->delete( OptionsInterface::TAG_MANAGER );

		return __( 'Successfully disconnected.', 'google-listings-and-ads' );
	}

	/**
	 * Get the status of the connection.
	 *
	 * @return array {
	 *     @type string $status            One of the self::STATUS_* constants.
	 *     @type string $id                The selected account's ID, once one has been chosen.
	 *     @type string $name              The selected account's name, once one has been chosen.
	 *     @type string $containerId       The selected container's ID, once one has been chosen.
	 *     @type string $containerName     The selected container's name, once one has been chosen.
	 *     @type string $containerPublicId The selected container's merchant-facing ID, once one has been chosen.
	 * }
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_status(): array {
		if ( ! $this->is_scope_granted() ) {
			return [ 'status' => self::STATUS_DISCONNECTED ];
		}

		$data = $this->get_connection_data();

		if ( empty( $data['account_id'] ) || empty( $data['container_id'] ) ) {
			return array_merge( [ 'status' => self::STATUS_INCOMPLETE ], $this->format_connection_data( $data ) );
		}

		return array_merge( [ 'status' => self::STATUS_CONNECTED ], $this->format_connection_data( $data ) );
	}

	/**
	 * List the connected Google user's Tag Manager accounts.
	 *
	 * @return array Each entry shaped `{ id, name }`.
	 * @throws TagManagerApiException On a non-2xx Tag Manager API response.
	 */
	public function list_accounts(): array {
		$response = $this->client->get( 'accounts' );

		return array_map( [ $this, 'format_account' ], $response['account'] ?? [] );
	}

	/**
	 * Select an account, storing it as this connection's account_id.
	 *
	 * Clears any previously selected container — it belonged to a different
	 * account and is no longer valid once the account selection changes.
	 *
	 * @param string $account_id The Tag Manager account ID.
	 *
	 * @return bool
	 * @throws TagManagerApiException On a non-2xx Tag Manager API response.
	 */
	public function select_account( string $account_id ): bool {
		$account = $this->format_account( $this->client->get( "accounts/{$account_id}" ) );

		return $this->update_connection_data(
			[
				'account_id'          => $account['id'],
				'account_name'        => $account['name'],
				'container_id'        => null,
				'container_name'      => null,
				'container_public_id' => null,
			]
		);
	}

	/**
	 * List the selected account's existing containers.
	 *
	 * @return array Each entry shaped `{ id, publicId, name }`.
	 * @throws Exception When no account has been selected yet.
	 * @throws TagManagerApiException On a non-2xx Tag Manager API response.
	 */
	public function list_containers(): array {
		$account_id = $this->get_connection_data()['account_id'];

		if ( empty( $account_id ) ) {
			throw new Exception( __( 'No Tag Manager account has been selected yet.', 'google-listings-and-ads' ) );
		}

		$response = $this->client->get( "accounts/{$account_id}/containers" );

		return array_map( [ $this, 'format_container' ], $response['container'] ?? [] );
	}

	/**
	 * Select a container, completing the connection.
	 *
	 * @param string $container_id The Tag Manager container ID.
	 *
	 * @return bool
	 * @throws Exception When no account has been selected yet.
	 * @throws TagManagerApiException On a non-2xx Tag Manager API response.
	 */
	public function select_container( string $container_id ): bool {
		$account_id = $this->get_connection_data()['account_id'];

		if ( empty( $account_id ) ) {
			throw new Exception( __( 'No Tag Manager account has been selected yet.', 'google-listings-and-ads' ) );
		}

		$container = $this->format_container( $this->client->get( "accounts/{$account_id}/containers/{$container_id}" ) );

		return $this->update_connection_data(
			[
				'container_id'        => $container['id'],
				'container_name'      => $container['name'],
				'container_public_id' => $container['publicId'],
			]
		);
	}

	/**
	 * Whether the shared Google connection currently carries the Tag Manager scope.
	 *
	 * @return bool
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	protected function is_scope_granted(): bool {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_connection_url() );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				return in_array( self::SCOPE_TAG_MANAGER, $response['scope'] ?? [], true );
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
	 * Map the stored connection data onto the response shape the account card expects.
	 *
	 * @param array $data Stored connection data (`self::DEFAULT_CONNECTION_DATA` shape).
	 *
	 * @return array
	 */
	protected function format_connection_data( array $data ): array {
		$formatted = [];

		if ( ! empty( $data['account_id'] ) ) {
			$formatted['id']   = $data['account_id'];
			$formatted['name'] = $data['account_name'];
		}

		if ( ! empty( $data['container_id'] ) ) {
			$formatted['containerId']       = $data['container_id'];
			$formatted['containerName']     = $data['container_name'];
			$formatted['containerPublicId'] = $data['container_public_id'];
		}

		return $formatted;
	}

	/**
	 * Map a Tag Manager API account resource onto the shape the account card expects.
	 *
	 * @param array $account Raw `account` resource (`accountId`, `name`, ...).
	 *
	 * @return array Shaped `{ id, name }`.
	 */
	protected function format_account( array $account ): array {
		return [
			'id'   => $account['accountId'] ?? '',
			'name' => $account['name'] ?? '',
		];
	}

	/**
	 * Map a Tag Manager API container resource onto the shape the account card expects.
	 *
	 * @param array $container Raw `container` resource (`containerId`, `publicId`, `name`, ...).
	 *
	 * @return array Shaped `{ id, publicId, name }`.
	 */
	protected function format_container( array $container ): array {
		return [
			'id'       => $container['containerId'] ?? '',
			'publicId' => $container['publicId'] ?? '',
			'name'     => $container['name'] ?? '',
		];
	}

	/**
	 * Get the shared Google connection URL.
	 *
	 * @return string
	 */
	protected function get_connection_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/connection/google-mc";
	}
}
