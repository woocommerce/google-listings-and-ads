<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Config;
use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPError;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class ThirdPartyServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class ThirdPartyServiceProvider extends AbstractServiceProvider {

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
		Config::class       => true,
		Manager::class      => true,
		GoogleClient::class => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$jetpack_id = 'connection-test';
		$this->share( Manager::class )->addArgument( $jetpack_id );

		$this->share( Config::class )->addMethodCall(
			'ensure',
			[
				'connection',
				[
					'slug' => $jetpack_id,
					'name' => __( 'Connection Test', 'google-listings-and-ads' ),
				],
			]
		);

		$this->share_interface( ClientInterface::class, GuzzleClient::class )
			->addArgument( $this->get_guzzle_arguments() );

		$this->share( GoogleClient::class )->addMethodCall( 'setHttpClient', [ ClientInterface::class ] );
	}

	/**
	 * Get arguments for the Guzzle client.
	 *
	 * @return array Array of arguments for the client.
	 */
	protected function get_guzzle_arguments(): array {
		try {
			$auth_header = $this->generate_guzzle_auth_header();
			return [
				'headers' => [
					'Authorization' => $auth_header,
				],
			];
		} catch ( WPError $error ) {
			return [];
		}
	}

	/**
	 * Generate the authorization header for the Guzzle client.
	 *
	 * @return string
	 */
	protected function generate_guzzle_auth_header(): string {
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

		$pieces = [ 'X_JP_AUTH' ];
		foreach ( $auth as $key => $value ) {
			$pieces[] = sprintf( '%s="%s"', $key, $value );
		}

		return join( ' ', $pieces );
	}
}
