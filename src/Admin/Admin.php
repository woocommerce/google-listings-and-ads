<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Automattic\WooCommerce\GoogleListingsAndAds\View\ViewException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\ScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\ChannelVisibilityMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class Admin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Pages
 */
class Admin implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * @var ViewFactory
	 */
	protected $view_factory;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var AdsService
	 */
	protected $ads;

	/**
	 * @var OnboardingCompleted
	 */
	protected $onboarding_completed;

	/**
	 * @var ServiceBasedMerchantState
	 */
	protected $service_based_merchant_state;

	/**
	 * Admin constructor.
	 *
	 * @param AssetsHandlerInterface    $assets_handler
	 * @param ViewFactory               $view_factory
	 * @param MerchantCenterService     $merchant_center
	 * @param AdsService                $ads
	 * @param OnboardingCompleted       $onboarding_completed
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, ViewFactory $view_factory, MerchantCenterService $merchant_center, AdsService $ads, OnboardingCompleted $onboarding_completed, ServiceBasedMerchantState $service_based_merchant_state ) {
		$this->assets_handler               = $assets_handler;
		$this->view_factory                 = $view_factory;
		$this->merchant_center              = $merchant_center;
		$this->ads                          = $ads;
		$this->onboarding_completed         = $onboarding_completed;
		$this->service_based_merchant_state = $service_based_merchant_state;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_enqueue_scripts',
			function () {
				if ( PageController::is_admin_page() ) {
					// Enqueue the required JavaScript scripts and CSS styles of the Media library.
					wp_enqueue_media();
				}

				$assets = $this->get_assets();

				$this->assets_handler->register_many( $assets );
				$this->assets_handler->enqueue_many( $assets );
			}
		);

		add_action(
			"plugin_action_links_{$this->get_plugin_basename()}",
			function ( $links ) {
				return $this->add_plugin_links( $links );
			}
		);

		add_action(
			'wp_default_scripts',
			function ( $scripts ) {
				$this->inject_fast_refresh_for_dev( $scripts );
			},
			20
		);

		add_action( 'admin_init', [ $this, 'privacy_policy' ] );
	}

	/**
	 * Return an array of assets.
	 *
	 * @return Asset[]
	 */
	protected function get_assets(): array {
		$wc_admin_condition = function () {
			return PageController::is_admin_page();
		};

		$build_dir = "{$this->get_root_dir()}/js/build";
		$assets[]  = ( new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads',
			'js/build/index',
			"{$build_dir}/index.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/index.js" ),
				]
			),
			$wc_admin_condition
		) )->add_inline_script(
			'glaData',
			[
				'slug'                     => $this->get_slug(),
				'mcSetupComplete'          => $this->merchant_center->is_setup_complete(),
				'mcSupportedCountry'       => $this->merchant_center->is_store_country_supported(),
				'mcSupportedLanguage'      => $this->merchant_center->is_language_supported(),
				'adsCampaignConvertStatus' => $this->options->get( OptionsInterface::CAMPAIGN_CONVERT_STATUS ),
				'adsSetupComplete'         => $this->ads->is_setup_complete(),
				'onboardingComplete'       => $this->onboarding_completed->is_onboarding_complete(),
				'enableReports'            => $this->enableReports(),
				'dateFormat'               => get_option( 'date_format' ),
				'timeFormat'               => get_option( 'time_format' ),
				'siteLogoUrl'              => wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ),
				'serviceBasedMerchant'     => $this->service_based_merchant_state->is_service_based_merchant(),
				'initialWpData'            => [
					'version' => $this->get_version(),
					'mcId'    => $this->options->get_merchant_id() ?: null,
					'adsId'   => $this->options->get_ads_id() ?: null,
				],
				'dataViewsScriptUrl'       => add_query_arg(
					[
						'version' => (string) filemtime( "{$this->get_root_dir()}/js/build/wp-dataviews-shim.js" ),
					],
					(
						new ScriptAsset(
							'gla-data-views-shim',
							'js/build/wp-dataviews-shim',
							[],
							(string) filemtime( "{$this->get_root_dir()}/js/build/wp-dataviews-shim.js" ),
						)
					)->get_uri(),
				),
			]
		);

		$assets[] = ( new AdminStyleAsset(
			'google-listings-and-ads-css',
			'/js/build/index',
			defined( 'WC_ADMIN_PLUGIN_FILE' ) ? [ 'wc-admin-app' ] : [],
			(string) filemtime( "{$this->get_root_dir()}/js/build/index.css" ),
			$wc_admin_condition
		) );

		$product_condition = function () {
			$screen = get_current_screen();
			return ( null !== $screen && 'product' === $screen->id );
		};

		$assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'gla-product-attributes',
			'js/build/product-attributes',
			"{$build_dir}/product-attributes.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/product-attributes.js" ),
				]
			),
			$product_condition
		) )->add_inline_script(
			'glaProductData',
			[
				'applicableProductTypes' => ProductSyncer::get_supported_product_types(),
			]
		);

		$assets[] = ( new AdminStyleAsset(
			'gla-product-attributes-css',
			'js/build/product-attributes',
			[],
			'',
			$product_condition
		) );

		$assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'gla-order-attribution',
			'js/build/order-attribution',
			"{$build_dir}/order-attribution.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/order-attribution.js" ),
				]
			),
			function (): bool {
				return $this->is_wc_order_edit_screen();
			}
		) )->add_inline_script(
			'glaData',
			[
				'slug'                   => $this->get_slug(),
				'adsSetupComplete'       => $this->ads->is_setup_complete(),
				'initialWpData'          => [
					'version' => $this->get_version(),
					'mcId'    => $this->options->get_merchant_id() ?: null,
					'adsId'   => $this->options->get_ads_id() ?: null,
				],
				'channelVisibility'      => $this->get_channel_visibility_data(),
				'orderAttributionSource' => $this->get_order_attribution_source_for_edit_screen(),
				'serviceBasedMerchant'   => $this->service_based_merchant_state->is_service_based_merchant(),
			]
		);

		$assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'gla-wc-product',
			'js/build/channel-visibility-meta-box',
			"{$this->get_root_dir()}/js/build/channel-visibility-meta-box.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/channel-visibility-meta-box.js" ),
				]
			),
			function (): bool {
				return $this->is_wc_product_edit_screen();
			}
		) )->add_inline_script(
			'glaData',
			[
				'slug'              => $this->get_slug(),
				'adsSetupComplete'  => $this->ads->is_setup_complete(),
				'initialWpData'     => [
					'version' => $this->get_version(),
					'mcId'    => $this->options->get_merchant_id() ?: null,
					'adsId'   => $this->options->get_ads_id() ?: null,
				],
				'channelVisibility' => $this->get_channel_visibility_data(),
			]
		);

		return $assets;
	}

	/**
	 * Get the order attribution source (utm_source) for the order currently being edited.
	 * Used only when the meta-boxes asset is loaded on the WooCommerce Edit Order screen.
	 *
	 * @return string|null The value persisted in the database (e.g. "google"), or null when not on order edit screen or no attribution.
	 */
	private function get_order_attribution_source_for_edit_screen(): ?string {
		if ( ! $this->is_wc_order_edit_screen() ) {
			return null;
		}

		// We use `id` when the setting for Order data storage (WooCommerce -> Settings -> Advanced -> Order data storage) is set to "High-performance order storage (recommended)".
		// We use `post` when the setting for Order data storage is set to "WordPress posts storage (legacy)".
		$order_id = 0;
		if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = absint( $_GET['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( 0 === $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$source = $order->get_meta( '_wc_order_attribution_utm_source', true );
		if ( $source === '' || $source === null ) {
			return null;
		}

		return (string) $source;
	}

	/**
	 * Check if the current screen is the WooCommerce orders edit screen.
	 *
	 * @return bool True if on the WC orders edit screen, false otherwise.
	 */
	protected function is_wc_order_edit_screen(): bool {
		if ( null === get_current_screen() ) {
			return false;
		}

		return OrderUtil::is_order_edit_screen( 'shop_order' );
	}

	/**
	 * Adds links to the plugin's row in the "Plugins" wp-admin page.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
	 * @param array $links The existing list of links that will be rendered.
	 */
	protected function add_plugin_links( $links ): array {
		$plugin_links = [];

		// Display settings url if setup is complete otherwise link to get started page
		if ( $this->onboarding_completed->is_onboarding_complete() ) {
			$plugin_links[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_attr( $this->get_settings_url() ),
				esc_html__( 'Settings', 'google-listings-and-ads' )
			);
		} else {
			$plugin_links[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_attr( $this->get_start_url() ),
				esc_html__( 'Get Started', 'google-listings-and-ads' )
			);
		}

		$plugin_links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_attr( $this->get_documentation_url() ),
			esc_html__( 'Documentation', 'google-listings-and-ads' )
		);

		// Add new links to the beginning
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Adds a meta box.
	 *
	 * @param MetaBoxInterface $meta_box
	 */
	public function add_meta_box( MetaBoxInterface $meta_box ) {
		add_filter(
			"postbox_classes_{$meta_box->get_screen()}_{$meta_box->get_id()}",
			function ( array $classes ) use ( $meta_box ) {
				return array_merge( $classes, $meta_box->get_classes() );
			}
		);

		add_meta_box(
			$meta_box->get_id(),
			$meta_box->get_title(),
			$meta_box->get_callback(),
			$meta_box->get_screen(),
			$meta_box->get_context(),
			$meta_box->get_priority(),
			$meta_box->get_callback_args()
		);
	}

	/**
	 * @param string $view              Name of the view
	 * @param array  $context_variables Array of variables to pass to the view
	 *
	 * @return string The rendered view
	 *
	 * @throws ViewException If the view doesn't exist or can't be loaded.
	 */
	public function get_view( string $view, array $context_variables = [] ): string {
		return $this->view_factory->create( $view )
							->render( $context_variables );
	}

	/**
	 * Only show reports if we enable it through a snippet.
	 *
	 * @return bool Whether reports should be enabled .
	 */
	protected function enableReports(): bool {
		return apply_filters( 'woocommerce_gla_enable_reports', true );
	}

	/**
	 * Add suggested privacy policy content
	 *
	 * @return void
	 */
	public function privacy_policy() {
		$policy_text = sprintf(
			/* translators: 1) HTML anchor open tag 2) HTML anchor closing tag */
			esc_html__( 'By using this extension, you may be storing personal data or sharing data with an external service. %1$sLearn more about what data is collected by Google and what you may want to include in your privacy policy%2$s.', 'google-listings-and-ads' ),
			'<a href="https://support.google.com/adspolicy/answer/54817" target="_blank">',
			'</a>'
		);

		// As the extension doesn't offer suggested privacy policy text, the button to copy it is hidden.
		$content = '
			<p class="privacy-policy-tutorial">' . $policy_text . '</p>
			<style>#privacy-settings-accordion-block-google-listings-ads .privacy-settings-accordion-actions { display: none }</style>';

		wp_add_privacy_policy_content( 'Google for WooCommerce', wpautop( $content, false ) );
	}

	/**
	 * This method is ONLY used during development.
	 *
	 * The runtime.js file is created when the front-end is developed in Fast Refresh mode
	 * and must be loaded together to enable the mode.
	 *
	 * When Gutenberg is not installed or not activated, the react dependency will not have
	 * the 'wp-react-refresh-entry' handle, so here injects the Fast Refresh scripts we built.
	 *
	 * The Fast Refresh also needs the development version of React and ReactDOM.
	 * They will be replaced if the SCRIPT_DEBUG flag is not enabled.
	 *
	 * @param WP_Scripts $scripts WP_Scripts instance.
	 */
	private function inject_fast_refresh_for_dev( $scripts ) {
		$runtime_path = "{$this->get_root_dir()}/js/build/runtime.js";

		if ( ! file_exists( $runtime_path ) ) {
			return;
		}

		$react_script = $scripts->query( 'react', 'registered' );

		if ( ! $react_script ) {
			return;
		}

		if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$react_dom_script      = $scripts->query( 'react-dom', 'registered' );
			$react_dom_script->src = str_replace( '.min', '', $react_dom_script->src );
			$react_script->src     = str_replace( '.min', '', $react_script->src );
		}

		$plugin_url = $this->get_plugin_url();

		$scripts->add(
			'gla-webpack-runtime',
			"{$plugin_url}/js/build/runtime.js",
			[],
			(string) filemtime( $runtime_path )
		);
		$react_script->deps[] = 'gla-webpack-runtime';

		if ( ! in_array( 'wp-react-refresh-entry', $react_script->deps, true ) ) {
			$scripts->add(
				'wp-react-refresh-runtime',
				"{$plugin_url}/js/build-dev/react-refresh-runtime.js",
				[]
			);
			$scripts->add(
				'wp-react-refresh-entry',
				"{$plugin_url}/js/build-dev/react-refresh-entry.js",
				[ 'wp-react-refresh-runtime' ]
			);
			$react_script->deps[] = 'wp-react-refresh-entry';
		}
	}

	/**
	 * Build channel visibility data for the current product edit screen.
	 *
	 * @return array
	 */
	protected function get_channel_visibility_data(): array {
		if ( ! $this->is_wc_product_edit_screen() ) {
			return [];
		}

		global $post;
		if ( ! $post || ! isset( $post->ID ) ) {
			return [];
		}

		try {
			$product_helper = \woogle_get_container()->get( ProductHelper::class );
			$meta_handler   = \woogle_get_container()->get( ProductMetaHandler::class );

			/** @var \WC_Product $product */
			$product = $product_helper->get_wc_product( absint( $post->ID ) );
			if ( ! $product ) {
				return [];
			}

			$field_id = sprintf( '%s_%s_%s', $this->get_slug(), ChannelVisibilityMetaBox::ID, ChannelVisibilityMetaBox::FIELD_VISIBILITY );

			return [
				'field_id'           => $field_id,
				'product_is_visible' => (bool) $product->is_visible(),
				'channel_visibility' => $product_helper->get_channel_visibility( $product ),
				'sync_status'        => $meta_handler->get_sync_status( $product ),
				'issues'             => $product_helper->get_validation_errors( $product ),
				'options'            => ChannelVisibility::get_value_options(),
			];
		} catch ( \Throwable $e ) {
			return [];
		}
	}

	/**
	 * Check if the current screen is a WooCommerce product edit screen.
	 *
	 * @return bool
	 */
	private function is_wc_product_edit_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return null !== $screen && 'product' === $screen->id;
	}
}
