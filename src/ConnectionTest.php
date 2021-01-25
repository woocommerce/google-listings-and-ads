<?php
// phpcs:ignoreFile

/**
 * Main plugin class.
 *
 * @package connection-test
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
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
			define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'http://localhost:5000' );
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

			<?php if ( $blog_token ) { ?>
				<p>Site is connected. ID: <?php echo Jetpack_Options::get_option( 'id' ); ?></p>
				<!--<pre><?php var_dump( $blog_token ); ?></pre>-->
			<?php } ?>

			<?php if ( $user_token ) { ?>
				<p>Connected as an authenticated user. ID: <?php echo $user_data['ID']; ?></p>
				<!--<pre><?php var_dump( $user_token ); ?></pre>-->
			<?php } ?>

			<?php if ( ! $blog_token || ! $user_token ) { ?>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'connect' ), $url ), 'connect' ) ); ?>">Connect to Jetpack</a></p>
			<?php } ?>

			<?php if ( $blog_token && $user_token ) { ?>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'disconnect' ), $url ), 'disconnect' ) ); ?>">Disconnect Jetpack</a></p>
			<?php } ?>

			<p>WCS Server: <?php echo defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_SERVER_URL : 'http://localhost:5000'; ?></p>

			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-test' ), $url ), 'wcs-test' ) ); ?>">Test WCS API</a>
				<?php if ( $blog_token && $user_token ) { ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-auth-test' ), $url ), 'wcs-auth-test' ) ); ?>">Test WCS API with an authenticated request</a>
				<?php } ?>
			</p>

			<?php if ( $blog_token && $user_token ) { ?>
				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<?php wp_nonce_field( 'wcs-google-manager' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-google-manager" type="hidden" />
						<label>
							Manager ID <input name="manager_id" type="text" value="<?php echo ! empty( $_GET['manager_id'] ) ? intval( $_GET['manager_id'] ) : ''; ?>" />
						</label>
						<button class="button">Connect Google Manager Account</button>
					</form>
				</div>

				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-ads-create' ), $url ), 'wcs-google-ads-create' ) ); ?>">Create Google Ads customer</a>
						<?php wp_nonce_field( 'wcs-google-ads-link' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-google-ads-link" type="hidden" />
						<label>
							Customer ID <input name="customer_id" type="text" value="<?php echo ! empty( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : ''; ?>" />
						</label>
						<button class="button">Link Google Ads customer to a Merchant Account</button>
					</form>
				</div>

				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-mc' ), $url ), 'wcs-google-mc' ) ); ?>">Connect Google Account</a>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-mc-disconnect' ), $url ), 'wcs-google-mc-disconnect' ) ); ?>">Disconnect Google Account</a>
				</p>

				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-google-mc-id' ), $url ), 'wcs-google-mc-id' ) ); ?>">Get Merchant Center ID</a>
						<?php wp_nonce_field( 'wcs-google-mc-proxy' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-google-mc-proxy" type="hidden" />
						<label>
							Merchant ID <input name="merchant_id" type="text" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
						</label>
						<button class="button">Send proxied request to Google Merchant Center</button>
					</form>
				</div>

				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-ads-customers' ), $url ), 'wcs-ads-customers' ) ); ?>">Get Customers from Google Ads</a>
						<?php wp_nonce_field( 'wcs-ads-campaign' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-ads-campaign" type="hidden" />
						<label>
							Customer ID <input name="customer_id" type="text" value="<?php echo ! empty( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : ''; ?>" />
						</label>
						<button class="button">Get Campaigns from Google Ads</button>
					</form>
				</div>

				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-ads-customers-lib' ), $url ), 'wcs-ads-customers-lib' ) ); ?>">Get Customers from Google Ads (using library)</a>
						<?php wp_nonce_field( 'wcs-ads-campaign-lib' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-ads-campaign-lib" type="hidden" />
						<label>
							Customer ID <input name="customer_id" type="text" value="<?php echo ! empty( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : ''; ?>" />
						</label>
						<button class="button">Get Campaigns from Google Ads (using library)</button>
					</form>
				</div>

				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-accept-tos' ), $url ), 'wcs-accept-tos' ) ); ?>">Accept ToS for Google</a>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wcs-check-tos' ), $url ), 'wcs-check-tos' ) ); ?>">Get latest ToS for Google</a>
				</p>

				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<?php wp_nonce_field( 'wcs-sync-product' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-sync-product" type="hidden" />
						<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
						<label>
							Product ID <input name="product_id" type="text" value="<?php echo ! empty( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : ''; ?>" />
						</label>
                        <input id="async-sync-product" name="async" value=1 type="checkbox" />
                        <label for="async-sync-product">Async?</label>
						<button class="button">Sync Product with Google Merchant Center</button>
					</form>
				</div>
				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<?php wp_nonce_field( 'wcs-sync-all-products' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-sync-all-products" type="hidden" />
                        <input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
                        <input id="async-sync-all-products" name="async" value=1 type="checkbox" <?php echo ! empty( $_GET['async'] ) ? 'checked' : ''; ?> />
                        <label for="async-sync-all-products">Async?</label>
						<button class="button">Sync All Products with Google Merchant Center</button>
					</form>
				</div>
				<div>
					<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
						<?php wp_nonce_field( 'wcs-delete-synced-products' ); ?>
						<input name="page" value="connection-test-admin-page" type="hidden" />
						<input name="action" value="wcs-delete-synced-products" type="hidden" />
						<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
                        <input id="async-delete-synced-products" name="async" value=1 type="checkbox" <?php echo ! empty( $_GET['async'] ) ? 'checked' : ''; ?> />
                        <label for="async-delete-synced-products">Async?</label>
						<button class="button">Delete All Synced Products from Google Merchant Center</button>
					</form>
				</div>
			<?php } ?>

			<?php if ( ! empty( $this->response ) ) { ?>
				<pre><?php echo wp_kses_post( $this->response ); ?></pre>
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
			$auth_url = add_query_arg( array( 'from' => 'woocommerce-payments' ), $auth_url );

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
			$id   = ! empty( $_GET['manager_id'] ) ? absint( $_GET['manager_id'] ) : 1;
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
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/manager/US/create-customer';
			$args = [
				'headers' => [
					'Authorization' => $this->get_auth_header(),
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'descriptive_name' => 'Connection test account at ' . date( 'Y-m-d h:i:s' ),
						'currency_code'    => 'USD',
						'time_zone'        => 'America/New_York',
					]
				),
			];

			$this->response = 'POST ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-google-ads-link' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-link' ) ) {
			$id   = ! empty( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : '12345';
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/manager/link-customer';
			$args = [
				'headers' => [
					'Authorization' => $this->get_auth_header(),
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'client_customer' => $id,
					]
				),
			];

			$this->response = 'POST ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-google-mc' === $_GET['action'] && check_admin_referer( 'wcs-google-mc' ) ) {
			/** @var Connection $connection */
			$connection = $this->container->get( Connection::class );
			$redirect_url = $connection->connect( admin_url( 'admin.php?page=connection-test-admin-page' ) );

			if ( ! empty( $redirect_url ) ) {
				wp_redirect( $redirect_url ); // phpcs:ignore WordPress.Security.SafeRedirect
				exit;
			}
		}

		if ( 'wcs-google-mc-disconnect' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-disconnect' ) ) {
			/** @var Connection $connection */
			$connection = $this->container->get( Connection::class );
			$response = $connection->disconnect();
			$this->response .= $response;
		}

		if ( 'wcs-google-mc-id' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-id' ) ) {
			$this->response = 'Proxied request > get merchant ID' . "\n";

			/** @var Proxy $proxy */
			$proxy = $this->container->get( Proxy::class );
			foreach ( $proxy->get_merchant_ids() as $id ) {
				$this->response .= sprintf( "Merchant ID: %s\n", $id );
				$_GET['merchant_id'] = $id;
			}
		}

		if ( 'wcs-google-mc-proxy' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-proxy' ) ) {
			/** @var Merchant $merchant */
			$merchant = $this->container->get( Merchant::class );

			$this->response = "Proxied request > get products for merchant {$merchant->get_id()}\n";

			$products = $merchant->get_products();
			if ( empty( $products ) ){
				$this->response .= 'No products found';
			}

			foreach ( $products as $product ) {
				$this->response .= "{$product->getId()} {$product->getTitle()}\n";
			}
		}

		if ( 'wcs-ads-customers' === $_GET['action'] && check_admin_referer( 'wcs-ads-customers' ) ) {
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/google-ads/v6/customers:listAccessibleCustomers';
			$args = [
				'headers' => [ 'Authorization' => $this->get_auth_header() ],
			];

			$this->response = 'GET ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$json = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $json && is_array( $json['resourceNames' ] ) ) {
				foreach( $json['resourceNames'] as $customer ) {
					$_GET['customer_id'] = absint( str_replace( 'customers/', '', $customer ) );
				}
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-ads-campaign' === $_GET['action'] && check_admin_referer( 'wcs-ads-campaign' ) ) {
			$id   = ! empty( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : '12345';
			$url  = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/google-ads/v6/customers/' . $id . '/googleAds:search';
			$args = [
				'headers' => [
					'Authorization' => $this->get_auth_header(),
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'pageSize' => 10000,
						'query'    => 'SELECT campaign.id, campaign.name FROM campaign ORDER BY campaign.id',
					]
				),
			];

			$this->response = 'POST ' . $url . "\n" . var_export( $args, true ) . "\n";

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'wcs-ads-customers-lib' === $_GET['action'] && check_admin_referer( 'wcs-ads-customers-lib' ) ) {
			try {
				$googleAdsClient       = $this->get_ads_client();
				$customerServiceClient = $googleAdsClient->getCustomerServiceClient();

				$args = [
					'headers' => [ 'Authorization' => $this->get_auth_header() ],
				];

				// Issues a request for listing all accessible customers.
				$accessibleCustomers = $customerServiceClient->listAccessibleCustomers( $args );
				$this->response .= 'Total results: ' . count( $accessibleCustomers->getResourceNames() ) . PHP_EOL;

				// Iterates over all accessible customers' resource names and prints them.
				foreach ( $accessibleCustomers->getResourceNames() as $resourceName ) {
					$this->response     .= sprintf( "Customer resource name: '%s'%s", $resourceName, PHP_EOL );
					$_GET['customer_id'] = absint( str_replace( 'customers/', '', $resourceName ) );
				}

			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-ads-campaign-lib' === $_GET['action'] && check_admin_referer( 'wcs-ads-campaign-lib' ) ) {
			try {
				$id  = ! empty( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : '12345';

				$googleAdsClient        = $this->get_ads_client();
				$googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

				// Creates a query that retrieves all campaigns.
				$query = 'SELECT campaign.id, campaign.name FROM campaign ORDER BY campaign.id';

				$args = [
					'headers' => [ 'Authorization' => $this->get_auth_header() ],
				];

				// Issues a search request.
				$response = $googleAdsServiceClient->search( $id, $query, $args );

				// Output details for each campaign.
				foreach ( $response->iterateAllElements() as $googleAdsRow ) {
					$this->response .= sprintf(
						"Campaign with ID %d and name '%s' was found.%s",
						$googleAdsRow->getCampaign()->getId(),
						$googleAdsRow->getCampaign()->getName(),
						PHP_EOL
					);
				}

			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-accept-tos' === $_GET['action'] && check_admin_referer( 'wcs-accept-tos' ) ) {
			/** @var Proxy $proxy */
			$proxy    = $this->container->get( Proxy::class );
			$result = $proxy->mark_tos_accepted( 'john.doe@example.com' );

			$this->response .= sprintf(
				"Attempting to accept Tos. Successful? %s<br>Response body: %s",
				$result->accepted() ? 'Yes' : 'No',
				$result->message()
			);
		}

		if ( 'wcs-check-tos' === $_GET['action'] && check_admin_referer( 'wcs-check-tos' ) ) {
			/** @var Proxy $proxy */
			$proxy    = $this->container->get( Proxy::class );
			$accepted = $proxy->check_tos_accepted();

			$this->response .= sprintf(
				"Tos Accepted? %s<br>Response body: %s",
				$accepted->accepted() ? 'Yes' : 'No',
				$accepted->message()
			);
		}

		if ( 'wcs-sync-product' === $_GET['action'] && check_admin_referer( 'wcs-sync-product' ) ) {
			$id      = ! empty( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : '12345';
			$product = wc_get_product( $id );

			if ( $product instanceof \WC_Product ) {
				if ( ! $_GET['async'] ) {
					/** @var ProductSyncer $product_syncer */
					$product_syncer = $this->container->get( ProductSyncer::class );

					try {
						$result = $product_syncer->update( [ $product ] );

						$this->response .= sprintf( '%s products successfully submitted to Google.', count( $result->get_products() ) ) . "\n";
						if ( ! empty( $result->get_errors() ) ) {
							$this->response .= sprintf( 'There were %s errors:', count( $result->get_errors() ) ) . "\n";
							foreach ( $result->get_errors() as $invalid_product ) {
								$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
							}
						}
					} catch ( ProductSyncerException $exception ) {
						$this->response = 'Error submitting product to Google: ' . $exception->getMessage();
					}
				} else {
					// schedule a job
					/** @var UpdateProducts $update_job */
					$update_job = $this->container->get( UpdateProducts::class );
					$update_job->start( [ $product->get_id() ] );
					$this->response = 'Successfully scheduled a job to sync the product ' . $product->get_id();
				}
			} else {
				$this->response = 'Invalid product ID provided: ' . $id;
			}
		}

		if ( 'wcs-sync-all-products' === $_GET['action'] && check_admin_referer( 'wcs-sync-all-products' ) ) {
			if ( ! $_GET['async'] ) {
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
						foreach ($result->get_errors() as  $invalid_product) {
							$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
						}
					}
				} catch ( ProductSyncerException $exception ) {
					$this->response = 'Error submitting products to Google: ' . $exception->getMessage();
				}
			} else {
			    // schedule a job
				/** @var UpdateAllProducts $update_job */
				$update_job = $this->container->get( UpdateAllProducts::class );
				$update_job->start();
				$this->response = 'Successfully scheduled a job to sync all products!';
			}
		}

		if ( 'wcs-delete-synced-products' === $_GET['action'] && check_admin_referer( 'wcs-delete-synced-products' ) ) {
			if ( ! $_GET['async'] ) {
				/** @var ProductSyncer $product_syncer */
				$product_syncer = $this->container->get( ProductSyncer::class );

				try {
					$products = wc_get_products(
						[
							'limit' => - 1,
						]
					);

					$result = $product_syncer->delete( $products );

					$this->response .= sprintf( '%s synced products deleted from Google.', count( $result->get_products() ) ) . "\n";
					if ( ! empty( $result->get_errors() ) ) {
						$this->response .= sprintf( 'There were %s errors:', count( $result->get_errors() ) ) . "\n";
						foreach ( $result->get_errors() as $invalid_product ) {
							$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
						}
					}
				} catch ( ProductSyncerException $exception ) {
					$this->response = 'Error deleting products from Google: ' . $exception->getMessage();
				}
			} else {
				// schedule a job
				/** @var DeleteAllProducts $delete_job */
				$delete_job = $this->container->get( DeleteAllProducts::class );
				$delete_job->start();
				$this->response = 'Successfully scheduled a job to delete all synced products!';
			}
		}
	}

	/**
	 * Get a GoogleAdsClient.
	 *
	 * @return GoogleAdsClient
	 */
	private function get_ads_client(): \Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient {
		$url = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . 'google/google-ads';
		$url = preg_replace( '/^https?:\/\//', '', $url );

		$oAuth2Credential = ( new \Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder() )
			->withClientId( 'clientid' )
			->withClientSecret( 'clientsecret' )
			->withRefreshToken( 'refreshtoken' )
			->build();

		return ( new \Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder() )
			->withDeveloperToken( 'developertoken' )
			->withOAuth2Credential( $oAuth2Credential )
			->withEndpoint( $url )
			->withTransport( 'rest' )
			->build();
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
