<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\GoogleGtagJs;

defined( 'ABSPATH' ) || exit;

/**
 * Injects the Google Tag Manager container snippet on the storefront once a container is connected,
 * unless the merchant has turned snippet injection off.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class TagManagerSiteTag implements Service, Registerable, Conditional {

	/** @var Connection */
	protected $connection;

	/** @var Settings */
	protected $settings;

	/** @var GoogleGtagJs */
	protected $gtag_js;

	/**
	 * TagManagerSiteTag constructor.
	 *
	 * @param Connection   $connection
	 * @param Settings     $settings
	 * @param GoogleGtagJs $gtag_js
	 */
	public function __construct( Connection $connection, Settings $settings, GoogleGtagJs $gtag_js ) {
		$this->connection = $connection;
		$this->settings   = $settings;
		$this->gtag_js    = $gtag_js;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		$container_public_id = $this->connection->get_connection_data()['container_public_id'] ?? '';

		if ( ! $this->settings->is_snippet_active() ) {
			return;
		}

		// The container's consent defaults are normally published by the Ads tag, which isn't registered when
		// it's disabled, so publish them here instead. They must be in place before the container loads.
		if ( ! GlobalSiteTag::is_needed() ) {
			add_action(
				'wp_head',
				function () {
					$this->display_consent_defaults();
				},
				999998
			);
		}

		add_action(
			'wp_head',
			function () use ( $container_public_id ) {
				$this->display_snippet( $container_public_id );
			},
			999999
		);

		add_action(
			'wp_body_open',
			function () use ( $container_public_id ) {
				$this->display_noscript_fallback( $container_public_id );
			}
		);
	}

	/**
	 * Whether the connected container is missing a usable public ID at render time.
	 *
	 * Does not detect a failed connect attempt itself — this plugin has no stored
	 * connection-recovery state to check yet, only what's derivable from the stored
	 * connection data.
	 *
	 * @return bool
	 */
	public function has_injection_failed(): bool {
		$data = $this->connection->get_connection_data();

		return ! empty( $data['container_id'] ) && empty( $data['container_public_id'] );
	}

	/**
	 * Display the `window.dataLayer`/`gtag()` shim and the default consent state.
	 */
	protected function display_consent_defaults() {
		// WooCommerce Google Analytics for WooCommerce publishes its own defaults when it's adding the framework.
		if ( $this->gtag_js->is_adding_framework() ) {
			return;
		}

		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		?>

		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag() { dataLayer.push(arguments); }
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ConsentModeSnippet::get_config();
			?>
		</script>

		<?php
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	/**
	 * Display the JavaScript code to load the Google Tag Manager container.
	 *
	 * @param string $container_public_id The connected container's public ID (e.g. `GTM-XXXXXXX`).
	 */
	protected function display_snippet( string $container_public_id ) {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		?>

		<!-- Google Tag Manager - Google for WooCommerce -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','<?php echo esc_js( $container_public_id ); ?>');</script>
		<!-- End Google Tag Manager -->

		<?php
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	/**
	 * Display the `<noscript>` iframe fallback required for non-JS clients.
	 *
	 * @param string $container_public_id The connected container's public ID (e.g. `GTM-XXXXXXX`).
	 */
	protected function display_noscript_fallback( string $container_public_id ) {
		?>

		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $container_public_id ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->

		<?php
	}

	/**
	 * @return bool True — real gating happens inside register(), not at the Conditional level,
	 *              since a merchant's connection data isn't cheaply checkable statically.
	 */
	public static function is_needed(): bool {
		return true;
	}
}
