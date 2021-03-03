<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPError;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Argument\RawArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\Definition;
use Exception;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Client;
use Google_Service_ShoppingContent;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Jetpack_Options;
use Psr\Http\Message\RequestInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class GoogleServiceProvider extends AbstractServiceProvider {

	use WPErrorTrait;

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		Client::class                         => true,
		Google_Service_ShoppingContent::class => true,
		GoogleAdsClient::class                => true,
		GuzzleClient::class                   => true,
		Proxy::class                          => true,
		Merchant::class                       => true,
		Ads::class                            => true,
		'connect_server_root'                 => true,
		'connect_server_auth_header'          => true,
		Connection::class                     => true,
		GoogleProductService::class           => true,
		SiteVerification::class               => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_guzzle();
		$this->register_ads_client();
		$this->register_google_classes();
		$this->add( Proxy::class, $this->getLeagueContainer() );
		$this->add( Connection::class, $this->getLeagueContainer() );

		$this->add(
			Ads::class,
			$this->getLeagueContainer(),
			$this->get_ads_id()
		);

		$this->add(
			Merchant::class,
			$this->getLeagueContainer(),
			$this->get_merchant_id()
		);

		$this->add(
			GoogleProductService::class,
			Google_Service_ShoppingContent::class,
			Merchant::class
		);

		$this->add(
			SiteVerification::class,
			$this->getLeagueContainer()
		);

		try {
			$auth_header = [ 'Authorization' => $this->generate_auth_header() ];
		} catch ( WPError $error ) {
			$auth_header = [];
		}
		$this->getLeagueContainer()->add( 'connect_server_auth_header', $auth_header );
		$this->getLeagueContainer()->add( 'connect_server_root', $this->get_connect_server_url_root() );
	}

	/**
	 * Register guzzle with authorization middleware added.
	 */
	protected function register_guzzle() {
		$callback = function() {
			$handler_stack = HandlerStack::create();
			$handler_stack->remove( 'http_errors' );
			$handler_stack->push( $this->add_auth_header() );

			return new GuzzleClient( [ 'handler' => $handler_stack ] );
		};

		$this->share_concrete( GuzzleClient::class, new Definition( GuzzleClient::class, $callback ) );
		$this->share_concrete( ClientInterface::class, new Definition( GuzzleClient::class, $callback ) );
	}

	/**
	 * Register ads client.
	 */
	protected function register_ads_client() {
		$callback = function() {
			// Using placeholder values, as the middleware server handles the authentication tokens.
			$oauth = ( new OAuth2TokenBuilder() )
				->withClientId( 'clientid' )
				->withClientSecret( 'clientsecret' )
				->withRefreshToken( 'refreshtoken' )
				->build();

			// The developer token will be handled by the middleware server.
			return ( new GoogleAdsClientBuilder() )
				->withDeveloperToken( 'developertoken' )
				->withOAuth2Credential( $oauth )
				->withEndpoint( $this->get_connect_server_endpoint() )
				->withTransport( 'rest' )
				->build();
		};

		$this->share_concrete( GoogleAdsClient::class, new Definition( GoogleAdsClient::class, $callback ) );
	}

	/**
	 * Register the various Google classes we use.
	 */
	protected function register_google_classes() {
		$this->add( Client::class )->addMethodCall( 'setHttpClient', [ ClientInterface::class ] );
		$this->add(
			Google_Service_ShoppingContent::class,
			Client::class,
			$this->get_connect_server_url_root( 'google-mc' )
		);
		$this->add(
			\Google_Service_SiteVerification::class,
			Client::class,
			$this->get_connect_server_url_root( 'google-sv' )
		);
		$this->share(
			GoogleProductService::class,
			Google_Service_ShoppingContent::class,
			Merchant::class
		);
	}


	/**
	 * @return callable
	 */
	protected function add_auth_header(): callable {
		return function( callable $handler ) {
			return function( RequestInterface $request, array $options ) use ( $handler ) {
				try {
					$request = $request->withHeader( 'Authorization', $this->generate_auth_header() );
				} catch ( WPError $error ) {
					do_action( 'gla_guzzle_client_exception', $error, __METHOD__ . ' in add_auth_header()' );
					throw new Exception( __( 'Jetpack authorization header error.', 'google-listings-and-ads' ), $error->getCode() );
				}

				return $handler( $request, $options );
			};
		};
	}

	/**
	 * Generate the authorization header for the GuzzleClient and GoogleAdsClient.
	 *
	 * @return string Empty if no access token is available.
	 *
	 * @throws WPError If the authorization token isn't found.
	 */
	protected function generate_auth_header(): string {
		/** @var Manager $manager */
		$manager = $this->getLeagueContainer()->get( Manager::class );
		$token   = $manager->get_access_token( false, false, false );
		$this->check_for_wp_error( $token );

		[ $key, $secret ] = explode( '.', $token->secret );

		$key       = sprintf(
			'%s:%d:%d',
			$key,
			defined( 'JETPACK__API_VERSION' ) ? JETPACK__API_VERSION : 1,
			$token->external_user_id
		);
		$timestamp = time() + (int) Jetpack_Options::get_option( 'time_diff' );
		$nonce     = wp_generate_password( 10, false );

		$request   = join( "\n", [ $key, $timestamp, $nonce, '' ] );
		$signature = base64_encode( hash_hmac( 'sha1', $request, $secret, true ) );
		$auth      = [
			'token'     => $key,
			'timestamp' => $timestamp,
			'nonce'     => $nonce,
			'signature' => $signature,
		];

		$pieces = [ 'X_JP_Auth' ];
		foreach ( $auth as $key => $value ) {
			$pieces[] = sprintf( '%s="%s"', $key, $value );
		}

		return join( ' ', $pieces );
	}

	/**
	 * Get the root Url for the connect server.
	 *
	 * @param string $path (Optional) A path relative to the root to include.
	 *
	 * @return RawArgument
	 */
	protected function get_connect_server_url_root( string $path = '' ): RawArgument {
		if ( ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
			define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'https://api.woocommerce.com/' );
		}
		$url = WOOCOMMERCE_CONNECT_SERVER_URL;
		$url = rtrim( $url, '/' );

		$path = '/' . trim( $path, '/' );

		return new RawArgument( "{$url}/google${path}" );
	}

	/**
	 * Get the connect server endpoint in the format `domain:port/path`
	 *
	 * @return string
	 */
	protected function get_connect_server_endpoint(): string {
		$parts = wp_parse_url( $this->get_connect_server_url_root( 'google-ads' )->getValue() );
		$port  = empty( $parts['port'] ) ? 443 : $parts['port'];
		return sprintf( '%s:%d%s', $parts['host'], $port, $parts['path'] );
	}

	/**
	 * Get the ads ID to use for requests.
	 *
	 * @return PositiveInteger
	 */
	protected function get_ads_id(): PositiveInteger {
		/** @var Options $options */
		$options = $this->getLeagueContainer()->get( OptionsInterface::class );

		// TODO: Remove overriding with default once ConnectionTest is removed.
		$default = intval( $_GET['customer_id'] ?? 0 ); // phpcs:ignore WordPress.Security
		$ads_id  = $default ?: $options->get( Options::ADS_ID );

		return new PositiveInteger( $ads_id );
	}

	/**
	 * Get the merchant ID to use for requests.
	 *
	 * @return PositiveInteger
	 */
	protected function get_merchant_id(): PositiveInteger {
		/** @var Options $options */
		$options = $this->getLeagueContainer()->get( OptionsInterface::class );

		// TODO: Remove overriding with default once ConnectionTest is removed.
		$default     = intval( $_GET['merchant_id'] ?? 0 ); // phpcs:ignore WordPress.Security
		$merchant_id = $default ?: $options->get( Options::MERCHANT_ID );

		return new PositiveInteger( $merchant_id );
	}
}
