<?php
// phpcs:ignoreFile

/**
 * Main plugin class.
 *
 * @package connection-test
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\Jetpack\Connection\Manager;

/**
 * Main class for Connection Test.
 */
class ConnectionTest {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * Store response from an API request.
	 *
	 * @var string
	 */
	public static $response = '';

	/**
	 * Initialize class.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'do_actions' ) );
	}

	/**
	 * Add menu entries
	 */
	public static function admin_menu() {
		add_menu_page(
			'Connection Test',
			'Connection Test',
			'manage_options',
			'connection-test-admin-page',
			array( __CLASS__, 'admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 */
	public static function admin_page() {
		$manager    = new Manager( 'connection-test' );
		$blog_token = $manager->get_access_token();
		$user_token = $manager->get_access_token( get_current_user_id() );
		$url        = admin_url( 'admin.php?page=connection-test-admin-page' );

		if ( ! empty( $_GET['google-mc'] ) && 'connected' === $_GET['google-mc'] ) {
			self::$response .= 'Google Merchant Center connected successfully.';
		}

		if ( ! empty( $_GET['google'] ) && 'failed' === $_GET['google'] ) {
			self::$response .= 'Failed to connect to Google.';
		}

		?>
		<div class="wrap">
			<h2>Connection Test</h2>

			<?php if ( $blog_token ) { ?>
				<p>Site is connected. Blog token:</p>
				<pre><?php var_dump( $blog_token ); ?></pre>
			<?php } ?>

			<?php if ( $user_token ) { ?>
				<p>Connected as an authenticated user. User token:</p>
				<pre><?php var_dump( $user_token ); ?></pre>
			<?php } ?>

			<?php if ( ! $blog_token || ! $user_token ) { ?>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'connect' ), $url ), 'connect' ) ); ?>">Connect to Jetpack</a></p>
			<?php } ?>

			<?php if ( $blog_token && $user_token ) { ?>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'disconnect' ), $url ), 'disconnect' ) ); ?>">Disconnect Jetpack</a></p>
			<?php } ?>

			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-test' ), $url ), 'wcs-test' ) ); ?>">Test WCS API</a></p>

			<?php if ( $blog_token && $user_token ) { ?>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-auth-test' ), $url ), 'wcs-auth-test' ) ); ?>">Test WCS API with an authenticated request</a></p>

				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-mc' ), $url ), 'wcs-google-mc' ) ); ?>">Connect to Google Merchant Center</a></p>

				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-mc-disconnect' ), $url ), 'wcs-google-mc-disconnect' ) ); ?>">Disconnect Google Merchant Center</a></p>

				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-mc-id' ), $url ), 'wcs-google-mc-id' ) ); ?>">Get Merchant Center ID</a></p>

				<p>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<?php wp_nonce_field( 'wcs-google-mc-proxy' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-google-mc-proxy" type="hidden" />
						<label>
							Merchant ID <input name="merchant_id" type="text" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
						</label>
						<button class="button">Send proxied request to Google Merchant Center</button>
					</form>
				</p>
			<?php } ?>

			<?php if ( ! empty( self::$response ) ) { ?>
				<pre><?php echo wp_kses_post( self::$response ); ?></pre>
			<?php } ?>

		</div>
		<?php
	}

	/**
	 * Handle actions.
	 */
	public static function do_actions() {

		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		if ( 'connect' === $_GET['action'] && check_admin_referer( 'connect' ) ) {
			$manager = new Manager( 'connection-test' );
			$manager->enable_plugin(); // Mark the plugin connection as enabled, in case it was disabled earlier.

			// Register the site to wp.com.
			if ( ! $manager->is_registered() ) {
				$result = $manager->register();

				if ( is_wp_error( $result ) ) {
					self::$response .= $result->get_error_message();
					return;
				}
			}

			// Get an authorization URL which will redirect back to our page.
			$redirect = admin_url( 'admin.php?page=connection-test-admin-page' );
			$auth_url = $manager->get_authorization_url( null, $redirect );

			// Payments flow allows redirect back to the site without showing plans.
			$auth_url = add_query_arg( array( 'from' => 'woocommerce-payments' ), $auth_url );

			error_log( $auth_url );

			// Using wp_redirect intentionally because we're redirecting outside.
			wp_redirect( $auth_url ); // phpcs:ignore WordPress.Security.SafeRedirect
			exit;
		}

		if ( 'disconnect' === $_GET['action'] && check_admin_referer( 'disconnect' ) ) {
			$manager = new Manager( 'connection-test' );
			$manager->remove_connection();

			$redirect = admin_url( 'admin.php?page=connection-test-admin-page' );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( 'wcs-test' === $_GET['action'] && check_admin_referer( 'wcs-test' ) ) {
			$url            = WOOCOMMERCE_CONNECT_SERVER_URL;
			self::$response = 'GET ' . $url . "\n";

			$response = wp_remote_get( $url );
			if ( is_wp_error( $response ) ) {
				self::$response .= $response->get_error_message();
				return;
			}

			self::$response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-auth-test' === $_GET['action'] && check_admin_referer( 'wcs-auth-test' ) ) {
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'connection/test';
			$args = [
				'headers' => [ 'Authorization' => self::get_auth_header() ],
			];

			self::$response = 'GET ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				self::$response .= $response->get_error_message();
				return;
			}

			self::$response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-google-mc' === $_GET['action'] && check_admin_referer( 'wcs-google-mc' ) ) {
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/connection/google-mc';
			$args = [
				'headers' => [ 'Authorization' => self::get_auth_header() ],
				'body'    => [
					'returnUrl' => admin_url( 'admin.php?page=connection-test-admin-page' ),
				],
			];

			self::$response = 'POST ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				self::$response .= $response->get_error_message();
				return;
			}

			self::$response .= wp_remote_retrieve_body( $response );

			$json = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $json && isset( $json['oauthUrl'] ) ) {
				wp_redirect( $json['oauthUrl'] ); // phpcs:ignore WordPress.Security.SafeRedirect
				exit;
			}
		}

		if ( 'wcs-google-mc-disconnect' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-disconnect' ) ) {
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/connection/google-mc';
			$args = [
				'headers' => [ 'Authorization' => self::get_auth_header() ],
				'method'  => 'DELETE',
			];

			self::$response = 'DELETE ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				self::$response .= $response->get_error_message();
				return;
			}

			self::$response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-google-mc-id' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-id' ) ) {
			try {
				self::$response = 'Proxied request > get merchant ID' . "\n";

				$service  = self::mc_service();
				$accounts = $service->accounts->authinfo();

				if ( ! empty( $accounts->getAccountIdentifiers() ) ) {
					foreach ( $accounts->getAccountIdentifiers() as $account ) {
						self::$response .= sprintf( "Merchant ID: %s\n", $account->getMerchantId() );

						$_GET['merchant_id'] = $account->getMerchantId();
					}
				}
			} catch ( \Exception $e ) {
				self::$response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-google-mc-proxy' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-proxy' ) ) {
			try {
				$merchant_id = ! empty( $_GET['merchant_id'] ) ? absint( $_GET['merchant_id'] ) : '12345';
				$service     = self::mc_service();
				$products    = $service->products->listProducts( $merchant_id );

				self::$response = 'Proxied request > get products for merchant ' . $merchant_id . "\n";

				if ( empty( $products->getResources() ) ) {
					self::$response .= 'No products found';
				}

				while ( ! empty( $products->getResources() ) ) {
					foreach ( $products->getResources() as $product ) {
						self::$response .= sprintf( "%s %s\n", $product->getId(), $product->getTitle() );
					}
					if ( ! empty( $products->getNextPageToken() ) ) {
						$products = $service->products->listProducts( $merchant_id, [ 'pageToken' => $products->getNextPageToken() ] );
					}
				}
			} catch ( \Exception $e ) {
				self::$response .= 'Error: ' . $e->getMessage();
			}
		}
	}

	/**
	 * Get Merchant Center service (with proxied URL).
	 *
	 * @return Google_Service_ShoppingContent
	 */
	private static function mc_service() {
		$root_url = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/google-mc';
		return new \Google_Service_ShoppingContent( self::google_client(), $root_url );
	}

	/**
	 * Get Google client (with custom authentication header).
	 *
	 * @return Google\Client
	 */
	private static function google_client() {
		$client = new \Google\Client();
		$client->setHttpClient(
			new \GuzzleHttp\Client(
				[
					'headers' => [
						'Authorization' => self::get_auth_header(),
					],
				]
			)
		);

		return $client;
	}

	/**
	 * Retrieve an authorization header containing a Jetpack token.
	 *
	 * @return string Authorization header.
	 */
	private static function get_auth_header() {
		$manager = new Manager( 'connection-test' );
		$token   = $manager->get_access_token();

		list( $token_key, $token_secret ) = explode( '.', $token->secret );

		$token_key = sprintf( '%s:%d:%d', $token_key, defined( 'JETPACK__API_VERSION' ) ? JETPACK__API_VERSION : 1, $token->external_user_id );
		$time_diff = (int) \Jetpack_Options::get_option( 'time_diff' );
		$timestamp = time() + $time_diff;
		$nonce     = wp_generate_password( 10, false );

		$normalized_request_string = join( "\n", array( $token_key, $timestamp, $nonce ) ) . "\n";

		$signature = base64_encode( hash_hmac( 'sha1', $normalized_request_string, $token_secret, true ) );

		$auth = array(
			'token'     => $token_key,
			'timestamp' => $timestamp,
			'nonce'     => $nonce,
			'signature' => $signature,
		);

		$header_pieces = array();
		foreach ( $auth as $key => $value ) {
			$header_pieces[] = sprintf( '%s="%s"', $key, $value );
		}

		return 'X_JP_Auth ' . join( ' ', $header_pieces );
	}
}
