<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidTerm;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidDomainName;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DateTimeUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\TosAccepted;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use DateTime;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Middleware
 *
 * Container used for:
 * - Ads
 * - Client
 * - DateTimeUtility
 * - GoogleHelper
 * - Merchant
 * - WP
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Middleware implements OptionsAwareInterface {

	use ExceptionTrait;
	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Middleware constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Get all Merchant Accounts associated with the connected account.
	 *
	 * @return array
	 * @throws Exception When an Exception is caught.
	 * @since 1.7.0
	 */
	public function get_merchant_accounts(): array {
		try {
			/** @var Client $client */
			$client   = $this->container->get( Client::class );
			$result   = $client->get( $this->get_manager_url( 'merchant-accounts' ) );
			$response = json_decode( $result->getBody()->getContents(), true );
			$accounts = [];

			if ( 200 === $result->getStatusCode() && is_array( $response ) ) {
				foreach ( $response as $account ) {
					$accounts[] = $account;
				}
			}

			return $accounts;
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception(
				$this->client_exception_message( $e, __( 'Error retrieving accounts', 'google-listings-and-ads' ) ),
				$e->getCode()
			);
		}
	}

	/**
	 * Create a new Merchant Center account.
	 *
	 * @return int Created merchant account ID
	 *
	 * @throws Exception When an Exception is caught or we receive an invalid response.
	 */
	public function create_merchant_account(): int {
		$user = wp_get_current_user();
		$tos  = $this->mark_tos_accepted( 'google-mc', $user->user_email );
		if ( ! $tos->accepted() ) {
			throw new Exception( __( 'Unable to log accepted TOS', 'google-listings-and-ads' ) );
		}

		$site_url = esc_url_raw( $this->get_site_url() );
		if ( ! wc_is_valid_url( $site_url ) ) {
			throw new Exception( __( 'Invalid site URL.', 'google-listings-and-ads' ) );
		}

		try {
			return $this->create_merchant_account_request(
				$this->new_account_name(),
				$site_url
			);
		} catch ( InvalidTerm $e ) {
			// Try again with a default account name.
			return $this->create_merchant_account_request(
				$this->default_account_name(),
				$site_url
			);
		}
	}

	/**
	 * Send a request to create a merchant account.
	 *
	 * @param string $name Site name
	 * @param string $site_url Website URL
	 *
	 * @return int Created merchant account ID
	 *
	 * @throws Exception   When an Exception is caught or we receive an invalid response.
	 * @throws InvalidTerm When the account name contains invalid terms.
	 * @throws InvalidDomainName When the site URL ends with an invalid top-level domain.
	 * @since 1.5.0
	 */
	protected function create_merchant_account_request( string $name, string $site_url ): int {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'create-merchant' ),
				[
					'body' => json_encode(
						[
							'name'       => $name,
							'websiteUrl' => $site_url,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['id'] ) ) {
				$id = absint( $response['id'] );
				$this->container->get( Merchant::class )->update_merchant_id( $id );
				return $id;
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when creating account', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			$message = $this->client_exception_message( $e, __( 'Error creating account', 'google-listings-and-ads' ) );

			if ( preg_match( '/terms?.* are|is not allowed/', $message ) ) {
				throw InvalidTerm::contains_invalid_terms( $name );
			}

			if ( strpos( $message, 'URL ends with an invalid top-level domain name' ) !== false ) {
				throw InvalidDomainName::create_account_failed_invalid_top_level_domain_name(
					$this->strip_url_protocol(
						esc_url_raw( $this->get_site_url() )
					)
				);
			}

			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );
			throw new Exception( $message, $e->getCode() );
		}
	}

	/**
	 * Link an existing Merchant Center account.
	 *
	 * @param int $id Existing account ID.
	 *
	 * @return int
	 */
	public function link_merchant_account( int $id ): int {
		$this->container->get( Merchant::class )->update_merchant_id( $id );

		return $id;
	}

	/**
	 * Link Merchant Center account to MCA.
	 *
	 * @return bool
	 * @throws Exception When a ClientException is caught or we receive an invalid response.
	 */
	public function link_merchant_to_mca(): bool {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'link-merchant' ),
				[
					'body' => json_encode(
						[
							'accountId' => $this->options->get_merchant_id(),
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['status'] ) && 'success' === $response['status'] ) {
				return true;
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when linking merchant to MCA', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception(
				$this->client_exception_message( $e, __( 'Error linking merchant to MCA', 'google-listings-and-ads' ) ),
				$e->getCode()
			);
		}
	}

	/**
	 * Claim the website for a MCA.
	 *
	 * @param bool $overwrite To enable claim overwriting.
	 * @return bool
	 * @throws Exception When an Exception is caught or we receive an invalid response.
	 */
	public function claim_merchant_website( bool $overwrite = false ): bool {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'claim-website' ),
				[
					'body' => json_encode(
						[
							'accountId' => $this->options->get_merchant_id(),
							'overwrite' => $overwrite,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['status'] ) && 'success' === $response['status'] ) {
				do_action( 'woocommerce_gla_site_claim_success', [ 'details' => 'google_manager' ] );
				return true;
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );
			do_action( 'woocommerce_gla_site_claim_failure', [ 'details' => 'google_manager' ] );

			$error = $response['message'] ?? __( 'Invalid response when claiming website', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );
			do_action( 'woocommerce_gla_site_claim_failure', [ 'details' => 'google_manager' ] );

			throw new Exception(
				$this->client_exception_message( $e, __( 'Error claiming website', 'google-listings-and-ads' ) ),
				$e->getCode()
			);
		}
	}

	/**
	 * Create a new Google Ads account.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught, unsupported store country, or we receive an invalid response.
	 */
	public function create_ads_account(): array {
		try {
			$country = WC()->countries->get_base_country();

			/** @var GoogleHelper $google_helper */
			$google_helper = $this->container->get( GoogleHelper::class );
			if ( ! $google_helper->is_country_supported( $country ) ) {
				throw new Exception( __( 'Store country is not supported', 'google-listings-and-ads' ) );
			}

			$user = wp_get_current_user();
			$tos  = $this->mark_tos_accepted( 'google-ads', $user->user_email );
			if ( ! $tos->accepted() ) {
				throw new Exception( __( 'Unable to log accepted TOS', 'google-listings-and-ads' ) );
			}

			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( $country . '/create-customer' ),
				[
					'body' => json_encode(
						[
							'descriptive_name' => $this->new_account_name(),
							'currency_code'    => get_woocommerce_currency(),
							'time_zone'        => $this->get_site_timezone_string(),
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );

			if ( 200 === $result->getStatusCode() && isset( $response['resourceName'] ) ) {
				/** @var Ads $ads */
				$ads = $this->container->get( Ads::class );

				$id = $ads->parse_ads_id( $response['resourceName'] );
				$ads->update_ads_id( $id );
				$ads->use_store_currency();

				$billing_url = $response['invitationLink'] ?? '';
				$ads->update_billing_url( $billing_url );

				return [
					'id'          => $id,
					'billing_url' => $billing_url,
				];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when creating account', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception(
				$this->client_exception_message( $e, __( 'Error creating account', 'google-listings-and-ads' ) ),
				$e->getCode()
			);
		}
	}

	/**
	 * Link an existing Google Ads account.
	 *
	 * @param int $id Existing account ID.
	 *
	 * @return array
	 * @throws Exception When a ClientException is caught or we receive an invalid response.
	 */
	public function link_ads_account( int $id ): array {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_manager_url( 'link-customer' ),
				[
					'body' => json_encode(
						[
							'client_customer' => $id,
						]
					),
				]
			);

			$response = json_decode( $result->getBody()->getContents(), true );
			$name     = "customers/{$id}";

			if ( 200 === $result->getStatusCode() && isset( $response['resourceName'] ) && 0 === strpos( $response['resourceName'], $name ) ) {
				/** @var Ads $ads */
				$ads = $this->container->get( Ads::class );

				$ads->update_ads_id( $id );
				$ads->request_ads_currency();

				return [ 'id' => $id ];
			}

			do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

			$error = $response['message'] ?? __( 'Invalid response when linking account', 'google-listings-and-ads' );
			throw new Exception( $error, $result->getStatusCode() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			throw new Exception(
				$this->client_exception_message( $e, __( 'Error linking account', 'google-listings-and-ads' ) ),
				$e->getCode()
			);
		}
	}

	/**
	 * Determine whether the TOS have been accepted.
	 *
	 * @param string $service Name of service.
	 *
	 * @return TosAccepted
	 */
	public function check_tos_accepted( string $service ): TosAccepted {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->get( $this->get_tos_url( $service ) );

			return new TosAccepted( 200 === $result->getStatusCode(), $result->getBody()->getContents() );
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

			return new TosAccepted( false, $e->getMessage() );
		}
	}

	/**
	 * Record TOS acceptance for a particular email address.
	 *
	 * @param string $service Name of service.
	 * @param string $email
	 *
	 * @return TosAccepted
	 */
	public function mark_tos_accepted( string $service, string $email ): TosAccepted {
		try {
			/** @var Client $client */
			$client = $this->container->get( Client::class );
			$result = $client->post(
				$this->get_tos_url( $service ),
				[
					'body' => json_encode(
						[
							'email' => $email,
						]
					),
				]
			);

			return new TosAccepted(
				200 === $result->getStatusCode(),
				$result->getBody()->getContents() ?? $result->getReasonPhrase()
			);
		} catch ( ClientExceptionInterface $e ) {
			do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );
			return new TosAccepted( false, $e->getMessage() );
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			return new TosAccepted( false, $e->getMessage() );
		}
	}

	/**
	 * Get the TOS endpoint URL
	 *
	 * @param string $service Name of service.
	 *
	 * @return string
	 */
	protected function get_tos_url( string $service ): string {
		$url = $this->container->get( 'connect_server_root' ) . 'tos';
		return $service ? trailingslashit( $url ) . $service : $url;
	}

	/**
	 * Get the manager endpoint URL
	 *
	 * @param string $name Resource name.
	 *
	 * @return string
	 */
	protected function get_manager_url( string $name = '' ): string {
		$url = $this->container->get( 'connect_server_root' ) . 'google/manager';
		return $name ? trailingslashit( $url ) . $name : $url;
	}

	/**
	 * Generate a descriptive name for a new account.
	 * Use site name if available.
	 *
	 * @return string
	 */
	protected function new_account_name(): string {
		$site_name = get_bloginfo( 'name' );
		return ! empty( $site_name ) ? $site_name : $this->default_account_name();
	}

	/**
	 * Generate a default account name based on the date.
	 *
	 * @return string
	 */
	protected function default_account_name(): string {
		return sprintf(
			/* translators: 1: current date in the format Y-m-d */
			__( 'Account %1$s', 'google-listings-and-ads' ),
			( new DateTime() )->format( 'Y-m-d' )
		);
	}

	/**
	 * Get a timezone string from WP Settings.
	 *
	 * @return string
	 * @throws Exception If the DateTime instantiation fails.
	 */
	protected function get_site_timezone_string(): string {
		/** @var WP $wp */
		$wp       = $this->container->get( WP::class );
		$timezone = $wp->wp_timezone_string();

		/** @var DateTimeUtility $datetime_util */
		$datetime_util = $this->container->get( DateTimeUtility::class );

		return $datetime_util->maybe_convert_tz_string( $timezone );
	}

	/**
	 * This function detects if the current account is a sub-account
	 * This function is cached in the MC_IS_SUBACCOUNT transient
	 *
	 * @return bool True if it's a standalone account.
	 */
	public function is_subaccount(): bool {
		/** @var TransientsInterface $transients */
		$transients    = $this->container->get( TransientsInterface::class );
		$is_subaccount = $transients->get( $transients::MC_IS_SUBACCOUNT );

		if ( is_null( $is_subaccount ) ) {
			$is_subaccount = 0;

			$merchant_id = $this->options->get_merchant_id();
			$accounts    = $this->get_merchant_accounts();

			foreach ( $accounts as $account ) {
				if ( $account['id'] === $merchant_id && $account['subaccount'] ) {
					$is_subaccount = 1;
				}
			}

			$transients->set( $transients::MC_IS_SUBACCOUNT, $is_subaccount );
		}

		// since transients don't support booleans, we save them as 0/1 and do the conversion here
		return boolval( $is_subaccount );
	}
}
