<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInitializer;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements\PluginValidator;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

/**
 * Class GoogleListingsAndAdsPlugin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
final class GoogleListingsAndAdsPlugin implements Plugin {

	/**
	 * The hook for registering our plugin's services.
	 *
	 * @var string
	 */
	private const SERVICE_REGISTRATION_HOOK = 'plugins_loaded';

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @var Service[]
	 */
	private $registered_services;

	/**
	 * The client ID.
	 * @var string
	 */
	private $client_id;

	/**
	 * GoogleListingsAndAdsPlugin constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public function activate(): void {
		// Delay activation if a required plugin is missing or an incompatible plugin is active.
		if ( ! PluginValidator::validate() ) {
			// Using update_option because we cannot access the option service
			// when the services have not been registered.
			update_option( 'gla_' . OptionsInterface::DELAYED_ACTIVATE, true );
			return;
		}

		$this->maybe_register_services();

		foreach ( $this->registered_services as $service ) {
			if ( $service instanceof Activateable ) {
				$service->activate();
			}
		}
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->maybe_register_services();

		foreach ( $this->registered_services as $service ) {
			if ( $service instanceof Deactivateable ) {
				$service->deactivate();
			}
		}
	}

	/**
	 * Register the plugin with the WordPress system.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			self::SERVICE_REGISTRATION_HOOK,
			function () {
				$this->maybe_register_services();
			},
			20
		);

		add_action(
			'init',
			function () {
				// register the job initializer only if it is available. see JobInitializer::is_needed.
				if ( $this->container->has( JobInitializer::class ) ) {
					$this->container->get( JobInitializer::class )->register();
				}

				// Check if activation is still pending.
				if ( $this->container->get( OptionsInterface::class )->get( OptionsInterface::DELAYED_ACTIVATE ) ) {
					$this->activate();
					// Remove the DELAYED_ACTIVATE flag.
					$this->container->get( OptionsInterface::class )->delete( OptionsInterface::DELAYED_ACTIVATE );
				}
			}
		);
	}

	/**
	 * Register our services if dependency validation passes.
	 */
	protected function maybe_register_services(): void {
		// Don't register anything if a required plugin is missing or an incompatible plugin is active.
		if ( ! PluginValidator::validate() ) {
			$this->registered_services = [];
			return;
		}

		static $registered = false;
		if ( $registered ) {
			return;
		}

		/** @var Service[] $services */
		$services = $this->container->get( Service::class );
		foreach ( $services as $service ) {
			if ( $service instanceof Registerable ) {
				$service->register();
			}
			$this->registered_services[ get_class( $service ) ] = $service;
		}

		$registered = true;
	}

	/**
	 * Handles the login action for Authorizing the JSON API
	 */
	public function login_form_json_api_authorization() {
		add_action( 'wp_login', array( $this, 'store_json_api_authorization_token' ), 10, 2 );
		add_action( 'login_form', array( $this, 'preserve_action_in_login_form_for_json_api_authorization' ) );
		add_filter( 'site_url', array( $this, 'post_login_form_to_signed_url' ), 10, 3 );
	}

	/**
	 * If someone logs in to approve API access, store the Access Code in usermeta.
	 *
	 * @param string  $user_login Unused.
	 * @param WP_User $user User logged in.
	 */
	public function store_json_api_authorization_token( $user_login, $user ) {
		$data         = json_decode( base64_decode( stripslashes( $_REQUEST['data'] ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$this->client_id = $data->client_id;
		add_filter( 'login_redirect', array( $this, 'add_token_to_login_redirect_json_api_authorization' ), 10, 3 );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_wpcom_public_api_domain' ) );
		$token = wp_generate_password( 32, false );
		update_user_meta( $user->ID, 'jetpack_json_api_' . $this->client_id, $token );
	}

	/**
	 * Make sure the POSTed request is handled by the same action.
	 */
	public function preserve_action_in_login_form_for_json_api_authorization() {
		$http_host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped with esc_url below.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped with esc_url below.
		echo "<input type='hidden' name='action' value='jetpack_json_api_authorization' />\n";
		echo "<input type='hidden' name='jetpack_json_api_original_query' value='" . esc_url( set_url_scheme( $http_host . $request_uri ) ) . "' />\n";
	}

	/**
	 * Make sure the login form is POSTed to the signed URL so we can reverify the request.
	 *
	 * @param string $url Redirect URL.
	 * @param string $path Path.
	 * @param string $scheme URL Scheme.
	 */
	public function post_login_form_to_signed_url( $url, $path, $scheme ) {
		if ( 'wp-login.php' !== $path || ( 'login_post' !== $scheme && 'login' !== $scheme ) ) {
			return $url;
		}
		$query_string = isset( $_SERVER['QUERY_STRING'] ) ? wp_unslash( $_SERVER['QUERY_STRING'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$parsed_url   = wp_parse_url( $url );
		$url          = strtok( $url, '?' );
		$url          = "$url?{$query_string}";
		if ( ! empty( $parsed_url['query'] ) ) {
			$url .= "&{$parsed_url['query']}";
		}

		return $url;
	}

	/**
	 * Add the Access Code details to the public-api.wordpress.com redirect.
	 *
	 * @param string  $redirect_to URL.
	 * @param string  $original_redirect_to URL.
	 * @param WP_User $user WP_User for the redirect.
	 *
	 * @return string
	 */
	public function add_token_to_login_redirect_json_api_authorization( $redirect_to, $original_redirect_to, $user ) {
		return add_query_arg(
			urlencode_deep(
				array(
					'jetpack-code'    => get_user_meta( $user->ID, 'jetpack_json_api_' . $this->client_id, true ),
					'jetpack-user-id' => (int) $user->ID,
					'jetpack-state'   => '',
				)
			),
			$redirect_to
		);
	}

	/**
	 * Add public-api.wordpress.com to the safe redirect allowed list - only added when someone allows API access.
	 *
	 * To be used with a filter of allowed domains for a redirect.
	 *
	 * @param array $domains Allowed WP.com Environments.
	 */
	public function allow_wpcom_public_api_domain( $domains ) {
		$domains[] = 'public-api.wordpress.com';
		return $domains;
	}
}
