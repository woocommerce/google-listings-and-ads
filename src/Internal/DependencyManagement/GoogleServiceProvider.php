<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignBudget;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignCriterion;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPError;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GooglePromotionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReconnectWordPress;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification as SiteVerificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client as GuzzleClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\ClientInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Argument\RawArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\Definition;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\RequestInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;
use Google\Ads\GoogleAds\Util\V14\GoogleAdsFailures;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class GoogleServiceProvider extends AbstractServiceProvider {

	use PluginHelper;
	use WPErrorTrait;

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		Client::class                 => true,
		ShoppingContent::class        => true,
		GoogleAdsClient::class        => true,
		GuzzleClient::class           => true,
		Middleware::class             => true,
		Merchant::class               => true,
		MerchantMetrics::class        => true,
		Ads::class                    => true,
		AdsAssetGroup::class          => true,
		AdsCampaign::class            => true,
		AdsCampaignBudget::class      => true,
		AdsConversionAction::class    => true,
		AdsReport::class              => true,
		AdsAssetGroupAsset::class     => true,
		AdsAsset::class               => true,
		'connect_server_root'         => true,
		Connection::class             => true,
		GoogleProductService::class   => true,
		GooglePromotionService::class => true,
		SiteVerification::class       => true,
		Settings::class               => true,
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
		$this->share( Middleware::class, ContainerInterface::class );
		$this->add( Connection::class );
		$this->add( Settings::class, ContainerInterface::class );

		$this->share( Ads::class, GoogleAdsClient::class );
		$this->share( AdsAssetGroup::class, GoogleAdsClient::class, AdsAssetGroupAsset::class );
		$this->share( AdsCampaign::class, GoogleAdsClient::class, AdsCampaignBudget::class, AdsCampaignCriterion::class, GoogleHelper::class );
		$this->share( AdsCampaignBudget::class, GoogleAdsClient::class );
		$this->share( AdsAssetGroupAsset::class, GoogleAdsClient::class, AdsAsset::class );
		$this->share( AdsAsset::class, GoogleAdsClient::class, WP::class );
		$this->share( AdsCampaignCriterion::class );
		$this->share( AdsConversionAction::class, GoogleAdsClient::class );
		$this->share( AdsReport::class, GoogleAdsClient::class );

		$this->share( Merchant::class, ShoppingContent::class );
		$this->share( MerchantMetrics::class, ShoppingContent::class, GoogleAdsClient::class, WP::class, TransientsInterface::class );
		$this->share( MerchantReport::class, ShoppingContent::class, ProductHelper::class );

		$this->share( SiteVerification::class );

		$this->getLeagueContainer()->add( 'connect_server_root', $this->get_connect_server_url_root() );
	}

	/**
	 * Register guzzle with authorization middleware added.
	 */
	protected function register_guzzle() {
		$callback = function() {
			$handler_stack = HandlerStack::create();
			$handler_stack->remove( 'http_errors' );
			$handler_stack->push( $this->error_handler(), 'http_errors' );
			$handler_stack->push( $this->add_auth_header() );
			$handler_stack->push( $this->add_plugin_version_header(), 'plugin_version_header' );

			// Override endpoint URL if we are using http locally.
			if ( 0 === strpos( $this->get_connect_server_url_root()->getValue(), 'http://' ) ) {
				$handler_stack->push( $this->override_http_url() );
			}

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
			return new GoogleAdsClient( $this->get_connect_server_endpoint() );
		};

		$this->share_concrete(
			GoogleAdsClient::class,
			new Definition( GoogleAdsClient::class, $callback )
		)->addMethodCall( 'setHttpClient', [ ClientInterface::class ] );
	}

	/**
	 * Register the various Google classes we use.
	 */
	protected function register_google_classes() {
		$this->add( Client::class )->addMethodCall( 'setHttpClient', [ ClientInterface::class ] );
		$this->add(
			ShoppingContent::class,
			Client::class,
			$this->get_connect_server_url_root( 'google/google-mc' )
		);
		$this->add(
			SiteVerificationService::class,
			Client::class,
			$this->get_connect_server_url_root( 'google/google-sv' )
		);
		$this->share( GoogleProductService::class, ShoppingContent::class );
		$this->share( GooglePromotionService::class, ShoppingContent::class );
	}

	/**
	 * Custom error handler to detect and handle a disconnected status.
	 *
	 * @return callable
	 */
	protected function error_handler(): callable {
		return function( callable $handler ) {
			return function( RequestInterface $request, array $options ) use ( $handler ) {
				return $handler( $request, $options )->then(
					function ( ResponseInterface $response ) use ( $request ) {
						$code = $response->getStatusCode();

						$path = $request->getUri()->getPath();

						// Partial Failures come back with a status code of 200, so it's necessary to call GoogleAdsFailures:init every time.
						if ( strpos( $path, 'google-ads' ) !== false ) {
							GoogleAdsFailures::init();
						}

						if ( $code < 400 ) {
							return $response;
						}

						if ( 401 === $code ) {
							$this->handle_unauthorized_error( $request, $response );
						}

						throw RequestException::create( $request, $response );
					}
				);
			};
		};
	}

	/**
	 * Handle a 401 unauthorized error.
	 * Marks either the Jetpack or the Google account as disconnected.
	 *
	 * @since 1.12.5
	 *
	 * @param RequestInterface  $request
	 * @param ResponseInterface $response
	 *
	 * @throws AccountReconnect When an account must be reconnected.
	 */
	protected function handle_unauthorized_error( RequestInterface $request, ResponseInterface $response ) {
		// Log original exception before throwing reconnect exception.
		do_action( 'woocommerce_gla_exception', RequestException::create( $request, $response ), __METHOD__ );

		$auth_header = $response->getHeader( 'www-authenticate' )[0] ?? '';
		if ( 0 === strpos( $auth_header, 'X_JP_Auth' ) ) {
			$this->set_jetpack_connected( false );
			throw AccountReconnect::jetpack_disconnected();
		}

		$this->set_google_disconnected();
		throw AccountReconnect::google_disconnected();
	}

	/**
	 * @return callable
	 */
	protected function add_auth_header(): callable {
		return function( callable $handler ) {
			return function( RequestInterface $request, array $options ) use ( $handler ) {
				try {
					$request = $request->withHeader( 'Authorization', $this->generate_auth_header() );

					// Getting a valid authorization token, indicates Jetpack is connected.
					$this->set_jetpack_connected( true );
				} catch ( WPError $error ) {
					do_action( 'woocommerce_gla_guzzle_client_exception', $error, __METHOD__ . ' in add_auth_header()' );

					$this->set_jetpack_connected( false );
					throw AccountReconnect::jetpack_disconnected();
				}

				return $handler( $request, $options );
			};
		};
	}

	/**
	 * Add client name and version headers to request
	 *
	 * @since 2.4.11
	 *
	 * @return callable
	 */
	public function add_plugin_version_header(): callable {
		return function( callable $handler ) {
			return function( RequestInterface $request, array $options ) use ( $handler ) {
				$request = $request->withHeader( 'x-client-name', $this->get_client_name() )
								   ->withHeader( 'x-client-version', $this->get_version() );
				return $handler( $request, $options );
			};
		};
	}

	/**
	 * @return callable
	 */
	protected function override_http_url(): callable {
		return function( callable $handler ) {
			return function( RequestInterface $request, array $options ) use ( $handler ) {
				$request = $request->withUri( $request->getUri()->withScheme( 'http' ) );
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
		$token   = $manager->get_tokens()->get_access_token( false, false, false );
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
		$url  = trailingslashit( $this->get_connect_server_url() );
		$path = trim( $path, '/' );

		return new RawArgument( "{$url}{$path}" );
	}

	/**
	 * Get the connect server endpoint in the format `domain:port/path`
	 *
	 * @return string
	 */
	protected function get_connect_server_endpoint(): string {
		$parts = wp_parse_url( $this->get_connect_server_url_root( 'google/google-ads' )->getValue() );
		$port  = empty( $parts['port'] ) ? 443 : $parts['port'];
		return sprintf( '%s:%d%s', $parts['host'], $port, $parts['path'] );
	}

	/**
	 * Set the Google account connection as disconnected.
	 */
	protected function set_google_disconnected() {
		/** @var Options $options */
		$options = $this->getLeagueContainer()->get( OptionsInterface::class );
		$options->update( OptionsInterface::GOOGLE_CONNECTED, false );
	}

	/**
	 * Set the Jetpack account connection.
	 *
	 * @since 1.12.5
	 *
	 * @param bool $connected Is connected or disconnected?
	 */
	protected function set_jetpack_connected( bool $connected ) {
		/** @var Options $options */
		$options = $this->getLeagueContainer()->get( OptionsInterface::class );

		// Save previous connected status before updating.
		$previous_connected = boolval( $options->get( OptionsInterface::JETPACK_CONNECTED ) );

		$options->update( OptionsInterface::JETPACK_CONNECTED, $connected );

		if ( $previous_connected !== $connected ) {
			$this->jetpack_connected_change( $connected );
		}
	}

	/**
	 * Handle the reconnect notification when the Jetpack connection status changes.
	 *
	 * @since 1.12.5
	 *
	 * @param boolean $connected
	 */
	protected function jetpack_connected_change( bool $connected ) {
		/** @var ReconnectWordPress $note */
		$note = $this->getLeagueContainer()->get( ReconnectWordPress::class );

		if ( $connected ) {
			$note->delete();
		} else {
			$note->get_entry()->save();
		}
	}
}
