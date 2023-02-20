<?php
declare( strict_types=1 );

/**
 * Global Site Tag functionality - add main script and track conversions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\ScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\GoogleGtagJs;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Main class for Global Site Tag.
 */
class GlobalSiteTag implements Service, Registerable, Conditional, OptionsAwareInterface {

	use OptionsAwareTrait;
	use PluginHelper;

	/** @var string Developer ID */
	protected const DEVELOPER_ID = 'dOGY3NW';

	/** @var string Meta key used to mark orders as converted */
	protected const ORDER_CONVERSION_META_KEY = '_gla_tracked';

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * @var GoogleGtagJs
	 */
	protected $gtag_js;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * Additional product data used for tracking add_to_cart events.
	 *
	 * @var array
	 */
	protected $products = [];

	/**
	 * Global Site Tag constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param GoogleGtagJs           $gtag_js
	 * @param ProductHelper          $product_helper
	 * @param WC                     $wc
	 * @param WP                     $wp
	 */
	public function __construct(
		AssetsHandlerInterface $assets_handler,
		GoogleGtagJs $gtag_js,
		ProductHelper $product_helper,
		WC $wc,
		WP $wp
	) {
		$this->assets_handler = $assets_handler;
		$this->gtag_js        = $gtag_js;
		$this->product_helper = $product_helper;
		$this->wc             = $wc;
		$this->wp             = $wp;
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
			function () use ( $ads_conversion_id ) {
				$this->activate_global_site_tag( $ads_conversion_id );
			},
			999999
		);

		add_action(
			'woocommerce_before_thankyou',
			function ( $order_id ) use ( $ads_conversion_id, $ads_conversion_label ) {
				$this->maybe_display_conversion_and_purchase_event_snippets( $ads_conversion_id, $ads_conversion_label, $order_id );
			},
		);

		add_action(
			'woocommerce_after_single_product',
			function () {
				$this->display_view_item_event_snippet();
			}
		);

		add_action(
			'wp_body_open',
			function () {
				$this->display_page_view_event_snippet();
			}
		);

		$this->product_data_hooks();
		$this->register_assets();
	}

	/**
	 * Attach filters to add product data required for tracking events.
	 */
	protected function product_data_hooks() {
		// Add product data for any add_to_cart link.
		add_filter(
			'woocommerce_loop_add_to_cart_link',
			function ( $link, $product ) {
				$this->add_product_data( $product );
				return $link;
			},
			10,
			2
		);

		// Add display name for an available variation.
		add_filter(
			'woocommerce_available_variation',
			function ( $data, $instance, $variation ) {
				$data['display_name'] = $variation->get_name();
				return $data;
			},
			10,
			3
		);
	}

	/**
	 * Register and enqueue assets for gtag events in blocks.
	 */
	protected function register_assets() {
		$gtag_events = new ScriptWithBuiltDependenciesAsset(
			'gla-gtag-events',
			'js/build/gtag-events',
			"{$this->get_root_dir()}/js/build/gtag-events.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => $this->get_version(),
				]
			),
			function () {
				return is_page() || is_woocommerce() || is_cart();
			}
		);

		$this->assets_handler->add( $gtag_events );

		add_action(
			'wp_footer',
			function () use ( $gtag_events ) {
				$gtag_events->add_localization(
					'glaGtagData',
					[
						'currency_minor_unit' => wc_get_price_decimals(),
						'products'            => $this->products,
					]
				);

				$this->assets_handler->enqueue( $gtag_events );
			}
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
		if ( $this->gtag_js->is_adding_framework() ) {
			add_filter(
				'woocommerce_gtag_snippet',
				function ( $gtag_snippet ) use ( $ads_conversion_id ) {
					return preg_replace(
						'~(\s)</script>~',
						"\tgtag('config', '" . $ads_conversion_id . "', { 'groups': 'GLA', 'send_page_view': false });\n$1</script>",
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

		<!-- Global site tag (gtag.js) - Google Ads: <?php echo esc_js( $ads_conversion_id ); ?> - Google Listings & Ads -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $ads_conversion_id ); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag() { dataLayer.push(arguments); }

			gtag('js', new Date());
			gtag('set', 'developer_id.<?php echo esc_js( self::DEVELOPER_ID ); ?>', true);
			gtag('config', '<?php echo esc_js( $ads_conversion_id ); ?>', {
				'groups': 'GLA',
				'send_page_view': false
			});
		</script>

		<?php
		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	/**
	 * Display the JavaScript code to track conversions on the order confirmation page.
	 *
	 * @param string $ads_conversion_id Google Ads account conversion ID.
	 * @param string $ads_conversion_label Google Ads conversion label.
	 * @param int    $order_id The order id.
	 */
	public function maybe_display_conversion_and_purchase_event_snippets( string $ads_conversion_id, string $ads_conversion_label, int $order_id ): void {
		// Only display on the order confirmation page.
		if ( ! is_order_received_page() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		// Make sure there is a valid order object and it is not already marked as tracked
		if ( ! $order || 1 === $order->get_meta( self::ORDER_CONVERSION_META_KEY, true ) ) {
			return;
		}

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_CONVERSION_META_KEY, 1 );
		$order->save_meta_data();

		$conversion_gtag_info =
		sprintf(
			'gtag("event", "conversion", {
			send_to: "%s",
			value: %f,
			currency: "%s",
			transaction_id: "%s"});',
			esc_js( "{$ads_conversion_id}/{$ads_conversion_label}" ),
			$order->get_total(),
			esc_js( $order->get_currency() ),
			esc_js( $order->get_id() ),
		);
		wp_print_inline_script_tag( $conversion_gtag_info );

		// Get the item info in the order
		$item_info = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id   = $item->get_product_id();
			$product_name = $item->get_name();
			$quantity     = $item->get_quantity();
			$price        = $item->get_subtotal();
			$item_info [] = sprintf(
				'{
				id: "gla_%s",
				price: %f,
				google_business_vertical: "retail",
				name: "%s",
				quantity: %d,
				}',
				esc_js( $product_id ),
				$price,
				esc_js( $product_name ),
				$quantity,
			);
		}

		// Check if this is the first time customer
		$is_new_customer = $this->is_first_time_customer( $order->get_billing_email() );

		// Track the purchase page
		$language = $this->wp->get_locale();
		if ( 'en_US' === $language ) {
			$language = 'English';
		}
		$purchase_page_gtag =
		sprintf(
			'gtag("event", "purchase", {
			ecomm_pagetype: "purchase",
			send_to: "%s",
			transaction_id: "%s",
			currency: "%s",
			country: "%s",
			value: %f,
			new_customer: %s,
			tax: %f,
			shipping: %f,
			delivery_postal_code: "%s",
			aw_feed_country: "%s",
			aw_feed_language: "%s",
			items: [%s]});',
			esc_js( "{$ads_conversion_id}/{$ads_conversion_label}" ),
			esc_js( $order->get_id() ),
			esc_js( $order->get_currency() ),
			esc_js( $this->wc->get_base_country() ),
			$order->get_total(),
			$is_new_customer ? 'true' : 'false',
			esc_js( $order->get_cart_tax() ),
			$order->get_total_shipping(),
			esc_js( $order->get_billing_postcode() ),
			esc_js( $this->wc->get_base_country() ),
			esc_js( $language ),
			join( ',', $item_info ),
		);
		wp_print_inline_script_tag( $purchase_page_gtag );
	}

	/**
	 * Display the JavaScript code to track the product view page.
	 */
	private function display_view_item_event_snippet(): void {
		$product = wc_get_product( get_the_ID() );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$this->add_product_data( $product );

		$view_item_gtag = sprintf(
			'gtag("event", "view_item", {
			send_to: "GLA",
			ecomm_pagetype: "product",
			value: %f,
			items:[{
				id: "gla_%s",
				price: %f,
				google_business_vertical: "retail",
				name: "%s",
				category: "%s",
			}]});',
			wc_get_price_to_display( $product ),
			esc_js( $product->get_id() ),
			wc_get_price_to_display( $product ),
			esc_js( $product->get_name() ),
			esc_js( join( ' & ', $this->product_helper->get_categories( $product ) ) ),
		);
		wp_print_inline_script_tag( $view_item_gtag );
	}

	/**
	 * Display the JavaScript code to track all pages.
	 */
	private function display_page_view_event_snippet(): void {
		if ( ! is_cart() ) {
			wp_print_inline_script_tag(
				'gtag("event", "page_view", {send_to: "GLA"});'
			);
			return;
		}
		// display the JavaScript code to track the cart page
		$item_info = [];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			// gets the product id
			$id = $cart_item['product_id'];

			// gets the product object
			$product = $cart_item['data'];
			$name    = $product->get_name();
			$price   = WC()->cart->display_prices_including_tax() ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product );
			// gets the cart item quantity
			$quantity = $cart_item['quantity'];

			$item_info[] = sprintf(
				'{
				id: "gla_%s",
				price: %f,
				google_business_vertical: "retail",
				name:"%s",
				quantity: %d,
				}',
				esc_js( $id ),
				$price,
				esc_js( $name ),
				$quantity,
			);
		}
		$value          = WC()->cart->total;
		$page_view_gtag = sprintf(
			'gtag("event", "page_view", {
			send_to: "GLA",
			ecomm_pagetype: "cart",
			value: %f,
			items: [%s]});',
			$value,
			join( ',', $item_info ),
		);
		wp_print_inline_script_tag( $page_view_gtag );
	}

	/**
	 * Add product data to include in JS data.
	 *
	 * @since 2.0.3
	 *
	 * @param WC_Product $product
	 */
	protected function add_product_data( $product ) {
		$this->products[ $product->get_id() ] = [
			'name'  => $product->get_name(),
			'price' => wc_get_price_to_display( $product ),
		];
	}

	/**
	 * TODO: Should the Global Site Tag framework be used if there are no paid Ads campaigns?
	 *
	 * @return bool True if the Global Site Tag framework should be included.
	 */
	public static function is_needed(): bool {
		if ( apply_filters( 'woocommerce_gla_disable_gtag_tracking', false ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the customer has previous orders.
	 * Called after order creation (check for older orders including the order which was just created).
	 *
	 * @param string $customer_email Customer email address.
	 * @return bool True if this customer has previous orders.
	 */
	private static function is_first_time_customer( $customer_email ): bool {
		$query = new \WC_Order_Query(
			[
				'limit'  => 2,
				'return' => 'ids',
			]
		);
		$query->set( 'customer', $customer_email );
		$orders = $query->get_orders();
		return count( $orders ) === 1 ? true : false;
	}
}
