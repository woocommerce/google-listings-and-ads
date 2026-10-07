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
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInputPatch;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiDataSourcesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductInputsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
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
							<code><?php echo esc_html( (string) $this->container->get( 'connect_server_root' ) ); ?></code>
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
								<code><?php echo esc_html( (string) Jetpack_Options::get_option( 'id' ) ); ?></code>
							</p>
						</td>
					</tr>
				<?php } ?>

				<?php if ( $user_token ) { ?>
					<tr>
						<th><label>User ID:</label></th>
						<td>
							<p>
								<code><?php echo esc_html( (string) $user_data['ID'] ); ?></code>
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
							<th>MAPI Single Fetch:</th>
							<td>
								<p>
									<input name="mapi_product_id" type="text" style="width:24em" placeholder="online~en~US~sku123" value="<?php echo isset( $_GET['mapi_product_id'] ) ? esc_attr( $_GET['mapi_product_id'] ) : ''; ?>" />
									<button class="button">Fetch product via MAPI</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-product-get' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-product-get" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>MAPI Parallel Fetch:</th>
							<td>
								<p>
									<input name="mapi_product_ids" type="text" style="width:36em" placeholder="id1, id2, id3" value="<?php echo isset( $_GET['mapi_product_ids'] ) ? esc_attr( $_GET['mapi_product_ids'] ) : ''; ?>" />
									<button class="button">Fetch products in parallel via MAPI</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-product-get-many' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-product-get-many" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>MAPI Resolve Data Source:</th>
							<td>
								<p>
									<input name="mapi_ds_language" type="text" style="width:5em" placeholder="en" value="<?php echo isset( $_GET['mapi_ds_language'] ) ? esc_attr( $_GET['mapi_ds_language'] ) : 'en'; ?>" />
									<input name="mapi_ds_feed" type="text" style="width:5em" placeholder="US" value="<?php echo isset( $_GET['mapi_ds_feed'] ) ? esc_attr( $_GET['mapi_ds_feed'] ) : 'US'; ?>" />
									<button class="button">Resolve data source</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-resolve-datasource' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-resolve-datasource" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>MAPI Insert Product:</th>
							<td>
								<p>
									<input name="mapi_offer_id" type="text" style="width:18em" placeholder="offer id" value="<?php echo isset( $_GET['mapi_offer_id'] ) ? esc_attr( $_GET['mapi_offer_id'] ) : ''; ?>" />
									<input name="mapi_title" type="text" style="width:22em" placeholder="product title" value="<?php echo isset( $_GET['mapi_title'] ) ? esc_attr( $_GET['mapi_title'] ) : ''; ?>" />
									<button class="button">Insert product via MAPI</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-product-insert' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-product-insert" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>MAPI Parallel Insert:</th>
							<td>
								<p>
									<input name="mapi_offer_ids" type="text" style="width:36em" placeholder="offer1, offer2, offer3" value="<?php echo isset( $_GET['mapi_offer_ids'] ) ? esc_attr( $_GET['mapi_offer_ids'] ) : ''; ?>" />
									<button class="button">Insert products in parallel via MAPI</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-product-insert-many' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-product-insert-many" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>MAPI Patch Product:</th>
							<td>
								<p>
									<input name="mapi_patch_offer_id" type="text" style="width:14em" placeholder="offer id" value="<?php echo isset( $_GET['mapi_patch_offer_id'] ) ? esc_attr( $_GET['mapi_patch_offer_id'] ) : ''; ?>" />
									<input name="mapi_patch_attribute" type="text" style="width:10em" placeholder="title" value="<?php echo isset( $_GET['mapi_patch_attribute'] ) ? esc_attr( $_GET['mapi_patch_attribute'] ) : 'title'; ?>" />
									<input name="mapi_patch_value" type="text" style="width:18em" placeholder="new value" value="<?php echo isset( $_GET['mapi_patch_value'] ) ? esc_attr( $_GET['mapi_patch_value'] ) : ''; ?>" />
									<button class="button">Patch product via MAPI</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-product-patch' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-product-patch" type="hidden" />
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>MAPI Parallel Patch:</th>
							<td>
								<p>
									<input name="mapi_patch_offer_ids" type="text" style="width:28em" placeholder="offer1, offer2, offer3" value="<?php echo isset( $_GET['mapi_patch_offer_ids'] ) ? esc_attr( $_GET['mapi_patch_offer_ids'] ) : ''; ?>" />
									<input name="mapi_patch_title" type="text" style="width:18em" placeholder="new title for all" value="<?php echo isset( $_GET['mapi_patch_title'] ) ? esc_attr( $_GET['mapi_patch_title'] ) : ''; ?>" />
									<button class="button">Patch products in parallel via MAPI</button>
								</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-product-patch-many' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-product-patch-many" type="hidden" />
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
										echo esc_html( (string) $merchant_id ); ?> ||
										<?php foreach ( $mc_account_state as $name => $step ) : ?>
											<?php echo esc_html( $name . ':' . $step['status'] ); ?>
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

				<h2 class="title">MAPI Data Sources</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th>Inventory:</th>
						<td>
							<p>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'mapi-ds-inventory' ], $url ), 'mapi-ds-inventory' ) ); ?>">List Data Sources</a>
							</p>
							<p class="description">Lists every data source on the merchant account, with its type and whether <code>fileInput</code> is set.</p>
						</td>
					</tr>
				</table>

				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Resolve Promotion Data Source:</th>
							<td>
								<p>
									<input name="mapi_ds_promo_language" type="text" style="width:5em" placeholder="en" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['mapi_ds_promo_language'] ?? 'en' ) ) ); ?>" />
									<input name="mapi_ds_promo_country" type="text" style="width:5em" placeholder="US" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['mapi_ds_promo_country'] ?? 'US' ) ) ); ?>" />
									<button class="button" type="submit">Resolve promotion data source</button>
								</p>
								<p class="description">Runs <code>ensure_promotion_data_source_for()</code>, the resolution path used when promotions are synced.</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-ds-resolve-promo' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-ds-resolve-promo" type="hidden" />
				</form>

				<?php if ( ! empty( $_GET['enable_ds_delete'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="GET">
					<table class="form-table" role="presentation">
						<tr>
							<th>Delete Data Source:</th>
							<td>
								<p>
									<input name="mapi_ds_delete_name" type="text" style="width:32em" placeholder="data source name or numeric id" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['mapi_ds_delete_name'] ?? '' ) ) ); ?>" />
									<button class="button" type="submit">Delete data source</button>
								</p>
								<p class="description">Deletes the given data source from the merchant account and drops its name from the resolved-data-source cache. A source's <code>fileInput</code> field cannot be cleared after creation (the MAPI rejects it with FAILED_PRECONDITION), so deleting the source and letting the plugin re-resolve is the only recovery for a poisoned source. Destructive; for test accounts only.</p>
							</td>
						</tr>
					</table>
					<?php wp_nonce_field( 'mapi-ds-delete' ); ?>
					<input name="page" value="connection-test-admin-page" type="hidden" />
					<input name="action" value="mapi-ds-delete" type="hidden" />
					<input name="enable_ds_delete" value="1" type="hidden" />
				</form>
				<?php endif; ?>

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
										( Ads account status -- ID: <?php echo esc_html( (string) $this->container->get( OptionsInterface::class )->get( OptionsInterface::ADS_ID ) ); ?> ||
										<?php foreach ( $ads_account_state as $name => $step ) : ?>
											<?php echo esc_html( $name . ':' . $step['status'] ); ?>
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
											<?php echo esc_html( "{$name} : \"{$value}\"" ); ?>
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

		add_filter(
			'woocommerce_gla_ads_id',
			function ( $id ) {
				return ! empty( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : $id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		);

		add_filter(
			'woocommerce_gla_merchant_id',
			function ( $id ) {
				return ! empty( $_GET['merchant_id'] ) ? intval( $_GET['merchant_id'] ) : $id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		);

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
			/** @var MapiProductsService $service */
			$service = $this->container->get( MapiProductsService::class );
			/** @var OptionsInterface $options */
			$options = $this->container->get( OptionsInterface::class );

			if ( empty( $options->get_merchant_id() ) ) {
				$this->response .= 'Please enter a Merchant ID';
				return;
			}

			$this->response = "Proxied request > get products for merchant {$options->get_merchant_id()}\n";

			try {
				$count = 0;
				foreach ( $service->list() as $product ) {
					$this->response .= "{$product->get_id()} {$product->get_title()}\n";
					++$count;
				}

				if ( 0 === $count ) {
					$this->response .= 'No products found';
				}
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-product-get' === $_GET['action'] && check_admin_referer( 'mapi-product-get' ) ) {
			$id = isset( $_GET['mapi_product_id'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_product_id'] ) ) : '';

			if ( '' === $id ) {
				$this->response .= 'Please enter a Google product ID.';
				return;
			}

			/** @var MapiProductsService $service */
			$service        = $this->container->get( MapiProductsService::class );
			$this->response = "MAPI GET accounts.products.get for {$id}\n\n";

			try {
				$product = $service->get( $id );
				$this->response .= print_r( $this->dump_product( $product ), true );
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-product-get-many' === $_GET['action'] && check_admin_referer( 'mapi-product-get-many' ) ) {
			$raw = isset( $_GET['mapi_product_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_product_ids'] ) ) : '';
			$ids = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

			if ( empty( $ids ) ) {
				$this->response .= 'Please enter one or more Google product IDs (comma-separated).';
				return;
			}

			/** @var MapiProductsService $service */
			$service        = $this->container->get( MapiProductsService::class );
			$this->response = sprintf( "MAPI parallel fetch for %d product(s)\n\n", count( $ids ) );

			$results = $service->get_many( $ids );

			foreach ( $ids as $id ) {
				$this->response .= "--- {$id} ---\n";
				if ( isset( $results[ $id ] ) ) {
					$this->response .= print_r( $this->dump_product( $results[ $id ] ), true );
				} else {
					$this->response .= "(no result)\n";
				}
			}
		}

		if ( 'mapi-resolve-datasource' === $_GET['action'] && check_admin_referer( 'mapi-resolve-datasource' ) ) {
			$language = isset( $_GET['mapi_ds_language'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_ds_language'] ) ) : 'en';
			$feed     = isset( $_GET['mapi_ds_feed'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_ds_feed'] ) ) : 'US';

			/** @var MapiDataSourcesService $service */
			$service        = $this->container->get( MapiDataSourcesService::class );
			$this->response = "MAPI ensure_data_source_for({$language}, {$feed})\n\n";

			try {
				$this->response .= $service->ensure_data_source_for( $language, $feed ) . "\n";
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-ds-inventory' === $_GET['action'] && check_admin_referer( 'mapi-ds-inventory' ) ) {
			/** @var OptionsInterface $options */
			$options     = $this->container->get( OptionsInterface::class );
			$merchant_id = $options->get_merchant_id();

			if ( empty( $merchant_id ) ) {
				$this->response .= 'Please enter a Merchant ID';
				return;
			}

			/** @var MerchantApiClient $client */
			$client         = $this->container->get( MerchantApiClient::class );
			$this->response = "MAPI data source inventory for merchant {$merchant_id}\n\n";

			try {
				$inventory = $this->list_mapi_data_sources( $client, $merchant_id );

				if ( empty( $inventory ) ) {
					$this->response .= 'No data sources found.';
				} else {
					$this->response .= sprintf(
						"%-34s  %-16s  %-30s  %-8s  %-10s  %s\n",
						'Name',
						'Kind',
						'Display',
						'Country',
						'Label',
						'fileInput'
					);

					foreach ( $inventory as $name => $source ) {
						$this->response .= sprintf(
							"%-34s  %-16s  %-30s  %-8s  %-10s  %s\n",
							$name,
							$source['kind'],
							$source['display_name'],
							$source['target_country'],
							$source['feed_label'],
							$source['has_file_input'] ? 'yes' : 'no'
						);
					}
				}
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-ds-resolve-promo' === $_GET['action'] && check_admin_referer( 'mapi-ds-resolve-promo' ) ) {
			$language = isset( $_GET['mapi_ds_promo_language'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_ds_promo_language'] ) ) : 'en';
			$country  = isset( $_GET['mapi_ds_promo_country'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_ds_promo_country'] ) ) : 'US';

			/** @var MapiDataSourcesService $service */
			$service        = $this->container->get( MapiDataSourcesService::class );
			$this->response = "MAPI ensure_promotion_data_source_for({$language}, {$country})\n\n";

			try {
				$name = $service->ensure_promotion_data_source_for( $language, $country );

				/** @var MerchantApiClient $client */
				$client = $this->container->get( MerchantApiClient::class );
				$state  = $this->describe_mapi_data_source( $client->get( sprintf( '%s/%s', MapiPaths::DATASOURCES, $name ) ) );

				$this->response .= "Resolved: {$name}\n";
				$this->response .= sprintf(
					'State:   %s -- %s' . "\n",
					$state['kind'],
					$state['has_file_input'] ? 'has fileInput' : 'clean (API)'
				);
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if (
			'mapi-ds-delete' === $_GET['action']
			&& check_admin_referer( 'mapi-ds-delete' )
			&& ! empty( $_GET['enable_ds_delete'] )
		) {
			$name = isset( $_GET['mapi_ds_delete_name'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_ds_delete_name'] ) ) : '';

			if ( '' === $name ) {
				$this->response .= 'Please enter a data source name or numeric id.';
				return;
			}

			/** @var OptionsInterface $options */
			$options     = $this->container->get( OptionsInterface::class );
			$merchant_id = $options->get_merchant_id();

			if ( empty( $merchant_id ) ) {
				$this->response .= 'Please enter a Merchant ID';
				return;
			}

			/** @var MerchantApiClient $client */
			$client         = $this->container->get( MerchantApiClient::class );
			$this->response = "MAPI dataSources.delete for {$name}\n\n";

			try {
				$inventory = $this->list_mapi_data_sources( $client, $merchant_id );
				$full_name = $this->resolve_mapi_data_source( $inventory, $name );

				if ( null === $full_name ) {
					$this->response .= 'Data source not found on this account. Re-open the page and try again.';
					return;
				}

				$client->delete( sprintf( '%s/%s', MapiPaths::DATASOURCES, $full_name ) );
				$this->response .= "Deleted data source {$full_name}\n";
				$this->response .= $this->remove_mapi_data_source_from_cache( $options, $full_name );
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-product-insert' === $_GET['action'] && check_admin_referer( 'mapi-product-insert' ) ) {
			$offer_id = isset( $_GET['mapi_offer_id'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_offer_id'] ) ) : '';
			$title    = isset( $_GET['mapi_title'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_title'] ) ) : '';

			if ( '' === $offer_id ) {
				$this->response .= 'Please enter an offer ID.';
				return;
			}

			$input = new ProductInput(
				$offer_id,
				'en',
				'US',
				[
					'title'        => '' !== $title ? $title : $offer_id,
					'description'  => 'Inserted via Connection Test.',
					'link'         => home_url( '/' ),
					'imageLink'    => 'https://via.placeholder.com/250',
					'availability' => 'IN_STOCK',
					'condition'    => 'NEW',
					'price'        => [
						'amountMicros' => '19990000',
						'currencyCode' => 'USD',
					],
				]
			);

			/** @var MapiProductInputsService $service */
			$service        = $this->container->get( MapiProductInputsService::class );
			$this->response = "MAPI productInputs.insert for {$offer_id}\n\n";

			try {
				$result          = $service->insert( $input );
				$this->response .= print_r(
					[
						'name'       => $result->get_name(),
						'offer_id'   => $result->get_offer_id(),
						'feed_label' => $result->get_feed_label(),
						'attributes' => $result->get_attributes(),
					],
					true
				);
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-product-insert-many' === $_GET['action'] && check_admin_referer( 'mapi-product-insert-many' ) ) {
			$raw       = isset( $_GET['mapi_offer_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_offer_ids'] ) ) : '';
			$offer_ids = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

			if ( empty( $offer_ids ) ) {
				$this->response .= 'Please enter one or more offer IDs (comma-separated).';
				return;
			}

			$inputs = [];
			foreach ( $offer_ids as $offer_id ) {
				$inputs[] = new ProductInput(
					$offer_id,
					'en',
					'US',
					[
						'title'        => $offer_id,
						'description'  => 'Inserted via Connection Test.',
						'link'         => home_url( '/' ),
						'imageLink'    => 'https://via.placeholder.com/250',
						'availability' => 'IN_STOCK',
						'condition'    => 'NEW',
						'price'        => [
							'amountMicros' => '19990000',
							'currencyCode' => 'USD',
						],
					]
				);
			}

			/** @var MapiProductInputsService $service */
			$service        = $this->container->get( MapiProductInputsService::class );
			$this->response = sprintf( "MAPI parallel productInputs.insert for %d product(s)\n\n", count( $inputs ) );

			$result = $service->insert_many( $inputs );

			foreach ( $offer_ids as $index => $offer_id ) {
				$this->response .= "--- {$offer_id} ---\n";
				if ( isset( $result['successes'][ $index ] ) ) {
					$this->response .= $result['successes'][ $index ]->get_name() . "\n";
				} elseif ( isset( $result['failures'][ $index ] ) ) {
					$e               = $result['failures'][ $index ];
					$this->response .= $e instanceof MerchantApiException
						? sprintf( "HTTP %d\n%s", $e->get_http_status(), print_r( $e->get_response_body(), true ) )
						: get_class( $e ) . ': ' . $e->getMessage() . "\n";
				} else {
					$this->response .= "(no result)\n";
				}
			}
		}

		if ( 'mapi-product-patch' === $_GET['action'] && check_admin_referer( 'mapi-product-patch' ) ) {
			$offer_id  = isset( $_GET['mapi_patch_offer_id'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_patch_offer_id'] ) ) : '';
			$attribute = isset( $_GET['mapi_patch_attribute'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_patch_attribute'] ) ) : '';
			$value     = isset( $_GET['mapi_patch_value'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_patch_value'] ) ) : '';

			if ( '' === $offer_id || '' === $attribute ) {
				$this->response .= 'Please enter an offer ID and an attribute.';
				return;
			}

			$input = new ProductInput( $offer_id, 'en', 'US', [ $attribute => $value ] );
			$patch = new ProductInputPatch( $input, [ "productAttributes.{$attribute}" ] );

			/** @var MapiProductInputsService $service */
			$service        = $this->container->get( MapiProductInputsService::class );
			$this->response = "MAPI productInputs.patch for {$offer_id} ({$attribute})\n\n";

			try {
				$result          = $service->patch( $patch );
				$this->response .= print_r(
					[
						'name'       => $result->get_name(),
						'offer_id'   => $result->get_offer_id(),
						'attributes' => $result->get_attributes(),
					],
					true
				);
			} catch ( MerchantApiException $e ) {
				$this->response .= sprintf( "HTTP %d\n", $e->get_http_status() );
				$this->response .= print_r( $e->get_response_body(), true );
			}
		}

		if ( 'mapi-product-patch-many' === $_GET['action'] && check_admin_referer( 'mapi-product-patch-many' ) ) {
			$raw       = isset( $_GET['mapi_patch_offer_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_patch_offer_ids'] ) ) : '';
			$title     = isset( $_GET['mapi_patch_title'] ) ? sanitize_text_field( wp_unslash( $_GET['mapi_patch_title'] ) ) : '';
			$offer_ids = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

			if ( empty( $offer_ids ) || '' === $title ) {
				$this->response .= 'Please enter one or more offer IDs (comma-separated) and a title.';
				return;
			}

			$patches = [];
			foreach ( $offer_ids as $offer_id ) {
				$patches[] = new ProductInputPatch(
					new ProductInput( $offer_id, 'en', 'US', [ 'title' => $title ] ),
					[ 'productAttributes.title' ]
				);
			}

			/** @var MapiProductInputsService $service */
			$service        = $this->container->get( MapiProductInputsService::class );
			$this->response = sprintf( "MAPI parallel productInputs.patch for %d product(s)\n\n", count( $patches ) );

			$result = $service->patch_many( $patches );

			foreach ( $offer_ids as $index => $offer_id ) {
				$this->response .= "--- {$offer_id} ---\n";
				if ( isset( $result['successes'][ $index ] ) ) {
					$this->response .= $result['successes'][ $index ]->get_name() . "\n";
				} elseif ( isset( $result['failures'][ $index ] ) ) {
					$e               = $result['failures'][ $index ];
					$this->response .= $e instanceof MerchantApiException
						? sprintf( "HTTP %d\n%s", $e->get_http_status(), print_r( $e->get_response_body(), true ) )
						: get_class( $e ) . ': ' . $e->getMessage() . "\n";
				} else {
					$this->response .= "(no result)\n";
				}
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
				$this->yes_or_no( $accepted->accepted() ),
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
					$stale_entries = $batch_product_helper->generate_stale_products_delete_entries( $products );

					$result = $product_syncer->delete_mapi_entries( $stale_entries );

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
	 * List every data source on the merchant account, summarised for the debug page.
	 *
	 * @param MerchantApiClient $client      Merchant API client.
	 * @param int               $merchant_id Merchant Center account id.
	 *
	 * @return array<string, array<string, string|bool>> Data sources keyed by full resource name.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	private function list_mapi_data_sources( MerchantApiClient $client, int $merchant_id ): array {
		$inventory  = [];
		$page_token = '';

		do {
			$path = sprintf( '%s/accounts/%s/dataSources', MapiPaths::DATASOURCES, $merchant_id );
			if ( '' !== $page_token ) {
				$path .= '?pageToken=' . rawurlencode( $page_token );
			}

			$response = $client->get( $path );

			foreach ( (array) ( $response['dataSources'] ?? [] ) as $source ) {
				if ( empty( $source['name'] ) ) {
					continue;
				}
				$inventory[ (string) $source['name'] ] = $this->describe_mapi_data_source( $source );
			}

			$page_token = $response['nextPageToken'] ?? '';
		} while ( '' !== $page_token );

		return $inventory;
	}

	/**
	 * Summarise one data source entry from a MAPI dataSources.list response.
	 *
	 * @param array $source Raw data source entry.
	 *
	 * @return array{id: string, kind: string, display_name: string, target_country: string, feed_label: string, has_file_input: bool}
	 */
	private function describe_mapi_data_source( array $source ): array {
		$promotion = isset( $source['promotionDataSource'] ) && is_array( $source['promotionDataSource'] )
			? $source['promotionDataSource']
			: null;
		$product   = isset( $source['primaryProductDataSource'] ) && is_array( $source['primaryProductDataSource'] )
			? $source['primaryProductDataSource']
			: null;
		$has_file  = isset( $source['fileInput'] ) && is_array( $source['fileInput'] ) && ! empty( $source['fileInput'] );

		return [
			'id'             => basename( (string) $source['name'] ),
			'kind'           => $has_file
				? 'file'
				: ( null !== $promotion ? 'API (promotion)' : ( null !== $product ? 'API (product)' : 'other' ) ),
			'display_name'   => (string) ( $source['displayName'] ?? '' ),
			'target_country' => null !== $promotion ? (string) ( $promotion['targetCountry'] ?? '' ) : '',
			'feed_label'     => null !== $product ? (string) ( $product['feedLabel'] ?? '' ) : '',
			'has_file_input' => $has_file,
		];
	}

	/**
	 * Resolve a user-supplied data source reference to its full resource name.
	 *
	 * Accepts a full resource name (accounts/<id>/dataSources/<id>), optionally
	 * prefixed with the API version, or the numeric id shown in the inventory.
	 *
	 * @param array<string, array<string, string|bool>> $inventory Data sources from list_mapi_data_sources().
	 * @param string                                    $reference Full resource name or numeric id.
	 *
	 * @return string|null The full resource name, or null when the reference matches no data source.
	 */
	private function resolve_mapi_data_source( array $inventory, string $reference ): ?string {
		$reference = ltrim( $reference, '/' );

		if ( '' === $reference ) {
			return null;
		}

		$prefix = MapiPaths::DATASOURCES . '/';
		if ( 0 === strpos( $reference, $prefix ) ) {
			$reference = substr( $reference, strlen( $prefix ) );
		}

		if ( isset( $inventory[ $reference ] ) ) {
			return $reference;
		}

		if ( is_numeric( $reference ) ) {
			foreach ( $inventory as $name => $source ) {
				if ( $reference === $source['id'] ) {
					return $name;
				}
			}
		}

		return null;
	}

	/**
	 * Drop every resolved-data-source cache entry pointing at a deleted data source,
	 * so the next ensure_*() call re-resolves from the API instead of re-adopting
	 * the deleted name.
	 *
	 * @param OptionsInterface $options   Plugin options.
	 * @param string           $full_name Full resource name of the deleted data source.
	 *
	 * @return string A human-readable summary of the cache cleanup.
	 */
	private function remove_mapi_data_source_from_cache( OptionsInterface $options, string $full_name ): string {
		$cache   = (array) $options->get( OptionsInterface::MAPI_DATA_SOURCES, [] );
		$changed = false;

		foreach ( $cache as $key => $value ) {
			if ( (string) $value === $full_name ) {
				unset( $cache[ $key ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			$options->update( OptionsInterface::MAPI_DATA_SOURCES, $cache );
			return 'Resolved-data-source cache cleaned.';
		}

		return 'No resolved-data-source cache entry pointed at this data source.';
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
	 * Flatten a MAPI Product DTO
	 */
	private function dump_product( Product $product ): array {
		$data = [
			'id'             => $product->get_id(),
			'offer_id'       => $product->get_offer_id(),
			'title'          => $product->get_title(),
			'target_country' => $product->get_target_country(),
		];

		$status = $product->get_product_status();
		if ( null !== $status ) {
			$issues = [];
			foreach ( $status->get_item_level_issues() as $issue ) {
				$issues[] = [
					'code'                 => $issue->get_code(),
					'description'          => $issue->get_description(),
					'detail'               => $issue->get_detail(),
					'documentation'        => $issue->get_documentation(),
					'resolution'           => $issue->get_resolution(),
					'severity'             => $issue->get_severity(),
					'applicable_countries' => $issue->get_applicable_countries(),
				];
			}

			$data['product_status'] = [
				'last_update_date'     => $status->get_last_update_date(),
				'destination_statuses' => $status->get_destination_statuses(),
				'item_level_issues'    => $issues,
			];
		}

		return $data;
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
