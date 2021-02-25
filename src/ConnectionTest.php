<?php
// phpcs:ignoreFile

/**
 * Main plugin class.
 *
 * @package connection-test
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;
use Jetpack_Options;
use Psr\Container\ContainerInterface;

/**
 * Main class for Connection Test.
 */
class ConnectionTest implements Service, Registerable {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * ConnectionTest constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
			define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'https://api-vipgo.woocommerce.com' );
		}

		add_action(
			'admin_menu',
			function() {
				$this->register_admin_menu();
			}
		);

		add_action(
			'admin_init',
			function() {
				$this->handle_actions();
			}
		);
	}

	/**
	 * Store response from an API request.
	 *
	 * @var string
	 */
	protected $response = '';

	/**
	 * Add menu entries
	 */
	protected function register_admin_menu() {
		add_menu_page(
			'Connection Test',
			'Connection Test',
			'manage_options',
			'connection-test-admin-page',
			function() {
				$this->render_admin_page();
			}
		);
	}

	/**
	 * Render the admin page.
	 */
	protected function render_admin_page() {
		/** @var Manager $manager */
		$manager    = $this->container->get( Manager::class );
		$blog_token = $manager->get_access_token();
		$user_token = $manager->get_access_token( get_current_user_id() );
		$user_data  = $manager->get_connected_user_data( get_current_user_id() );
		$url        = admin_url( 'admin.php?page=connection-test-admin-page' );

		if ( ! empty( $_GET['google-mc'] ) && 'connected' === $_GET['google-mc'] ) {
			$this->response .= 'Google Merchant Center connected successfully.';
		}

		if ( ! empty( $_GET['google-manager'] ) && 'connected' === $_GET['google-manager'] ) {
			$this->response .= 'Successfully connected a Google Manager account.';
		}

		if ( ! empty( $_GET['google'] ) && 'failed' === $_GET['google'] ) {
			$this->response .= 'Failed to connect to Google.';
		}

		?>
		<div class="wrap">
			<h2>Connection Test</h2>

			<p>Google Listings & Ads connection testing page used for debugging purposes. Debug responses are output at the top of the page.</p>

			<hr />

			<?php if ( ! empty( $this->response ) ) { ?>
				<div style="padding: 10px 20px; background: #e1e1e1;">
					<h2 class="title">Response</h2>
						<pre style="
							overflow: auto;
							word-break: normal !important;
							word-wrap: normal !important;
							white-space: pre !important;"
						><?php echo wp_kses_post( $this->response ); ?></pre>
				</div>
			<?php } ?>

			<h2 class="title">WooCommerce Connect Server</h2>

			<table class="form-table" role="presentation">
				<tr>
					<th><label>WCS Server:</label></th>
					<td>
						<p>
							<code><?php echo defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_SERVER_URL : 'http://localhost:5000'; ?></code>
						</p>
					</td>
				</tr>

				<tr>
					<th>Test WCS Connection:</th>
					<td>
						<p>
							<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-test' ], $url ), 'wcs-test' ) ); ?>">Test</a>
						</p>
					</td>
				</tr>

			</table>

			<hr />

			<h2 class="title">Jetpack</h2>

			<table class="form-table" role="presentation">

				<?php if ( $blog_token ) { ?>
					<tr>
						<th><label>Site ID:</label></th>
						<td>
							<p>
								<code><?php echo Jetpack_Options::get_option( 'id' ); ?></code>
							</p>
							<!--<pre><?php var_dump( $blog_token ); ?></pre>-->
						</td>
					</tr>
				<?php } ?>

				<?php if ( $user_token ) { ?>
					<tr>
						<th><label>User ID:</label></th>
						<td>
							<p>
								<code><?php echo $user_data['ID']; ?></code>
							</p>
							<!--<pre><?php var_dump( $user_token ); ?></pre>-->
						</td>
					</tr>
				<?php } ?>

				<?php if ( $blog_token && $user_token ) { ?>
				<tr>
					<th>Test Authenticated WCS Request:</th>
					<td>
						<p>
							<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-auth-test' ], $url ), 'wcs-auth-test' ) ); ?>">Test Authenticated Request</a>
						</p>
					</td>
				</tr>
				<?php } ?>

				<tr>
					<th>Toggle Connection:</th>
					<td>
						<?php if ( ! $blog_token || ! $user_token ) { ?>
							<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'connect' ], $url ), 'connect' ) ); ?>">Connect to Jetpack</a></p>
						<?php } ?>
						<?php if ( $blog_token && $user_token ) { ?>
							<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'disconnect' ], $url ), 'disconnect' ) ); ?>">Disconnect Jetpack</a></p>
						<?php } ?>
					</td>
				</tr>

			</table>

			<hr />

			<?php if ( $blog_token && $user_token ) { ?>

				<h2 class="title">Google Account</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th>Connect:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-mc' ], $url ), 'wcs-google-mc' ) ); ?>">Connect Google Account</a>
							</p>
						</td>
					</tr>
					<tr>
						<th>Disconnect:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-mc-disconnect' ], $url ), 'wcs-google-mc-disconnect' ) ); ?>">Disconnect Google Account</a>
							</p>
						</td>
					</tr>
					<tr>
						<th>Get Status:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-mc-status' ], $url ), 'wcs-google-mc-status' ) ); ?>">Google Account Status</a>
							</p>
						</td>
					</tr>
				</table>

				<hr />

				<h2 class="title">Merchant Center</h2>

				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Get Merchant Center ID(s):</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-mc-id' ], $url ), 'wcs-google-mc-id' ) ); ?>">Get Merchant Center ID(s)</a>
								</p>
							</td>
						</tr>
						<tr>
							<th>Merchant ID:</th>
							<td>
								<p>
									<input name="merchant_id" type="text" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
									<button class="button">Send proxied request to Google Merchant Center</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-google-mc-proxy' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-google-mc-proxy" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">

					<table class="form-table" role="presentation">
						<tr>
							<th>MC Account Setup:</th>
							<td>
								<p>
									<label title="Use a live site!">
										Site URL <input name="site_url" type="text" style="width:14em; font-size:.9em" value="<?php echo ! empty( $_GET['site_url'] ) ? ( $_GET['site_url'] ) : apply_filters( 'woocommerce_gla_site_url', site_url() ); ?>" />
									</label>
									<label title="To simulate linking with an external site">
										MC ID <input name="account_id" type="text" style="width:8em; font-size:.9em" value="<?php echo ! empty( $_GET['account_id'] ) ? intval( $_GET['account_id'] ) : ''; ?>" />
									</label>
									<button class="button">MC Account Setup (I & II)</button>
								</p>

								<?php if ( $this->container->get( OptionsInterface::class )->get( OptionsInterface::MERCHANT_ID ) ) : ?>
									<p class="description">
										( Merchant Center connected -- ID: <?php echo $this->container->get( OptionsInterface::class )->get( OptionsInterface::MERCHANT_ID ); ?> ||
										<?php foreach ( $this->container->get( OptionsInterface::class )->get( OptionsInterface::MERCHANT_ACCOUNT_STATE, [] ) as $name => $step ) : ?>
											<?php echo $name . ':' . $step['status']; ?>
										<?php endforeach; ?>
										)
									</p>
								<?php endif; ?>
								<p class="description">
									Begins/continues four-step account-setup sequence: creation, verification, linking, claiming.
								</p>
								<p class="description">Claim overwrite performed with <a href="#overwrite">Claim Overwrite button</a>.
								</p>
								<p class="description">
									If no MC ID is provided, then a sub-account will be created under our MCA.
								</p>
								<p class="description">
									Adds <em>gla_merchant_id</em> to site options.
								</p>
							</td>
						</tr>
						<tr>
							<th>Check MC Status:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-accounts-check' ], $url ), 'wcs-google-accounts-check' ) ); ?>">MC Connection Status</a>
								</p>
							</td>
						</tr>
						<tr>
							<th>Disconnect MC:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-accounts-delete' ), $url ), 'wcs-google-accounts-delete' ) ); ?>">MC Disconnect</a>
								</p>
							</td>
						</tr>
						<tr>
							<th><a name="overwrite"></a>Claim Overwrite:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-mc-claim-overwrite' ], $url ), 'wcs-google-mc-claim-overwrite' ) ); ?>">Claim Overwrite</a>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-google-mc-setup' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-google-mc-setup" type="hidden" />
				</form>

				<details>
					<summary><strong>More Merchant Center</strong></summary>
					<p class="description">For single-step development testing, not used for normal account setup flow.</p>
				<table class="form-table" role="presentation">
					<tr>
						<th>Perform Verification:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-sv-token' ], $url ), 'wcs-google-sv-token' ) ); ?>">Perform Site Verification</a>
							</p>
						</td>
					</tr>
					<tr>
						<th>Check Verification:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-sv-check' ], $url ), 'wcs-google-sv-check' ) ); ?>">Check Site Verification</a>
							</p>
						</td>
					</tr>
					<tr>
						<th>Link Site to MCA:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-sv-link' ], $url ), 'wcs-google-sv-link' ) ); ?>">Link Site to MCA</a>
							</p>
						</td>
					</tr>
					<tr>
						<th>Claim Website:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-accounts-claim' ], $url ), 'wcs-google-accounts-claim' ) ); ?>">Claim website</a>
							</p>
						</td>
					</tr>
				</table>

				</details>
				<br>
				<hr />

				<h2 class="title">Google Ads</h2>

				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Get Customers:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-ads-customers-lib' ], $url ), 'wcs-ads-customers-lib' ) ); ?>">Get Customers from Google Ads</a>
								</p>
							</td>
						</tr>
						<tr>
							<th>Get Campaigns:</th>
							<td>
								<p>
									<label>
										Customer ID <input name="customer_id" type="text" value="<?php echo ! empty( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : ''; ?>" />
									</label>
									<button class="button">Get Campaigns from Google Ads</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-ads-campaign-lib' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-ads-campaign-lib" type="hidden" />
				</form>

				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Create Ads Customer:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-ads-create' ], $url ), 'wcs-google-ads-create' ) ); ?>">Create Google Ads customer as a sub account</a>
								</p>
								<ol>
									<li>Create account</li>
									<li>Direct user to billing flow (not implemented yet)</li>
								</ol>
							</td>
						</tr>
						<tr>
							<th>Link Google Ads Customer:</th>
							<td>
								<p>
									<label>
										Customer ID <input name="customer_id" type="text" value="<?php echo ! empty( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : ''; ?>" />
									</label>
									<button class="button">Link existing Google Ads customer to the site</button>
								</p>
								<ol>
									<li>Link to manager account</li>
									<li>Link to merchant account (not implemented yet)</li>
								</ol>
							</td>
						</tr>
						<tr>
							<th>Check Ads Status:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-google-ads-check' ], $url ), 'wcs-google-ads-check' ) ); ?>">Ads Connection Status</a>
								</p>
							</td>
						</tr>
						<tr>
							<th>Disconnect Ads:</th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-ads-disconnect' ), $url ), 'wcs-google-ads-disconnect' ) ); ?>">Ads Disconnect</a>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-google-ads-link' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-google-ads-link" type="hidden" />
				</form>

				<hr />

				<h2 class="title">Terms of Service</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th>Accept Merchant Center ToS:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-accept-tos' ], $url ), 'wcs-accept-tos' ) ); ?>">Accept ToS for Google</a>
							</p>
						</td>
					</tr>
					<tr>
						<th>Get Latest Merchant Center ToS:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-check-tos' ], $url ), 'wcs-check-tos' ) ); ?>">Get latest ToS for Google</a>
							</p>
						</td>
					</tr>
				</table>

				<hr />

				<h2 class="title">Product Sync</h2>

				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Sync Product:</th>
							<td>
								<p>
									<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
									<label>
										Product ID <input name="product_id" type="text" value="<?php echo ! empty( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : ''; ?>" />
									</label>
									<button class="button">Sync Product with Google Merchant Center</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-sync-product' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-sync-product" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Sync All Products:</th>
							<td>
								<p>
									<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
									<button class="button">Sync All Products with Google Merchant Center</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-sync-all-products' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-sync-all-products" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Delete All Synced Products:</th>
							<td>
								<p>
									<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
									<button class="button">Delete All Synced Products from Google Merchant Center</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-delete-synced-products' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-delete-synced-products" type="hidden" />
				</form>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Handle actions.
	 */
	protected function handle_actions() {
		if ( ! isset( $_GET['page'], $_GET['action'] ) || 'connection-test-admin-page' !== $_GET['page'] ) {
			return;
		}

		/** @var Manager $manager */
		$manager = $this->container->get( Manager::class );

		if ( 'connect' === $_GET['action'] && check_admin_referer( 'connect' ) ) {
			$manager->enable_plugin(); // Mark the plugin connection as enabled, in case it was disabled earlier.

			// Register the site to wp.com.
			if ( ! $manager->is_registered() ) {
				$result = $manager->register();

				if ( is_wp_error( $result ) ) {
					$this->response .= $result->get_error_message();
					return;
				}
			}

			// Get an authorization URL which will redirect back to our page.
			$redirect = admin_url( 'admin.php?page=connection-test-admin-page' );
			$auth_url = $manager->get_authorization_url( null, $redirect );

			// Payments flow allows redirect back to the site without showing plans.
			$auth_url = add_query_arg( [ 'from' => 'google-listings-and-ads' ], $auth_url );

			error_log( $auth_url );

			// Using wp_redirect intentionally because we're redirecting outside.
			wp_redirect( $auth_url ); // phpcs:ignore WordPress.Security.SafeRedirect
			exit;
		}

		if ( 'disconnect' === $_GET['action'] && check_admin_referer( 'disconnect' ) ) {
			$manager->remove_connection();

			$redirect = admin_url( 'admin.php?page=connection-test-admin-page' );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( 'wcs-test' === $_GET['action'] && check_admin_referer( 'wcs-test' ) ) {
			$url            = WOOCOMMERCE_CONNECT_SERVER_URL;
			$this->response = 'GET ' . $url . "\n";

			$response = wp_remote_get( $url );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-auth-test' === $_GET['action'] && check_admin_referer( 'wcs-auth-test' ) ) {
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'connection/test';
			$args = [
				'headers' => [ 'Authorization' => $this->get_auth_header() ],
			];

			$this->response = 'GET ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-google-manager' === $_GET['action'] && check_admin_referer( 'wcs-google-manager' ) ) {
			if ( empty( $_GET['manager_id'] ) ) {
				$this->response .= 'Manager ID must be set';
				return;
			}

			$id   = absint( $_GET['manager_id'] );
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/connection/google-manager';
			$args = [
				'headers' => [ 'Authorization' => $this->get_auth_header() ],
				'body'    => [
					'returnUrl' => admin_url( 'admin.php?page=connection-test-admin-page' ),
					'managerId' => $id,
					'countries' => 'US,CA',
				],
			];

			$this->response = 'POST ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );

			$json = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $json && isset( $json['oauthUrl'] ) ) {
				wp_redirect( $json['oauthUrl'] ); // phpcs:ignore WordPress.Security.SafeRedirect
				exit;
			}
		}

		if ( 'wcs-google-ads-create' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-create' ) ) {
			try {
				/** @var Proxy $proxy */
				$proxy    = $this->container->get( Proxy::class );
				$account_id = $proxy->create_ads_account();

				$this->response .= 'Created account: ' . $account_id . "\n";
			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-google-ads-link' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-link' ) ) {

			if ( empty( $_GET['customer_id'] ) ) {
				$this->response .= 'Please enter a Customer ID';
				return;
			}

			try {
				/** @var Proxy $proxy */
				$proxy    = $this->container->get( Proxy::class );
				$account_id = $proxy->link_ads_account( absint( $_GET['customer_id'] ) );

				$this->response .= 'Linked account: ' . $account_id . "\n";
			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-google-ads-check' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-check' ) ) {
			/** @var Proxy $proxy */
			$proxy    = $this->container->get( Proxy::class );
			$status = $proxy->get_connected_ads_account();
			$this->response .= wp_json_encode( $status );
		}

		if ( 'wcs-google-ads-disconnect' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-disconnect' ) ) {
			/** @var Proxy $proxy */
			$proxy    = $this->container->get( Proxy::class );
			$status = $proxy->disconnect_ads_account();
			$this->response .= 'Disconnected ads account' . "\n";
		}

		if ( 'wcs-google-mc' === $_GET['action'] && check_admin_referer( 'wcs-google-mc' ) ) {
			/** @var Connection $connection */
			$connection   = $this->container->get( Connection::class );
			$redirect_url = $connection->connect( admin_url( 'admin.php?page=connection-test-admin-page' ) );

			if ( ! empty( $redirect_url ) ) {
				wp_redirect( $redirect_url ); // phpcs:ignore WordPress.Security.SafeRedirect
				exit;
			}
		}

		if ( 'wcs-google-mc-disconnect' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-disconnect' ) ) {
			/** @var Connection $connection */
			$connection      = $this->container->get( Connection::class );
			$response        = $connection->disconnect();
			$this->response .= $response;
		}

		if ( 'wcs-google-sv-token' === $_GET['action'] && check_admin_referer( 'wcs-google-sv-token' ) ) {
			// Full process using REST API
			$request         = new \WP_REST_Request( 'POST', '/wc/gla/site/verify' );
			$response        = rest_do_request( $request );
			$server          = rest_get_server();
			$data            = $server->response_to_data( $response, false );
			$json            = wp_json_encode( $data );
			$this->response .= $json;
		}

		if ( 'wcs-google-sv-check' === $_GET['action'] && check_admin_referer( 'wcs-google-sv-check' ) ) {
			// Check using REST API
			$request         = new \WP_REST_Request( 'GET', '/wc/gla/site/verify' );
			$response        = rest_do_request( $request );
			$server          = rest_get_server();
			$data            = $server->response_to_data( $response, false );
			$json            = wp_json_encode( $data );
			$this->response .= $json;
		}

		if ( 'wcs-google-sv-link' === $_GET['action'] && check_admin_referer( 'wcs-google-sv-link' ) ) {
			try {
				/** @var Proxy $proxy */
				$proxy = $this->container->get( Proxy::class );
				if ( $proxy->link_merchant_to_mca() ) {
					$this->response .= "Linked merchant to MCA\n";
				}
			} catch ( \Exception $e ) {
				$this->response .= $e->getMessage();
			}
		}

		if ( 'wcs-google-mc-setup' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-setup' ) ) {
			// Using REST API
			add_filter(
				'woocommerce_gla_site_url',
				function( $url ) {
					return $_GET['site_url'] ?? $url;
				}
			);

			$request = new \WP_REST_Request( 'POST', '/wc/gla/mc/accounts' );
			if ( is_numeric( $_GET['account_id'] ?? false ) ) {
				$request->set_body_params( [ 'id' => $_GET['account_id'] ] );
			}
			$response        = rest_do_request( $request );
			$server          = rest_get_server();
			$data            = $server->response_to_data( $response, false );
			$json            = wp_json_encode( $data );
			$this->response .= $response->get_status() . ' ' . $json;
		}

		if ( 'wcs-google-mc-claim-overwrite' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-claim-overwrite' ) ) {
			$request         = new \WP_REST_Request( 'POST', '/wc/gla/mc/accounts/claim-overwrite' );
			$response        = rest_do_request( $request );
			$server          = rest_get_server();
			$data            = $server->response_to_data( $response, false );
			$json            = wp_json_encode( $data );
			$this->response .= $response->get_status() . ' ' . $json;
		}

		if ( 'wcs-google-accounts-check' === $_GET['action'] && check_admin_referer( 'wcs-google-accounts-check' ) ) {
			$request         = new \WP_REST_Request( 'GET', '/wc/gla/mc/connection' );
			$response        = rest_do_request( $request );
			$server          = rest_get_server();
			$data            = $server->response_to_data( $response, false );
			$json            = wp_json_encode( $data );
			$this->response .= $response->get_status() . ' ' . $json;
		}

		if ( 'wcs-google-accounts-delete' === $_GET['action'] && check_admin_referer( 'wcs-google-accounts-delete' ) ) {
			$request         = new \WP_REST_Request( 'DELETE', '/wc/gla/mc/connection' );
			$response        = rest_do_request( $request );
			$server          = rest_get_server();
			$data            = $server->response_to_data( $response, false );
			$json            = wp_json_encode( $data );
			$this->response .= $response->get_status() . ' ' . $json;
		}

		if ( 'wcs-google-accounts-claim' === $_GET['action'] && check_admin_referer( 'wcs-google-accounts-claim' ) ) {
			// Using REST API
			add_filter(
				'woocommerce_gla_site_url',
				function ( $url ) {
					return $_GET['site_url'] ?? $url;
				}
			);

			try {
				$this->container->get( Merchant::class )->claimwebsite();
				$this->response .= 'Website claimed';
			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-google-mc-status' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-status' ) ) {
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/connection/google-mc';
			$args = [
				'headers' => [ 'Authorization' => $this->get_auth_header() ],
				'method'  => 'GET',
			];

			$this->response = 'GET ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-google-mc-id' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-id' ) ) {
			try {
				$this->response = 'Proxied request > get merchant ID' . "\n";

				/** @var Proxy $proxy */
				$proxy = $this->container->get( Proxy::class );
				foreach ( $proxy->get_merchant_ids() as $account ) {
					$this->response     .= sprintf(
						"Merchant ID: %s%s\n",
						$account['id'],
						$account['subaccount'] ? ' (IS a subaccount)' : ''
					);
					$_GET['merchant_id'] = $account['id'];
				}
			} catch ( \Exception $e ) {
				$this->response .= $e->getMessage();
			}
		}

		if ( 'wcs-google-mc-proxy' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-proxy' ) ) {
			/** @var Merchant $merchant */
			$merchant = $this->container->get( Merchant::class );

			if ( empty( $merchant->get_id() ) ) {
				$this->response .= 'Please enter a Merchant ID';
				return;
			}

			$this->response = "Proxied request > get products for merchant {$merchant->get_id()}\n";

			$products = $merchant->get_products();
			if ( empty( $products ) ) {
				$this->response .= 'No products found';
			}

			foreach ( $products as $product ) {
				$this->response .= "{$product->getId()} {$product->getTitle()}\n";
			}
		}

		if ( 'wcs-ads-customers-lib' === $_GET['action'] && check_admin_referer( 'wcs-ads-customers-lib' ) ) {
			try {
				/** @var Proxy $proxy */
				$proxy    = $this->container->get( Proxy::class );
				$accounts = $proxy->get_ads_account_ids();

				$this->response .= 'Total accounts: ' . count( $accounts ) . "\n";
				foreach ( $accounts as $id ) {
					$this->response     .= sprintf( "Ads ID: %d\n", $id );
					$_GET['customer_id'] = $id;
				}
			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-ads-campaign-lib' === $_GET['action'] && check_admin_referer( 'wcs-ads-campaign-lib' ) ) {
			try {
				/** @var Ads $ads */
				$ads = $this->container->get( Ads::class );

				$this->response = "Proxied request > get ad campaigns {$ads->get_id()}\n";

				$campaigns = $ads->get_campaigns();
				if ( empty( $campaigns ) ) {
					$this->response .= 'No campaigns found';
				} else {
					$this->response .= 'Total campaigns: ' . count( $campaigns ) . "\n";
					foreach ( $campaigns as $campaign ) {
						$this->response .= print_r( $campaign, true ) . "\n";
					}
				}
			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-accept-tos' === $_GET['action'] && check_admin_referer( 'wcs-accept-tos' ) ) {
			/** @var Proxy $proxy */
			$proxy  = $this->container->get( Proxy::class );
			$result = $proxy->mark_tos_accepted( 'google-mc', 'john.doe@example.com' );

			$this->response .= sprintf(
				'Attempting to accept Tos. Successful? %s<br>Response body: %s',
				$result->accepted() ? 'Yes' : 'No',
				$result->message()
			);
		}

		if ( 'wcs-check-tos' === $_GET['action'] && check_admin_referer( 'wcs-check-tos' ) ) {
			/** @var Proxy $proxy */
			$proxy    = $this->container->get( Proxy::class );
			$accepted = $proxy->check_tos_accepted( 'google-mc' );

			$this->response .= sprintf(
				'Tos Accepted? %s<br>Response body: %s',
				$accepted->accepted() ? 'Yes' : 'No',
				$accepted->message()
			);
		}

		if ( 'wcs-sync-product' === $_GET['action'] && check_admin_referer( 'wcs-sync-product' ) ) {

			if ( empty( $_GET['product_id'] ) ) {
				$this->response .= 'Please enter a Product ID';
				return;
			}

			$id      = absint( $_GET['product_id'] );
			$product = wc_get_product( $id );

			/** @var ProductSyncer $product_syncer */
			$product_syncer = $this->container->get( ProductSyncer::class );

			try {
				$result = $product_syncer->update( [ $product ] );

				$this->response .= sprintf( '%s products successfully submitted to Google.', count( $result->get_products() ) ) . "\n";
				if ( ! empty( $result->get_errors() ) ) {
					$this->response .= sprintf( 'There were %s errors:', count( $result->get_errors() ) ) . "\n";
					foreach ( $result->get_errors() as  $invalid_product ) {
						$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
					}
				}
			} catch ( ProductSyncerException $exception ) {
				$this->response = 'Error submitting product to Google: ' . $exception->getMessage();
			}
		}

		if ( 'wcs-sync-all-products' === $_GET['action'] && check_admin_referer( 'wcs-sync-all-products' ) ) {
			/** @var ProductSyncer $product_syncer */
			$product_syncer = $this->container->get( ProductSyncer::class );

			try {
				$products = wc_get_products(
					[
						'limit' => -1,
					]
				);

				$result = $product_syncer->update( $products );

				$this->response .= sprintf( '%s products successfully submitted to Google.', count( $result->get_products() ) ) . "\n";
				if ( ! empty( $result->get_errors() ) ) {
					$this->response .= sprintf( 'There were %s errors:', count( $result->get_errors() ) ) . "\n";
					foreach ( $result->get_errors() as  $invalid_product ) {
						$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
					}
				}
			} catch ( ProductSyncerException $exception ) {
				$this->response = 'Error submitting products to Google: ' . $exception->getMessage();
			}
		}

		if ( 'wcs-delete-synced-products' === $_GET['action'] && check_admin_referer( 'wcs-delete-synced-products' ) ) {
			/** @var ProductSyncer $product_syncer */
			$product_syncer = $this->container->get( ProductSyncer::class );

			try {
				$products = wc_get_products(
					[
						'limit' => -1,
					]
				);

				$result = $product_syncer->delete( $products );

				$this->response .= sprintf( '%s synced products deleted from Google.', count( $result->get_products() ) ) . "\n";
				if ( ! empty( $result->get_errors() ) ) {
					$this->response .= sprintf( 'There were %s errors:', count( $result->get_errors() ) ) . "\n";
					foreach ( $result->get_errors() as  $invalid_product ) {
						$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
					}
				}
			} catch ( ProductSyncerException $exception ) {
				$this->response = 'Error deleting products from Google: ' . $exception->getMessage();
			}
		}
	}

	/**
	 * Retrieve an authorization header containing a Jetpack token.
	 *
	 * @return string Authorization header.
	 */
	private function get_auth_header(): string {
		/** @var Manager $manager */
		$manager = $this->container->get( Manager::class );
		$token   = $manager->get_access_token();

		[ $token_key, $token_secret ] = explode( '.', $token->secret );

		$token_key = sprintf( '%s:%d:%d', $token_key, defined( 'JETPACK__API_VERSION' ) ? JETPACK__API_VERSION : 1, $token->external_user_id );
		$time_diff = (int) Jetpack_Options::get_option( 'time_diff' );
		$timestamp = time() + $time_diff;
		$nonce     = wp_generate_password( 10, false );

		$normalized_request_string = join( "\n", [ $token_key, $timestamp, $nonce ] ) . "\n";

		$signature = base64_encode( hash_hmac( 'sha1', $normalized_request_string, $token_secret, true ) );

		$auth = [
			'token'     => $token_key,
			'timestamp' => $timestamp,
			'nonce'     => $nonce,
			'signature' => $signature,
		];

		$header_pieces = [];
		foreach ( $auth as $key => $value ) {
			$header_pieces[] = sprintf( '%s="%s"', $key, $value );
		}

		return 'X_JP_Auth ' . join( ' ', $header_pieces );
	}
}
