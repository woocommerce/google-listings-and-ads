<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAssetGenerationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignBudget;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignCriterion;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignLabel;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetRecommendations;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountBusinessInfoService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountHomepageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountIssuesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountRegionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountServicesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountShippingSettingsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountUsersService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiIssueResolutionService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiPromotionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPError;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notes\ReconnectWordPress;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification as SiteVerificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client as GuzzleClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\ClientInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Middleware as GuzzleMiddleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Utils;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\Definition;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\RequestInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;
use Google\Ads\GoogleAds\Util\V23\GoogleAdsFailures;
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
		Client::class                             => true,
		ShoppingContent::class                    => true,
		GoogleAdsClient::class                    => true,
		GuzzleClient::class                       => true,
		Middleware::class                         => true,
		Merchant::class                           => true,
		MerchantMetrics::class                    => true,
		Ads::class                                => true,
		AdsIncentives::class                      => true,
		AdsAssetGroup::class                      => true,
		AdsCampaign::class                        => true,
		AdsCampaignAsset::class                   => true,
		AdsCampaignBudget::class                  => true,
		AdsCampaignLabel::class                   => true,
		AdsConversionAction::class                => true,
		AdsReport::class                          => true,
		AdsRecommendationsService::class          => true,
		AdsAssetGenerationService::class          => true,
		AdsAssetGroupAsset::class                 => true,
		AdsAsset::class                           => true,
		BudgetMetrics::class                      => true,
		BudgetRecommendations::class              => true,
		'connect_server_root'                     => true,
		Connection::class                         => true,
		GoogleProductService::class               => true,
		MerchantApiClient::class                  => true,
		MapiProductsService::class                => true,
		MapiDataSourcesService::class             => true,
		MapiProductInputsService::class           => true,
		MapiPromotionsService::class              => true,
		MapiAccountIssuesService::class           => true,
		MapiIssueResolutionService::class         => true,
		MapiAccountHomepageService::class         => true,
		MapiAccountBusinessInfoService::class     => true,
		MapiAccountUsersService::class            => true,
		MapiAccountShippingSettingsService::class => true,
		MapiAccountRegionsService::class          => true,
		MapiAccountServicesService::class         => true,
		SiteVerification::class                   => true,
		Settings::class                           => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->container property or the `getContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_guzzle();
		$this->register_ads_client();
		$this->register_google_classes();
		$this->share( Middleware::class );
		$this->add( Connection::class );
		$this->add( Settings::class );

		$this->share( Ads::class, GoogleAdsClient::class );
		$this->share( AdsIncentives::class, GoogleAdsClient::class, WC::class );
		$this->share( AdsAssetGroup::class, GoogleAdsClient::class, AdsAssetGroupAsset::class, AdsCampaign::class );
		$this->share( AdsCampaign::class, GoogleAdsClient::class, AdsCampaignBudget::class, AdsCampaignCriterion::class, GoogleHelper::class, AdsCampaignLabel::class, AdsCampaignAsset::class );
		$this->share( AdsCampaignAsset::class, GoogleAdsClient::class );
		$this->share( AdsCampaignBudget::class, GoogleAdsClient::class );
		$this->share( AdsAssetGroupAsset::class, GoogleAdsClient::class, AdsAsset::class );
		$this->share( AdsAsset::class, GoogleAdsClient::class, WP::class );
		$this->share( AdsCampaignCriterion::class );
		$this->share( AdsCampaignLabel::class, GoogleAdsClient::class );
		$this->share( AdsConversionAction::class, GoogleAdsClient::class );
		$this->share( AdsReport::class, GoogleAdsClient::class );
		$this->share( AdsRecommendationsService::class, GoogleAdsClient::class );
		$this->share( AdsAssetGenerationService::class, GoogleAdsClient::class );
		$this->share( BudgetMetrics::class, GoogleAdsClient::class, MerchantMetrics::class );
		$this->share( BudgetRecommendations::class, GoogleAdsClient::class, MerchantMetrics::class );

		$this->share( Merchant::class, ShoppingContent::class, MapiAccountHomepageService::class, MapiAccountBusinessInfoService::class, MapiAccountUsersService::class, MapiAccountServicesService::class, MapiIssueResolutionService::class );
		$this->share( MerchantMetrics::class, MerchantApiClient::class, GoogleAdsClient::class, WP::class, TransientsInterface::class );
		$this->share( MerchantReport::class, ProductHelper::class, MerchantApiClient::class );

		$this->share( MerchantPriceBenchmarks::class, MerchantApiClient::class );

		$this->share( SiteVerification::class );

		$this->getContainer()->add( 'connect_server_root', $this->get_connect_server_url_root() );
	}

	/**
	 * Register guzzle with authorization middleware added.
	 */
	protected function register_guzzle() {
		$callback = function () {
			$handler_stack = HandlerStack::create();
			$handler_stack->remove( 'http_errors' );
			$handler_stack->push( $this->error_handler(), 'http_errors' );
			$handler_stack->push( $this->add_auth_header(), 'auth_header' );
			$handler_stack->push( $this->add_plugin_version_header(), 'plugin_version_header' );
			$handler_stack->push( $this->strip_apply_incentive_duplicates(), 'strip_incentive_duplicates' );

			// Override endpoint URL if we are using http locally.
			if ( 0 === strpos( $this->get_connect_server_url_root(), 'http://' ) ) {
				$handler_stack->push( $this->override_http_url(), 'override_http_url' );
			}

			// Innermost: retry transient failures before the error handler turns them into exceptions.
			$handler_stack->push( $this->retry_on_transient_error(), 'retry_on_transient_error' );

			return new GuzzleClient( [ 'handler' => $handler_stack ] );
		};

		$this->share_concrete( GuzzleClient::class, new Definition( GuzzleClient::class, $callback ) );
		$this->share_concrete( ClientInterface::class, new Definition( GuzzleClient::class, $callback ) );
	}

	/**
	 * Retry middleware for transient Merchant API failures: HTTP 429, 5xx, and
	 * connection errors. Honors a Retry-After header when present, otherwise uses
	 * capped exponential backoff with jitter.
	 *
	 * @return callable
	 */
	protected function retry_on_transient_error(): callable {
		$limit = (int) apply_filters( 'woocommerce_gla_mapi_retry_limit', 3 );

		return GuzzleMiddleware::retry(
			function ( int $retries, RequestInterface $request, ?ResponseInterface $response = null, ?\Throwable $reason = null ) use ( $limit ): bool {
				if ( $retries >= $limit ) {
					return false;
				}

				// No response means the request did not complete.
				if ( ! $response instanceof ResponseInterface ) {
					// A connection error never reached the server, so any method is safe.
					if ( $reason instanceof ConnectException ) {
						return true;
					}

					// Other transport failures (e.g. a reset mid-response) may have been
					// applied, so only retry idempotent requests or the product/batch paths.
					return $reason instanceof RequestException && $this->is_retryable_request( $request );
				}

				$code = $response->getStatusCode();

				// 429 (rate limited) is not applied server-side, so any method is safe to retry.
				if ( 429 === $code ) {
					return true;
				}

				// A 5xx may have been applied, so only retry idempotent requests (or the
				// product upsert/batch paths) to avoid duplicating non-idempotent writes.
				return $code >= 500 && $this->is_retryable_request( $request );
			},
			function ( int $retries, ?ResponseInterface $response = null ): int {
				return $this->retry_delay( $retries, $response );
			}
		);
	}

	/**
	 * The delay in milliseconds before a retry: the Retry-After header when present,
	 * otherwise capped exponential backoff with jitter. Both paths are capped so a
	 * large value cannot stall the background sync job.
	 *
	 * @param int                    $retries
	 * @param ResponseInterface|null $response
	 *
	 * @return int
	 */
	protected function retry_delay( int $retries, ?ResponseInterface $response = null ): int {
		$max_delay = 30000;

		if ( $response instanceof ResponseInterface && $response->hasHeader( 'Retry-After' ) ) {
			$retry_after = (int) $response->getHeaderLine( 'Retry-After' );

			if ( $retry_after > 0 ) {
				return (int) min( $retry_after * 1000, $max_delay );
			}
		}

		$backoff = ( 2 ** max( 0, $retries - 1 ) ) * 1000;

		return (int) min( $backoff + wp_rand( 0, 1000 ), $max_delay );
	}

	/**
	 * Whether a request is safe to retry on a 5xx: idempotent methods, or the product
	 * upsert/batch endpoints where a POST is an upsert rather than a create.
	 *
	 * @param RequestInterface $request
	 *
	 * @return bool
	 */
	protected function is_retryable_request( RequestInterface $request ): bool {
		if ( in_array( strtoupper( $request->getMethod() ), [ 'GET', 'HEAD', 'PUT', 'DELETE', 'PATCH' ], true ) ) {
			return true;
		}

		$path = $request->getUri()->getPath();

		return false !== strpos( $path, 'productInputs' ) || '/batch' === substr( $path, -6 );
	}

	/**
	 * Register ads client.
	 */
	protected function register_ads_client() {
		$callback = function () {
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

		$this->share(
			MerchantApiClient::class,
			ClientInterface::class,
			$this->get_connect_server_url_root( 'google/google-merchant' )
		);
		$this->share( MapiProductsService::class, MerchantApiClient::class );
		$this->share( MapiDataSourcesService::class, MerchantApiClient::class );
		$this->share( MapiProductInputsService::class, MerchantApiClient::class, MapiDataSourcesService::class );
		$this->share( MapiPromotionsService::class, MerchantApiClient::class );
		$this->share( MapiAccountIssuesService::class, MerchantApiClient::class );
		$this->share( MapiIssueResolutionService::class, MerchantApiClient::class );
		$this->share( MapiAccountHomepageService::class, MerchantApiClient::class );
		$this->share( MapiAccountBusinessInfoService::class, MerchantApiClient::class );
		$this->share( MapiAccountUsersService::class, MerchantApiClient::class );
		$this->share( MapiAccountShippingSettingsService::class, MerchantApiClient::class );
		$this->share( MapiAccountRegionsService::class, MerchantApiClient::class );
		$this->share( MapiAccountServicesService::class, MerchantApiClient::class );
	}

	/**
	 * Custom error handler to detect and handle a disconnected status.
	 *
	 * @return callable
	 */
	protected function error_handler(): callable {
		return function ( callable $handler ) {
			return function ( RequestInterface $request, array $options ) use ( $handler ) {
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
		$auth_header = $response->getHeader( 'www-authenticate' )[0] ?? '';
		if ( 0 === strpos( $auth_header, 'X_JP_Auth' ) ) {
			// Log original exception before throwing reconnect exception.
			do_action( 'woocommerce_gla_exception', RequestException::create( $request, $response ), __METHOD__ );

			$this->set_jetpack_connected( false );
			throw AccountReconnect::jetpack_disconnected();
		}

		// Exclude listing customers as it will handle it's own unauthorized errors.
		$path = $request->getUri()->getPath();
		if ( false === strpos( $path, 'customers:listAccessibleCustomers' ) ) {
			// Log original exception before throwing reconnect exception.
			do_action( 'woocommerce_gla_exception', RequestException::create( $request, $response ), __METHOD__ );

			$this->set_google_disconnected();
			throw AccountReconnect::google_disconnected();
		}
	}

	/**
	 * @return callable
	 */
	protected function add_auth_header(): callable {
		return function ( callable $handler ) {
			return function ( RequestInterface $request, array $options ) use ( $handler ) {
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
	protected function add_plugin_version_header(): callable {
		return function ( callable $handler ) {
			return function ( RequestInterface $request, array $options ) use ( $handler ) {
				$request = $request->withHeader( 'x-client-name', $this->get_client_name() )
					->withHeader( 'x-client-version', $this->get_version() );
				return $handler( $request, $options );
			};
		};
	}

	/**
	 * Strip path-bound fields from the ApplyIncentive request body.
	 *
	 * The ApplyIncentive REST config places customer_id and selected_incentive_id
	 * in both the URL path and the JSON body (body: *). WCS cannot handle proto3
	 * optional fields that appear in both locations, so we remove them from the
	 * body before the request is sent.
	 *
	 * @since 3.3.0
	 *
	 * @return callable
	 */
	protected function strip_apply_incentive_duplicates(): callable {
		return function ( callable $handler ) {
			return function ( RequestInterface $request, array $options ) use ( $handler ) {
				$path = $request->getUri()->getPath();

				if ( false !== strpos( $path, ':applyIncentive' ) ) {
					$body = json_decode( (string) $request->getBody(), true );

					if ( is_array( $body ) ) {
						unset( $body['selectedIncentiveId'], $body['customerId'] );
						$request = $request->withBody(
							Utils::streamFor( wp_json_encode( $body ) )
						);
					}
				}

				return $handler( $request, $options );
			};
		};
	}

	/**
	 * @return callable
	 */
	protected function override_http_url(): callable {
		return function ( callable $handler ) {
			return function ( RequestInterface $request, array $options ) use ( $handler ) {
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
		$manager = $this->getContainer()->get( Manager::class );
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
	 * @return string
	 */
	protected function get_connect_server_url_root( string $path = '' ): string {
		$url  = trailingslashit( $this->get_connect_server_url() );
		$path = trim( $path, '/' );

		return "{$url}{$path}";
	}

	/**
	 * Get the connect server endpoint in the format `domain:port/path`
	 *
	 * @return string
	 */
	protected function get_connect_server_endpoint(): string {
		$parts = wp_parse_url( $this->get_connect_server_url_root( 'google/google-ads' ) );
		$port  = empty( $parts['port'] ) ? 443 : $parts['port'];
		return sprintf( '%s:%d%s', $parts['host'], $port, $parts['path'] );
	}

	/**
	 * Set the Google account connection as disconnected.
	 */
	protected function set_google_disconnected() {
		/** @var Options $options */
		$options = $this->getContainer()->get( OptionsInterface::class );
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
		$options = $this->getContainer()->get( OptionsInterface::class );

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
		$note = $this->getContainer()->get( ReconnectWordPress::class );

		if ( $connected ) {
			$note->delete();
		} else {
			$note->get_entry()->save();
		}
	}
}
