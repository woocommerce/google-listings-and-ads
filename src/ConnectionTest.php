<?php
// phpcs:ignoreFile

/**
 * Main plugin class.
 *
 * @package connection-test
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\Jetpack\Connection\Client;
use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\GTINMigrationUtilities;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\MigrateGTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;
use Jetpack_Options;
use WP_REST_Request as Request;

/**
 * Main class for Connection Test.
 */
class ConnectionTest implements ContainerAwareInterface, Service, Registerable {

	use ContainerAwareTrait;
	use GTINMigrationUtilities;
	use PluginHelper;

	/**
	 * Register a service.
	 */
	public function register(): void {
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
	 * Store response from the integration status API request.
	 *
	 * @var string
	 */
	protected $integration_status_response = [];

	/**
	 * Add menu entries
	 */
	protected function register_admin_menu() {
		if ( apply_filters( 'woocommerce_gla_enable_connection_test', false ) ) {
			add_menu_page(
				'Connection Test',
				'Connection Test',
				'manage_woocommerce',
				'connection-test-admin-page',
				function () {
					$this->render_admin_page();
				}
			);
		} else {
			add_submenu_page(
				'',
				'Connection Test',
				'Connection Test',
				'manage_woocommerce',
				'connection-test-admin-page',
				function () {
					$this->render_admin_page();
				}
			);
		}
	}

	/**
	 * Render the admin page.
	 */
	protected function render_admin_page() {
		/** @var OptionsInterface $options */
		$options = $this->container->get( OptionsInterface::class );
		/** @var Manager $manager */
		$manager    = $this->container->get( Manager::class );
		$blog_token = $manager->get_tokens()->get_access_token();
		$user_token = $manager->get_tokens()->get_access_token( get_current_user_id() );
		$user_data  = $manager->get_connected_user_data( get_current_user_id() );
		$url        = admin_url( 'admin.php?page=connection-test-admin-page' );

		if ( ! empty( $_GET['google-mc'] ) && 'connected' === $_GET['google-mc'] ) {
			$this->response .= 'Google Account connected successfully.';
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

			<p>Google for WooCommerce connection testing page used for debugging purposes. Debug responses are output at the top of the page.</p>

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
							<code><?php echo $this->container->get( 'connect_server_root' ); ?></code>
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

				<?php if ( $blog_token ) { ?>
				<tr>
					<th>Test Authenticated WCS Request:</th>
					<td>
						<p>
							<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wcs-auth-test' ], $url ), 'wcs-auth-test' ) ); ?>">Test Authenticated Request</a>
						</p>
					</td>
				</tr>
				<?php } ?>

			</table>

			<hr />

			<h2 class="title">WordPress.com</h2>

			<table class="form-table" role="presentation">

				<?php if ( $blog_token ) { ?>
					<tr>
						<th><label>Site ID:</label></th>
						<td>
							<p>
								<code><?php echo Jetpack_Options::get_option( 'id' ); ?></code>
							</p>
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
						</td>
					</tr>
				<?php } elseif ( $blog_token ) { ?>
					<tr>
						<th><label>User:</label></th>
						<td><p>Connected with another user account</p></td>
					</tr>
				<?php } ?>

				<tr>
					<th>Connection Status:</th>
					<td>
						<?php if ( ! $blog_token ) { ?>
							<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'connect' ], $url ), 'connect' ) ); ?>">Connect to WordPress.com</a></p>
						<?php } else { ?>
							<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wp-status' ], $url ), 'wp-status' ) ); ?>">WordPress.com Connection Status</a></p>
						<?php } ?>
					</td>
				</tr>

				<?php if ( $blog_token && ! $options->get( OptionsInterface::JETPACK_CONNECTED ) ) { ?>
				<tr>
					<th>Reconnect WordPress.com:</th>
					<td>
						<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'connect' ], $url ), 'connect' ) ); ?>">Reconnect to WordPress.com</a></p>
					</td>
				</tr>
				<?php } ?>

			</table>

			<hr />

			<?php if ( $blog_token ) { ?>

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
										Site URL <input name="site_url" type="text" style="width:14em; font-size:.9em" value="<?php echo esc_url( ! empty( $_GET['site_url'] ) ? $_GET['site_url'] : $this->get_site_url() ); ?>" />
									</label>
									<label title="To simulate linking with an external site">
										MC ID <input name="account_id" type="text" style="width:8em; font-size:.9em" value="<?php echo ! empty( $_GET['account_id'] ) ? intval( $_GET['account_id'] ) : ''; ?>" />
									</label>
									<button class="button">MC Account Setup (I & II)</button>
								</p>

								<?php
									$mc_account_state = $this->container->get( MerchantAccountState::class )->get( false );
									$merchant_id = $this->container->get( OptionsInterface::class )->get_merchant_id();
									if ( ! empty( $mc_account_state ) ) :
								?>
									<p class="description" style="font-style: italic">
										( Merchant Center account status -- ID: <?php
										echo $merchant_id; ?> ||
										<?php foreach ( $mc_account_state as $name => $step ) : ?>
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
							<th><a id="overwrite"></a>Claim Overwrite:</th>
							<td>
								<p>
									<a class="button" href="<?php
									echo esc_url( wp_nonce_url(
										add_query_arg(
											[
												'action' => 'wcs-google-mc-claim-overwrite',
												'account_id' => ($_GET['account_id'] ?? false) ?: $merchant_id,
											],
											$url
										),
										'wcs-google-mc-claim-overwrite' )
									); ?>" <?php echo ( ($_GET['account_id'] ?? false) || $merchant_id ) ? '' : 'disabled="disabled" title="Missing account ID"' ?>>Claim Overwrite</a>
								</p>
							</td>
						</tr>
						<tr>
							<th><a id="switch"></a>Switch URL:</th>
							<td>
								<p>
									<a class="button" href="<?php
									echo esc_url( wp_nonce_url(
											add_query_arg(
												[
													'action' => 'wcs-google-mc-switch-url',
													'site_url' => $_GET['site_url'] ?? $this->get_site_url(),
													'account_id' => ($_GET['account_id'] ?? false) ?: $merchant_id,
												]
											),
											'wcs-google-mc-switch-url'
										) ); ?>" <?php echo ( ($_GET['account_id'] ?? false) || $merchant_id ) ? '' : 'disabled="disabled" title="Missing account ID"' ?>>Switch URL</a>
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
					<tr>
						<th>Clear Status Cache:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'clear-mc-status-cache' ], $url ), 'clear-mc-status-cache' ) ); ?>">Clear</a>
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
							<th>Ads Account Setup:</th>
							<td>
								<p>
									<label>
										Ads ID <input name="ads_id" type="text" value="" />
									</label>
									<button class="button">Setup an existing account or create a new one</button>
								</p>
								<?php
									$ads_account_state = $this->container->get( AdsAccountState::class )->get( false );
									if ( ! empty( $ads_account_state ) ) :
								?>
									<p class="description" style="font-style: italic">
										( Ads account status -- ID: <?php echo $this->container->get( OptionsInterface::class )->get( OptionsInterface::ADS_ID ); ?> ||
										<?php foreach ( $ads_account_state as $name => $step ) : ?>
											<?php echo $name . ':' . $step['status']; ?>
										<?php endforeach; ?>
										)
									</p>
									<?php
										$conversion_action = $options->get( OptionsInterface::ADS_CONVERSION_ACTION );
										if ( ! empty( $conversion_action ) && is_array( $conversion_action ) ) :
									?>
									<p class="description" style="font-style: italic">
										( Conversion Action --
										<?php foreach ( $conversion_action as $name => $value ) : ?>
											<?php echo "{$name} : \"{$value}\""; ?>
										<?php endforeach; ?>
										)
									</p>
									<?php endif; ?>
									<br/>
								<?php endif; ?>
								<p class="description">
									Begins/continues a multistep account-setup sequence.
									If no Ads ID is provided, then a sub-account will be created under our manager account.
									Adds <em>gla_ads_id</em> to site options.

									<h4>Create account steps:</h4>
									create account &gt;
									direct user to billing flow &gt;
									link to merchant account &gt;
									create conversion action

									<h4>Link account steps:</h4>
									link to manager account &gt;
									link to merchant account &gt;
									create conversion action
								</p>
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
					<?php wp_nonce_field( 'wcs-google-ads-setup' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-google-ads-setup" type="hidden" />
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
						<label>
							Product ID <input name="product_id" type="text" value="<?php echo ! empty( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : ''; ?>" />
						</label>
						<label for="async-sync-product">Async?</label>
						<input id="async-sync-product" name="async" value=1 type="checkbox" <?php echo ! empty( $_GET['async'] ) ? 'checked' : ''; ?> />
						<button class="button">Sync Product with Google Merchant Center</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-sync-product' ); ?>
					<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-sync-product" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Sync All Products:</th>
							<td>
								<p>
									<label for="async-sync-all-products">Async?</label>
									<input id="async-sync-all-products" name="async" value=1 type="checkbox" <?php echo ! empty( $_GET['async'] ) ? 'checked' : ''; ?> />
									<button class="button">Sync All Products with Google Merchant Center</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-sync-all-products' ); ?>
					<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-sync-all-products" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Delete All Synced Products:</th>
							<td>
								<p>
									<label for="async-delete-synced-products">Async?</label>
									<input id="async-delete-synced-products" name="async" value=1 type="checkbox" <?php echo ! empty( $_GET['async'] ) ? 'checked' : ''; ?> />
									<button class="button">Delete All Synced Products from Google Merchant Center
									</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-delete-synced-products' ); ?>
					<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-delete-synced-products" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Cleanup All Products:</th>
							<td>
								<p>
									<label for="async-cleanup-products">Async?</label>
									<input id="async-cleanup-products" name="async" value=1 type="checkbox" <?php echo ! empty( $_GET['async'] ) ? 'checked' : ''; ?> />
									<button class="button">Cleanup All Products
									</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'wcs-cleanup-products' ); ?>
					<input name="merchant_id" type="hidden" value="<?php echo ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : ''; ?>" />
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="wcs-cleanup-products" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th><label>GTIN Migration:</label></th>
							<td>
								<p>
									<code><?php echo $this->get_gtin_migration_status(); ?></code>
								</p>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'migrate-gtin' ], $url ), 'migrate-gtin' ) ); ?>">Start GTIN Migration</a>
								</p>
							</td>
						</tr>
					</table>
				</form>
			<?php } ?>

			<hr />

			<?php if ( $blog_token ) { ?>
				<?php
				  $options = $this->container->get( OptionsInterface::class );
				  $wp_api_status = $options->get( OptionsInterface::WPCOM_REST_API_STATUS );
				  $notification_service = new NotificationsService( $this->container->get( MerchantCenterService::class ), $this->container->get( AccountService::class ) );
				  $notification_service->set_options_object( $options );
				?>
				<h2 class="title">Partner API Pull Integration</h2>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th><label>Notification Service Enabled:</label></th>
							<td>
								<p>
									<code><?php echo $this->yes_or_no( $notification_service->is_enabled() ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>Notification Service Ready:</label></th>
							<td>
								<p>
									<code><?php echo $this->yes_or_no( $notification_service->is_ready() ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>Products API PULL Sync:</label></th>
							<td>
								<p>

									<code><?php echo $this->enabled_or_disabled( $notification_service->is_pull_enabled_for_datatype( NotificationsService::DATATYPE_PRODUCT ) ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>Coupons API PULL Sync:</label></th>
							<td>
								<p>

									<code><?php echo $this->enabled_or_disabled( $notification_service->is_pull_enabled_for_datatype( NotificationsService::DATATYPE_COUPON ) ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>Shipping API PULL Sync:</label></th>
							<td>
								<p>

									<code><?php echo $this->enabled_or_disabled( $notification_service->is_pull_enabled_for_datatype( NotificationsService::DATATYPE_SHIPPING ) ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>Settings API PULL Sync:</label></th>
							<td>
								<p>

									<code><?php echo $this->enabled_or_disabled( $notification_service->is_pull_enabled_for_datatype( NotificationsService::DATATYPE_SETTINGS ) ); ?></code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>WPCOM REST API Status:</label></th>
							<td>
								<p>
									<code><?php echo $wp_api_status ?? 'NOT SET'; ?></code>
									<?php if ( $wp_api_status === 'approved' ) { ?> <a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'disconnect-wp-api' ), $url ), 'disconnect-wp-api' ) ); ?>">Disconnect</a> <?php }  ?>
								</p>
							</td>
						</tr>
						<tr>
							<th>Send partner notification request to WPCOM:</th>
							<td>
								<p>
									<label>
										Product/Coupon ID <input name="item_id" type="text" value="<?php echo ! empty( $_GET['item_id'] ) ? intval( $_GET['item_id'] ) : ''; ?>" />
									</label>
									<br />
									<br />
									<label>
										Topic
										<select name="topic">
											<option value="product.create" <?php echo (! isset( $_GET['topic'] ) || $_GET['topic'] === 'product.create') ? "selected" : "" ?>>product.create</option>
											<option value="product.delete" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'product.delete' ? "selected" : ""?>>product.delete</option>
											<option value="product.update" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'product.update' ? "selected" : ""?>>product.update</option>
											<option value="coupon.create" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'coupon.create' ? "selected" : ""?>>coupon.create</option>
											<option value="coupon.delete" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'coupon.delete' ? "selected" : ""?>>coupon.delete</option>
											<option value="coupon.update" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'coupon.update' ? "selected" : ""?>>coupon.update</option>
											<option value="shipping.update" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'shipping.update' ? "selected" : ""?>>shipping.update</option>
											<option value="settings.update" <?php echo isset( $_GET['topic'] ) && $_GET['topic'] === 'settings.update' ? "selected" : ""?>>settings.update</option>
										</select>
									</label>
									<button class="button">Send Notification</button>
								</p>
							</td>
						</tr>
						<tr>
							<th><label>API Pull Integration Status:</label></th>
							<td>
								<p>
									<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'partner-integration-status' ], $url ), 'partner-integration-status' ) ); ?>">Get API Pull Integration Status</a>
								</p>
							</td>
						</tr>
						<?php if ( isset( $this->integration_status_response['site'] ) || isset( $this->integration_status_response['errors'] ) ) { ?>
							<tr>
								<th><label>Site:</label></th>
								<td>
									<p>
										<code><?php echo $this->integration_status_response['site'] ?? ''; ?></code>
									</p>
								</td>
							</tr>
							<tr>
								<th><label>Jetpack Connection Health:</label></th>
								<td>
									<p>
										<code><?php echo isset( $this->integration_status_response['is_healthy'] ) && $this->integration_status_response['is_healthy'] === true ? 'Healthy' : 'Unhealthy'; ?></code>
									</p>
								</td>
							</tr>
							<tr>
								<th><label>Last Jetpack Contact:</label></th>
								<td>
									<p>
										<code><?php echo isset( $this->integration_status_response['last_jetpack_contact'] ) ? date( 'Y-m-d H:i:s', $this->integration_status_response['last_jetpack_contact'] ) : '-'; ?></code>
									</p>
								</td>
							</tr>
							<tr>
								<th><label>WC REST API Health:</label></th>
								<td>
									<p>
										<code><?php echo isset( $this->integration_status_response['is_wc_rest_api_healthy'] ) && $this->integration_status_response['is_wc_rest_api_healthy'] === true ? 'Healthy' : 'Unhealthy'; ?></code>
									</p>
								</td>
							</tr>
							<tr>
								<th><label>Google token health:</label></th>
								<td>
									<p>
										<code><?php echo isset( $this->integration_status_response['is_partner_token_healthy'] ) && $this->integration_status_response['is_partner_token_healthy'] === true ? 'Connected' : 'Disconnected'; ?></code>
									</p>
								</td>
							</tr>
							<tr>
								<th><label>Errors:</label></th>
								<td>
									<p>
										<code><?php echo isset( $this->integration_status_response['errors'] ) ? wp_kses_post( wp_json_encode( $this->integration_status_response['errors'] ) ) ?? '' : '-'; ?></code>
									</p>
								</td>
							</tr>
						<?php } ?>
					</table>
					<?php wp_nonce_field( 'partner-notification' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="partner-notification" type="hidden" />
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
			// Register the site to WPCOM.
			if ( $manager->is_connected() ) {
				$result = $manager->reconnect();
			} else {
				$result = $manager->register();
			}

			if ( is_wp_error( $result ) ) {
				$this->response .= $result->get_error_message();
				return;
			}

			// Get an authorization URL which will redirect back to our page.
			$redirect = admin_url( 'admin.php?page=connection-test-admin-page' );
			$auth_url = $manager->get_authorization_url( null, $redirect );

			// Payments flow allows redirect back to the site without showing plans.
			$auth_url = add_query_arg( [ 'from' => 'google-listings-and-ads' ], $auth_url );

			// Using wp_redirect intentionally because we're redirecting outside.
			wp_redirect( $auth_url ); // phpcs:ignore WordPress.Security.SafeRedirect
			exit;
		}

		if ( 'disconnect' === $_GET['action'] && check_admin_referer( 'disconnect' ) ) {
			$manager->remove_connection();

			$plugin = $manager->get_plugin();

			if ( $plugin && ! $plugin->is_only() ) {
				$connected_plugins = $manager->get_connected_plugins();
				$this->response    = 'Cannot disconnect WordPress.com connection as there are other plugins using it: ';
				$this->response   .= implode( ', ', array_keys( $connected_plugins ) ) . "\n";
				$this->response   .= 'Please disconnect the connection using the Jetpack plugin.';
				return;
			} else {
				$redirect = admin_url( 'admin.php?page=connection-test-admin-page' );
				wp_safe_redirect( $redirect );
				exit;
			}
		}

		if ( 'wp-status' === $_GET['action'] && check_admin_referer( 'wp-status' ) ) {
			$request = new Request( 'GET', '/wc/gla/jetpack/connected' );
			$this->send_rest_request( $request );

			/** @var OptionsInterface $options */
			$options = $this->container->get( OptionsInterface::class );
			$this->response .= "\n\n" . 'Saved Connection option = ' . ( $options->get( OptionsInterface::JETPACK_CONNECTED ) ? 'connected' : 'disconnected' );

			$this->response .= "\n\n" . 'Connected plugins: ' . implode( ', ', array_column( $manager->get_connected_plugins(), 'name' ) ) . "\n";
		}

		if ( 'wcs-test' === $_GET['action'] && check_admin_referer( 'wcs-test' ) ) {
			$url            = $this->get_connect_server_url();
			$this->response = 'GET ' . $url . "\n";

			$response = wp_remote_get( $url );
			if ( is_wp_error( $response ) ) {
				$this->response .= $response->get_error_message();
				return;
			}

			$this->response .= wp_remote_retrieve_body( $response );
		}

		if ( 'partner-notification' === $_GET['action'] && check_admin_referer( 'partner-notification' ) ) {
			if ( ! isset( $_GET['topic'] ) ) {
				$this->response .= "\n Topic is required.";
				return;
			}

			$item  = $_GET['item_id'] ?? null;
			$topic = $_GET['topic'];
			$mc    = $this->container->get( MerchantCenterService::class );
			/** @var OptionsInterface $options */
			$options = $this->container->get( OptionsInterface::class );
			$service = new NotificationsService( $mc, $this->container->get( AccountService::class ) );
			$service->set_options_object( $options );

			if ( $service->notify( $topic, $item ) ) {
				$this->response .= "\n Notification success. Item: " . $item . " - Topic: " . $topic;
			} else {
				$this->response .= "\n Notification failed. Item: " . $item . " - Topic: " . $topic;
			}

			return;
		}

		if ( 'partner-integration-status' === $_GET['action'] && check_admin_referer( 'partner-integration-status' ) ) {

			$integration_status_args = [
				'method'  => 'GET',
				'timeout' => 30,
				'url'     => 'https://public-api.wordpress.com/wpcom/v2/sites/' . Jetpack_Options::get_option( 'id' ) . '/wc/partners/google/remote-site-status',
				'user_id' => get_current_user_id(),
			];

			$integration_remote_request_response = Client::remote_request( $integration_status_args, null );

			if ( is_wp_error( $integration_remote_request_response ) ) {
				$this->response .= $integration_remote_request_response->get_error_message();
			} else {
				$this->integration_status_response = json_decode( wp_remote_retrieve_body( $integration_remote_request_response ), true ) ?? [];

				// If the merchant isn't connected to the Google App, it's not necessary to display an error indicating that the partner token isn't associated.
				if ( ! $this->integration_status_response['is_partner_token_healthy'] && isset( $this->integration_status_response['errors'] ['rest_api_partner_token']['error_code'] ) && $this->integration_status_response['errors'] ['rest_api_partner_token']['error_code'] === 'wpcom_partner_token_not_associated' ) {
					unset( $this->integration_status_response['errors'] ['rest_api_partner_token'] );
				}

				if ( json_last_error() || ! isset( $this->integration_status_response['site'] ) ) {
					$this->response .= wp_remote_retrieve_body( $integration_remote_request_response );
				}
			}

		}

		if ( 'disconnect-wp-api' === $_GET['action'] && check_admin_referer( 'disconnect-wp-api' ) ) {
			$request = new Request( 'DELETE', '/wc/gla/rest-api/authorize' );
			$this->send_rest_request( $request );
		}

		if ( 'wcs-auth-test' === $_GET['action'] && check_admin_referer( 'wcs-auth-test' ) ) {
			$url  = trailingslashit( $this->get_connect_server_url() ) . 'connection/test';
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
			$url  = trailingslashit( $this->get_connect_server_url() ) . 'google/connection/google-manager';
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

		if ( 'wcs-google-ads-setup' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-setup' ) ) {
			$request = new Request( 'POST', '/wc/gla/ads/accounts' );
			if ( is_numeric( $_GET['ads_id'] ?? false ) ) {
				$request->set_body_params( [ 'id' => absint( $_GET['ads_id'] ) ] );
			}
			$this->send_rest_request( $request );
		}

		if ( 'wcs-google-ads-check' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-check' ) ) {
			$request = new Request( 'GET', '/wc/gla/ads/connection' );
			$this->send_rest_request( $request );
		}

		if ( 'wcs-google-ads-disconnect' === $_GET['action'] && check_admin_referer( 'wcs-google-ads-disconnect' ) ) {
			$request = new Request( 'DELETE', '/wc/gla/ads/connection' );
			$this->send_rest_request( $request );
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

		if ( 'wcs-google-sv-link' === $_GET['action'] && check_admin_referer( 'wcs-google-sv-link' ) ) {
			try {
				if ( $this->container->get( Middleware::class )->link_merchant_to_mca() ) {
					$this->response .= "Linked merchant to MCA\n";
				}
			} catch ( \Exception $e ) {
				$this->response .= $e->getMessage();
			}
		}

		if ( 'wcs-google-mc-setup' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-setup' ) ) {
			add_filter(
				'woocommerce_gla_site_url',
				function( $url ) {
					return isset( $_GET['site_url'] ) ? esc_url_raw( $_GET['site_url'] ) : $url;
				}
			);

			$request = new Request( 'POST', '/wc/gla/mc/accounts' );
			if ( is_numeric( $_GET['account_id'] ?? false ) ) {
				$request->set_body_params( [ 'id' => $_GET['account_id'] ] );
			}
			$this->send_rest_request( $request );
		}

		if ( 'wcs-google-mc-claim-overwrite' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-claim-overwrite' ) ) {
			$request = new Request( 'POST', '/wc/gla/mc/accounts/claim-overwrite' );
			if ( is_numeric( $_GET['account_id'] ?? false ) ) {
				$request->set_body_params( [ 'id' => $_GET['account_id'] ] );
			}
			$this->send_rest_request( $request );
		}

		if ( 'wcs-google-mc-switch-url' === $_GET['action'] && check_admin_referer( 'wcs-google-mc-switch-url' ) ) {
			$request = new Request( 'POST', '/wc/gla/mc/accounts/switch-url' );
			if ( is_numeric( $_GET['account_id'] ?? false ) ) {
				$request->set_body_params( [ 'id' => $_GET['account_id'] ] );
			}
			$this->send_rest_request( $request );
		}

		if ( 'clear-mc-status-cache' === $_GET['action'] && check_admin_referer( 'clear-mc-status-cache' ) ) {
			$this->container->get( MerchantStatuses::class )->clear_cache();
			$this->response .= 'Merchant Center statuses transient successfully deleted.';
		}

		if ( 'wcs-google-accounts-check' === $_GET['action'] && check_admin_referer( 'wcs-google-accounts-check' ) ) {
			$request = new Request( 'GET', '/wc/gla/mc/connection' );
			$this->send_rest_request( $request );
		}

		if ( 'wcs-google-accounts-delete' === $_GET['action'] && check_admin_referer( 'wcs-google-accounts-delete' ) ) {
			$request = new Request( 'DELETE', '/wc/gla/mc/connection' );
			$this->send_rest_request( $request );
		}

		if ( 'wcs-google-accounts-claim' === $_GET['action'] && check_admin_referer( 'wcs-google-accounts-claim' ) ) {
			add_filter(
				'woocommerce_gla_site_url',
				function ( $url ) {
					return isset( $_GET['site_url'] ) ? esc_url_raw( $_GET['site_url'] ) : $url;
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
			$url  = trailingslashit( $this->get_connect_server_url() ) . 'google/connection/google-mc';
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

				foreach ( $this->container->get( Middleware::class )->get_merchant_accounts() as $account ) {
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
			/** @var OptionsInterface $options */
			$options = $this->container->get( OptionsInterface::class );

			if ( empty( $options->get_merchant_id() ) ) {
				$this->response .= 'Please enter a Merchant ID';
				return;
			}

			$this->response = "Proxied request > get products for merchant {$options->get_merchant_id()}\n";

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
				$accounts = $this->container->get( Ads::class )->get_ads_accounts();

				$this->response .= 'Total accounts: ' . count( $accounts ) . "\n";
				foreach ( $accounts as $account ) {
					$this->response     .= sprintf( "%d : %s\n", $account['id'], $account['name'] );
					$_GET['customer_id'] = $account['id'];
				}
			} catch ( \Exception $e ) {
				$this->response .= 'Error: ' . $e->getMessage();
			}
		}

		if ( 'wcs-ads-campaign-lib' === $_GET['action'] && check_admin_referer( 'wcs-ads-campaign-lib' ) ) {
			try {
				/** @var AdsCampaign $ads_campaign */
				$ads_campaign = $this->container->get( AdsCampaign::class );
				/** @var OptionsInterface $options */
				$options = $this->container->get( OptionsInterface::class );

				$this->response = "Proxied request > get ad campaigns {$options->get_ads_id()}\n";

				$campaigns = $ads_campaign->get_campaigns();
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
			$result = $this->container->get( Middleware::class )->mark_tos_accepted( 'google-mc', 'john.doe@example.com' );

			$this->response .= sprintf(
				'Attempting to accept Tos. Successful? %s<br>Response body: %s',
				$this->yes_or_no( $result->accepted() ),
				$result->message()
			);
		}

		if ( 'wcs-check-tos' === $_GET['action'] && check_admin_referer( 'wcs-check-tos' ) ) {
			$accepted = $this->container->get( Middleware::class )->check_tos_accepted( 'google-mc' );

			$this->response .= sprintf(
				'Tos Accepted? %s<br>Response body: %s',
				$this->yes_or_no( $result->accepted() ),
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

			if ( $product instanceof \WC_Product ) {
				if ( empty( $_GET['async'] ) ) {
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
					$update_job = $this->container->get( JobRepository::class )->get( UpdateProducts::class );
					$update_job->schedule( [ [ $product->get_id() ] ] );
					$this->response = 'Successfully scheduled a job to sync the product ' . $product->get_id();
				}
			} else {
				$this->response = 'Invalid product ID provided: ' . $id;
			}
		}

		if ( 'wcs-sync-all-products' === $_GET['action'] && check_admin_referer( 'wcs-sync-all-products' ) ) {
			if ( empty( $_GET['async'] ) ) {
				/** @var ProductSyncer $product_syncer */
				$product_syncer = $this->container->get( ProductSyncer::class );
				/** @var ProductRepository $product_repository */
				$product_repository = $this->container->get( ProductRepository::class );

				try {
					$products = $product_repository->find_sync_ready_products()->get();

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
			} else {
				// schedule a job
				/** @var UpdateAllProducts $update_job */
				$update_job = $this->container->get( JobRepository::class )->get( UpdateAllProducts::class );
				$update_job->schedule();
				$this->response = 'Successfully scheduled a job to sync all products!';
			}
		}

		if ( 'wcs-delete-synced-products' === $_GET['action'] && check_admin_referer( 'wcs-delete-synced-products' ) ) {
			if ( empty( $_GET['async'] ) ) {
				/** @var ProductSyncer $product_syncer */
				$product_syncer = $this->container->get( ProductSyncer::class );
				/** @var ProductRepository $product_repository */
				$product_repository = $this->container->get( ProductRepository::class );

				try {
					$products = $product_repository->find_synced_products();

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
				$delete_job = $this->container->get( JobRepository::class )->get( DeleteAllProducts::class );
				$delete_job->schedule();
				$this->response = 'Successfully scheduled a job to delete all synced products!';
			}
		}

		if ( 'wcs-cleanup-products' === $_GET['action'] && check_admin_referer( 'wcs-cleanup-products' ) ) {
			if ( empty( $_GET['async'] ) ) {
				/** @var ProductSyncer $product_syncer */
				$product_syncer = $this->container->get( ProductSyncer::class );
				/** @var ProductRepository $product_repository */
				$product_repository = $this->container->get( ProductRepository::class );
				/** @var BatchProductHelper $batch_product_helper */
				$batch_product_helper = $this->container->get( BatchProductHelper::class );

				try {
					$products = $product_repository->find_synced_products();
					$stale_entries = $batch_product_helper->generate_stale_products_request_entries( $products );

					$result = $product_syncer->delete_by_batch_requests( $stale_entries );

					$this->response .= sprintf( '%s products cleaned up.', count( $result->get_products() ) ) . "\n";
					if ( ! empty( $result->get_errors() ) ) {
						$this->response .= sprintf( 'There were %s errors:', count( $result->get_errors() ) ) . "\n";
						foreach ( $result->get_errors() as $invalid_product ) {
							$this->response .= sprintf( "%s:\n%s", $invalid_product->get_wc_product_id(), implode( "\n", $invalid_product->get_errors() ) ) . "\n";
						}
					}
				} catch ( ProductSyncerException $exception ) {
					$this->response = 'Error cleaning up products: ' . $exception->getMessage();
				}
			} else {
				// schedule a job
				/** @var CleanupProductsJob $delete_job */
				$delete_job = $this->container->get( JobRepository::class )->get( CleanupProductsJob::class );
				$delete_job->schedule();
				$this->response = 'Successfully scheduled a job to cleanup all products!';
			}
		}

		if ( 'migrate-gtin' === $_GET['action'] && check_admin_referer( 'migrate-gtin' ) ) {
			/** @var MigrateGTIN $job */
			$job = $this->container->get( JobRepository::class )->get( MigrateGTIN::class );
			$job->schedule();
			$this->response = 'Successfully scheduled a job to migrate GTIN';
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
		$token   = $manager->get_tokens()->get_access_token();

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

	/**
	 * Send a REST API request and add the response to our buffer.
	 */
	private function send_rest_request( Request $request ) {
		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$data     = $server->response_to_data( $response, false );
		$json     = wp_json_encode( $data, JSON_PRETTY_PRINT );

		$this->response .= 'Request:  ' . $request->get_method() . ' ' . $request->get_route() . PHP_EOL;
		$this->response .= 'Status:   ' . $response->get_status() . PHP_EOL;
		$this->response .= 'Response: ' . $json;

		return $data;
	}
}
