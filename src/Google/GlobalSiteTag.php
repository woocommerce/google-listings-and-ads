<?php
/**
 * Global Site Tag functionality - add main script and track conversions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\GoogleGtagJs;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Psr\Container\ContainerInterface;

/**
 * Main class for Global Site Tag.
 */
class GlobalSiteTag implements Service, Registerable, Conditional, OptionsAwareInterface {

	use OptionsAwareTrait;

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
		$conversion_action = $this->options->get( OptionsInterface::ADS_CONVERSION_ACTION );

		// No snippets without conversion action info.
		if ( ! $conversion_action ) {
			return;
		}

		$ads_conversion_id    = $conversion_action['conversion_id'];
		$ads_conversion_label = $conversion_action['conversion_label'];

		add_action(
			'wp_head',
			function() use ( $ads_conversion_id ) {
				$this->activate_global_site_tag( $ads_conversion_id );
			},
			999998
		);
		add_action(
			'wp_head',
			function() use ( $ads_conversion_id, $ads_conversion_label ) {
				$this->maybe_display_event_snippet( $ads_conversion_id, $ads_conversion_label );
			},
			1000000
		);
	}

	/**
	 * Activate the Global Site Tag framework:
	 * - Insert GST code, or
	 * - Include the Google Ads conversion ID in WooCommerce Google Analytics Integration output, if available
	 *
	 * @param string $ads_conversion_id Google Ads account conversion ID.
	 */
	public function activate_global_site_tag( string $ads_conversion_id ) {
		if ( $this->container->get( GoogleGtagJs::class )->is_adding_framework() ) {
			add_filter(
				'woocommerce_gtag_snippet',
				function( $gtag_snippet ) use ( $ads_conversion_id ) {
					return preg_replace(
						'~(\s)</script>~',
						"\tgtag('config', '" . $ads_conversion_id . "');\n$1</script>",
						$gtag_snippet
					);
				}
			);
		} else {
			$this->display_global_site_tag( $ads_conversion_id );
		}
	}


	/**
	 * Display the JavaScript code to load the Global Site Tag framework.
	 *
	 * @param string $ads_conversion_id Google Ads account conversion ID.
	 */
	protected function display_global_site_tag( string $ads_conversion_id ) {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		?>
		<!-- Global site tag (gtag.js) - Google Ads: <?php echo esc_js( $ads_conversion_id ); ?> -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $ads_conversion_id ); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('set', 'developer_id.<?php echo esc_js( self::DEVELOPER_ID ); ?>', true);

			gtag('config','<?php echo esc_js( $ads_conversion_id ); ?>');
		</script>
		<?php
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	/**
	 * Display the JavaScript code to track conversions on the order confirmation page.
	 *
	 * @param string $ads_conversion_id Google Ads account conversion ID.
	 * @param string $ads_conversion_label Google Ads conversion label.
	 */
	public function maybe_display_event_snippet( string $ads_conversion_id, string $ads_conversion_label ): void {
		// Only display on the order confirmation page.
		if ( ! is_order_received_page() ) {
			return;
		}

		$order_id = $this->container->get( WP::class )->get_query_vars( 'order-received', 0 );
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
		gtag('event', 'conversion', {
			'send_to': '<?php echo esc_js( $ads_conversion_id ); ?>/<?php echo esc_js( $ads_conversion_label ); ?>',
			'value': '<?php echo esc_js( $order->get_total() ); ?>',
			'currency': '<?php echo esc_js( $order->get_currency() ); ?>',
			'transaction_id': '<?php echo esc_js( $order->get_id() ); ?>'
		});
	</script>
			<?php
		}
	}

	/**
	 * TODO: Should the Global Site Tag framework be used if there are no paid Ads campaigns?
	 *
	 * @return bool True if the Global Site Tag framework should be included.
	 */
	public static function is_needed(): bool {
		return true;
	}
}
