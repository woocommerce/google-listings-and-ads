<?php
/**
 * Global Site Tag functionality - add main script and track conversions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Psr\Container\ContainerInterface;

/**
 * Main class for Global Site Tag.
 */
class GlobalSiteTag implements Service, Registerable {

	/** @var string Developer ID */
	protected const DEVELOPER_ID = 'dOGY3NW';

	/** @var string Meta key used to mark orders as converted */
	protected const ORDER_CONVERSION_META_KEY = '_gla_tracked';

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Global Site Tag constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		$aw_conversion_id    = 'AW-TEST-CODE';
		$aw_conversion_label = 'CONVERSION_LABEL';

		add_action(
			'wp_head',
			function() use ( $aw_conversion_id ) {
				$this->activate_global_site_tag( $aw_conversion_id );
			},
			999998
		);
		add_action(
			'wp_head',
			function() use ( $aw_conversion_id, $aw_conversion_label ) {
				$this->maybe_display_event_snippet( $aw_conversion_id, $aw_conversion_label );
			},
			1000000
		);
	}

	/**
	 * Activate the Global Site Tag framework:
	 * - Insert GST code, or
	 * - Include the Google Ads conversion ID in WooCommerce Google Analytics Integration output, if available
	 *
	 * @param string $aw_conversion_id Google Ads account conversion ID.
	 */
	public function activate_global_site_tag( string $aw_conversion_id ) {
		if ( $this->is_woocommerce_google_analytics_active() && $this->is_gtag_enabled() ) {
			add_filter(
				'woocommerce_gtag_snippet',
				function( $gtag_snippet ) use ( $aw_conversion_id ) {
					return preg_replace(
						'~(\s)</script>~',
						"\tgtag('config', '" . $aw_conversion_id . "');\n$1</script>",
						$gtag_snippet
					);
				}
			);
		} else {
			$this->display_global_site_tag( $aw_conversion_id );
		}
	}

	/**
	 * Whether WooCommerce Google Analytics Integration v1.5+ is loaded.
	 *
	 * @return bool True if WooCommerce Google Analytics Integration is loaded.
	 */
	protected function is_woocommerce_google_analytics_active() {
		return class_exists( '\WC_Google_Gtag_JS' );
	}

	/**
	 * Whether WooCommerce Google Analytics Integration has Global Site Tag enabled.
	 *
	 * @return bool True if WooCommerce Google Analytics Integration has "Use Global Site Tag" enabled.
	 */
	protected function is_gtag_enabled(): bool {
		$woocommerce_google_analytics_settings = get_option( 'woocommerce_google_analytics_settings', [] );
		if ( empty( $woocommerce_google_analytics_settings['ga_gtag_enabled'] ) ) {
			return false;
		}
		return 'yes' === $woocommerce_google_analytics_settings['ga_gtag_enabled'];
	}

	/**
	 * Display the JavaScript code to load the Global Site Tag framework.
	 *
	 * @param string $aw_conversion_id Google Ads account conversion ID.
	 */
	protected function display_global_site_tag( string $aw_conversion_id ) {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		?>
		<!-- Global site tag (gtag.js) - Google Ads: <?php echo esc_js( $aw_conversion_id ); ?> -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $aw_conversion_id ); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('set', 'developer_id.<?php echo esc_js( self::DEVELOPER_ID ); ?>', true);

			gtag('config','<?php echo esc_js( $aw_conversion_id ); ?>');
		</script>
		<?php
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	/**
	 * Display the JavaScript code to track conversions on the order confirmation page.
	 *
	 * @param string $aw_conversion_id Google Ads account conversion ID.
	 * @param string $aw_conversion_label Google Ads conversion label.
	 */
	public function maybe_display_event_snippet( string $aw_conversion_id, string $aw_conversion_label ): void {
		// Only display on the order confirmation page.
		if ( ! is_order_received_page() ) {
			return;
		}

		global $wp;
		$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;

		if ( 0 < $order_id && 1 !== get_post_meta( $order_id, self::ORDER_CONVERSION_META_KEY, true ) ) {
			$order = wc_get_order( $order_id );

			// Make sure there is a valid order object.
			if ( ! $order ) {
				return;
			}

			// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
			update_post_meta( $order_id, self::ORDER_CONVERSION_META_KEY, 1 );

			?>
	<script>
		gtag('event', 'conversion', {'send_to': '<?php echo esc_js( $aw_conversion_id ); ?>/<?php echo esc_js( $aw_conversion_label ); ?>',
			'value': '<?php echo esc_js( $order->get_total() ); ?>',
			'currency': '<?php echo esc_js( $order->get_currency() ); ?>'
		});
	</script>
			<?php
		}
	}

}
