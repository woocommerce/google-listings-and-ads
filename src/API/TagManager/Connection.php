<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshTagManagerAdsConversionConflict;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;
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

	/** @var string An account has been selected, but its container hasn't been chosen yet. */
	public const STATUS_INCOMPLETE = 'incomplete';

	/** @var string An account and container are both selected. */
	public const STATUS_CONNECTED = 'connected';

	/** @var string No account has been selected yet, or the connection was explicitly disconnected. */
	public const STATUS_DISCONNECTED = 'disconnected';

	/**
	 * The OAuth scope that grants read access to Tag Manager accounts and containers,
	 * requested as an additional scope on the shared Google connection.
	 *
	 * @var string
	 */
	public const SCOPE_TAG_MANAGER = 'https://www.googleapis.com/auth/tagmanager.readonly';

	/**
	 * The OAuth scope Google requires to create a container, requested alongside the read scope.
	 * Whether it has been granted is what tells the plugin if creating a container can work at all.
	 *
	 * @var string
	 */
	public const SCOPE_TAG_MANAGER_EDIT = 'https://www.googleapis.com/auth/tagmanager.edit.containers';

	/**
	 * Default shape of the `tag_manager` option.
	 *
	 * - `account_id`, `account_name`: the selected Tag Manager account.
	 * - `container_id`, `container_name`, `container_public_id`: the connected container.
	 * - `ads_conversion_conflict`: whether the connected container holds a Google Ads conversion tag.
	 * - `pending_container_id`: a container that was created but couldn't be connected yet.
	 * - `create_denied_account_id`: the account in which Tag Manager refused to create a container for
	 *   the connected Google user, remembered so the card can explain it instead of offering to create
	 *   again. Set when a create is refused. Cleared when an account is selected, and removed with the
	 *   option on disconnect.
	 *
	 * @var array
	 */
	protected const DEFAULT_CONNECTION_DATA = [
		'account_id'               => null,
		'account_name'             => null,
		'container_id'             => null,
		'container_name'           => null,
		'container_public_id'      => null,
		'ads_conversion_conflict'  => null,
		'pending_container_id'     => null,
		'create_denied_account_id' => null,
	];

	/**
	 * GTM tag type of a Google Ads Conversion Tracking tag.
	 *
	 * @var string
	 */
	protected const ADS_CONVERSION_TAG_TYPE = 'awct';

	/**
	 * Usage context of the containers created from the plugin, which are always Web.
	 *
	 * @var string
	 */
	protected const CONTAINER_USAGE_CONTEXT = 'web';

	/**
	 * The shape of {@see self::get_status()}, with `null` for what doesn't apply to the current state.
	 *
	 * @var array
	 */
	protected const EMPTY_STATUS = [
		'status'                => null,
		'canCreateContainer'    => null,
		'pendingContainerId'    => null,
		'id'                    => null,
		'name'                  => null,
		'containerId'           => null,
		'containerName'         => null,
		'containerPublicId'     => null,
		'adsConversionConflict' => null,
	];

	/**
	 * The shape of a container's public ID, e.g. `GTM-ABCDEFG`.
	 *
	 * @var string
	 */
	protected const CONTAINER_PUBLIC_ID_PATTERN = '/^GTM-[A-Z0-9]+$/D';

	/**
	 * What the Connect Server's error message contains when it doesn't support a requested additional scope.
	 * The fallback in {@see self::connect()} depends on this wording.
	 *
	 * @var string
	 */
	protected const UNSUPPORTED_SCOPES_MESSAGE = 'unsupported additional scopes';

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
	 * Whether the last completed check found a Google Ads conversion tag in the connected container.
	 *
	 * @return bool
	 */
	public function has_ads_conversion_conflict(): bool {
		return true === ( $this->get_connection_data()['ads_conversion_conflict'] ?? null );
	}

	/**
	 * Re-check the connected container's published version for an active Google Ads conversion
	 * tag, and store the result.
	 *
	 * @return bool Whether a conflict was found.
	 * @throws Exception When no container is selected, or the check can't complete.
	 */
	public function refresh_ads_conversion_conflict(): bool {
		$data = $this->get_connection_data();

		if ( empty( $data['account_id'] ) || empty( $data['container_id'] ) ) {
			throw new Exception( __( 'No Tag Manager container has been selected yet.', 'google-listings-and-ads' ) );
		}

		try {
			$live_version = $this->client->get( "accounts/{$data['account_id']}/containers/{$data['container_id']}/versions:live" );
		} catch ( TagManagerApiException $e ) {
			// A container that has never been published has no live version, so nothing can conflict.
			if ( 404 !== $e->get_http_status() ) {
				throw $e;
			}

			$live_version = [];
		}

		$conflict = self::has_active_ads_conversion_tag( $live_version['tag'] ?? [] );

		$this->update_connection_data( [ 'ads_conversion_conflict' => $conflict ] );

		return $conflict;
	}

	/**
	 * Get the connection URL for performing a connection redirect.
	 *
	 * Tag Manager has no dedicated OAuth connection of its own — it requests the
	 * `tagmanager.readonly` scope, and the edit scope needed to create containers, as
	 * additional scopes on the shared Google connection, the same connection Merchant
	 * Center/Ads already establish. If the Connect Server doesn't support the edit scope,
	 * the request is repeated with the read scope alone, so connecting keeps working.
	 *
	 * Accepts a `loginHint`, sourced from the client's own already-known connected
	 * account, so Google pre-selects it on the consent screen. This only narrows,
	 * never eliminates, the risk of a merchant granting this scope under a different
	 * account than Merchant Center/Ads — Google still allows switching accounts on
	 * that screen. Mirrors the general Google connection's own `connect()` signature
	 * exactly — see {@see \Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection::connect()}.
	 *
	 * @param string $return_url The return URL.
	 * @param string $login_hint Suggested Google account to use for the scope grant.
	 *
	 * @return string
	 * @throws Exception When a ClientException is caught or the response doesn't contain the oauthUrl.
	 */
	public function connect( string $return_url, string $login_hint = '' ): string {
		try {
			try {
				$result = $this->request_connection_url( $return_url, $login_hint, [ self::SCOPE_TAG_MANAGER, self::SCOPE_TAG_MANAGER_EDIT ] );
			} catch ( RequestException $e ) {
				if ( ! self::is_unsupported_scope_error( $e ) ) {
					throw $e;
				}

				// The Connect Server rejects the whole request when it doesn't support the edit scope.
				// Connecting must still work with the read scope alone, so repeat without it.
				do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

				$result = $this->request_connection_url( $return_url, $login_hint, [ self::SCOPE_TAG_MANAGER ] );
			}

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
	 * Ask the Connect Server for the OAuth URL that grants the given additional scopes.
	 *
	 * @param string   $return_url The return URL.
	 * @param string   $login_hint Suggested Google account to use for the scope grant.
	 * @param string[] $scopes     The additional scopes to request.
	 *
	 * @return ResponseInterface
	 * @throws ClientExceptionInterface When the request itself fails, or the Connect Server answers with an error status.
	 */
	private function request_connection_url( string $return_url, string $login_hint, array $scopes ): ResponseInterface {
		$body = [
			'returnUrl'        => $return_url,
			'additionalScopes' => $scopes,
		];

		if ( ! empty( $login_hint ) ) {
			$body['loginHint'] = $login_hint;
		}

		/** @var Client $client */
		$client = $this->container->get( Client::class );

		return $client->post(
			$this->get_connection_url(),
			[
				'body' => wp_json_encode( $body ),
			]
		);
	}

	/**
	 * Whether a failed Connect Server request was rejected because an additional scope isn't supported.
	 *
	 * @param RequestException $e
	 *
	 * @return bool
	 */
	private static function is_unsupported_scope_error( RequestException $e ): bool {
		$response = $e->getResponse();

		if ( null === $response || 400 !== $response->getStatusCode() ) {
			return false;
		}

		$body = json_decode( (string) $response->getBody(), true );

		return is_array( $body ) && false !== stripos( (string) ( $body['message'] ?? '' ), self::UNSUPPORTED_SCOPES_MESSAGE );
	}

	/**
	 * Disconnect from the Tag Manager account.
	 *
	 * Purely local. The connection URL is shared with Merchant Center/Ads (see
	 * {@see self::get_connection_url()}), so a remote DELETE here would tear
	 * down that shared connection instead of just Tag Manager's own state —
	 * only what is stored locally is cleared: the account/container selection,
	 * the settings that belong to that connection, and the recurring conflict check.
	 *
	 * @return string
	 */
	public function disconnect(): string {
		$this->options->delete( OptionsInterface::TAG_MANAGER );

		// The settings belong to this connection, so the next one starts from the defaults.
		$this->options->delete( OptionsInterface::TAG_MANAGER_SETTINGS );

		// Last, so that failing to unschedule can't leave the stored connection behind. A check left
		// scheduled is harmless: it can only be scheduled while a container is connected.
		try {
			if ( $this->container->has( JobRepository::class ) ) {
				$this->container->get( JobRepository::class )->get( RefreshTagManagerAdsConversionConflict::class )->unschedule();
			}
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}

		return __( 'Successfully disconnected.', 'google-listings-and-ads' );
	}

	/**
	 * Get the status of the connection.
	 *
	 * No account selected yet is reported the same as no connection at all
	 * (`STATUS_DISCONNECTED`), never `STATUS_INCOMPLETE` — the account-card UI
	 * that renders on `STATUS_INCOMPLETE` assumes an account is already chosen
	 * and only prompts for a container; routing "nothing chosen yet" there
	 * would drop the merchant straight into container selection with no
	 * account behind it.
	 *
	 * @return array {
	 *     Every key is always present, `null` when it doesn't apply to the current state.
	 *
	 *     @type string      $status                One of the self::STATUS_* constants.
	 *     @type bool|null   $canCreateContainer    Whether a container can be created: the granted scopes allow it, and
	 *                                              Tag Manager hasn't refused it for the selected account. Only set while
	 *                                              the status is incomplete, the one state a container can be created from.
	 *     @type string|null $pendingContainerId    The ID of a container that was created but not connected yet. Only set
	 *                                              while the status is incomplete and such a container exists.
	 *     @type string|null $id                    The selected account's ID, once one has been chosen.
	 *     @type string|null $name                  The selected account's name, once one has been chosen.
	 *     @type string|null $containerId           The selected container's ID, once one has been chosen.
	 *     @type string|null $containerName         The selected container's name, once one has been chosen.
	 *     @type string|null $containerPublicId     The selected container's merchant-facing ID, once one has been chosen.
	 *     @type bool|null   $adsConversionConflict Whether the container's published version contains an active Google Ads
	 *                                              conversion tag, as of the last completed check.
	 * }
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function get_status(): array {
		$scopes = $this->get_granted_scopes();

		if ( ! in_array( self::SCOPE_TAG_MANAGER, $scopes, true ) ) {
			return array_merge( self::EMPTY_STATUS, [ 'status' => self::STATUS_DISCONNECTED ] );
		}

		$data = $this->get_connection_data();

		if ( empty( $data['account_id'] ) ) {
			return array_merge( self::EMPTY_STATUS, [ 'status' => self::STATUS_DISCONNECTED ] );
		}

		if ( empty( $data['container_id'] ) ) {
			$status = [
				'status'             => self::STATUS_INCOMPLETE,
				'canCreateContainer' => in_array( self::SCOPE_TAG_MANAGER_EDIT, $scopes, true )
					&& ( $data['create_denied_account_id'] ?? null ) !== $data['account_id'],
				// A container was created but couldn't be connected; creating again connects it instead of making another.
				'pendingContainerId' => ! empty( $data['pending_container_id'] ) ? $data['pending_container_id'] : null,
			];

			return array_merge( self::EMPTY_STATUS, $status, $this->format_connection_data( $data ) );
		}

		return array_merge( self::EMPTY_STATUS, [ 'status' => self::STATUS_CONNECTED ], $this->format_connection_data( $data ) );
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
				'account_id'               => $account['id'],
				'account_name'             => $account['name'],
				'container_id'             => null,
				'container_name'           => null,
				'container_public_id'      => null,
				'ads_conversion_conflict'  => null,
				'pending_container_id'     => null,
				'create_denied_account_id' => null,
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
		$account_id = $this->get_selected_account_id_or_throw();

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
		$account_id = $this->get_selected_account_id_or_throw();

		$container = $this->format_container( $this->client->get( "accounts/{$account_id}/containers/{$container_id}" ) );

		$updated = $this->update_connection_data(
			[
				'container_id'         => $container['id'],
				'container_name'       => $container['name'],
				'container_public_id'  => $container['publicId'],
				'pending_container_id' => null,
			]
		);

		try {
			$this->refresh_ads_conversion_conflict();
		} catch ( Exception $e ) {
			// Fail open: with no known conflict, snippet injection stays enabled.
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

			$this->update_connection_data( [ 'ads_conversion_conflict' => null ] );
		}

		return $updated;
	}

	/**
	 * Whether the shared Google connection carries the scope needed to create a container.
	 *
	 * @return bool
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	public function can_create_containers(): bool {
		return in_array( self::SCOPE_TAG_MANAGER_EDIT, $this->get_granted_scopes(), true );
	}

	/**
	 * Remember that Tag Manager refused to create a container in the selected account.
	 *
	 * @return bool Whether it was stored. False when no account is selected.
	 */
	public function remember_create_denied(): bool {
		$account_id = $this->get_connection_data()['account_id'] ?? null;

		if ( empty( $account_id ) ) {
			return false;
		}

		return $this->update_connection_data( [ 'create_denied_account_id' => $account_id ] );
	}

	/**
	 * Create a Web container in the selected account and connect it.
	 *
	 * The created container's ID is stored before connecting, so a retry after a failed
	 * connect connects that container rather than creating a second one.
	 *
	 * @param string $name The new container's name.
	 *
	 * @return bool
	 * @throws Exception When no account has been selected yet, a container is already connected,
	 *                   or the created container's ID isn't returned.
	 * @throws TagManagerApiException On a non-2xx Tag Manager API response.
	 */
	public function create_container( string $name ): bool {
		$account_id = $this->get_selected_account_id_or_throw();
		$data       = $this->get_connection_data();

		if ( ! empty( $data['container_id'] ) ) {
			throw new Exception( __( 'A Tag Manager container is already connected.', 'google-listings-and-ads' ), 409 );
		}

		$container_id = $data['pending_container_id'] ?? null;

		if ( empty( $container_id ) ) {
			$created = $this->format_container(
				$this->client->post(
					"accounts/{$account_id}/containers",
					[
						'name'         => $name,
						'usageContext' => [ self::CONTAINER_USAGE_CONTEXT ],
					]
				)
			);

			if ( '' === $created['id'] ) {
				throw new Exception( __( 'Tag Manager did not return the ID of the new container.', 'google-listings-and-ads' ) );
			}

			$container_id = $created['id'];

			$this->update_connection_data( [ 'pending_container_id' => $container_id ] );
		}

		return $this->select_container( $container_id );
	}

	/**
	 * Whether a container version's tags include an unpaused Google Ads Conversion Tracking tag.
	 *
	 * @param array $tags Tag resources from a container version.
	 *
	 * @return bool
	 */
	private static function has_active_ads_conversion_tag( array $tags ): bool {
		foreach ( $tags as $tag ) {
			if ( self::ADS_CONVERSION_TAG_TYPE === ( $tag['type'] ?? '' ) && empty( $tag['paused'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the currently selected account ID.
	 *
	 * @return string
	 * @throws Exception When no account has been selected yet.
	 */
	private function get_selected_account_id_or_throw(): string {
		$account_id = $this->get_connection_data()['account_id'];

		if ( empty( $account_id ) ) {
			throw new Exception( __( 'No Tag Manager account has been selected yet.', 'google-listings-and-ads' ) );
		}

		return $account_id;
	}

	/**
	 * Get the scopes the shared Google connection currently carries.
	 *
	 * @return string[]
	 * @throws Exception When a ClientException is caught or the response contains an error.
	 */
	protected function get_granted_scopes(): array {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_connection_url() );
			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() ) {
				$scopes = $response['scope'] ?? [];

				return is_array( $scopes ) ? $scopes : [];
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

			$formatted['adsConversionConflict'] = true === ( $data['ads_conversion_conflict'] ?? null );
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
		$public_id = $container['publicId'] ?? '';

		return [
			'id'       => $container['containerId'] ?? '',
			// The public ID ends up in the storefront snippet, so one that isn't shaped like `GTM-XXXXXXX` is dropped.
			'publicId' => is_string( $public_id ) && 1 === preg_match( self::CONTAINER_PUBLIC_ID_PATTERN, $public_id ) ? $public_id : '',
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
