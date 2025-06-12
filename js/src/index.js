/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { lazy } from '@wordpress/element';
import { addFilter, didFilter, hasAction } from '@wordpress/hooks';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import './css/index.scss';
import withAdminPageShell from '~/components/withAdminPageShell';
import './data';
import { addBaseEventProperties } from '~/utils/tracks';

const Dashboard = lazy( () =>
	import( /* webpackChunkName: "dashboard" */ './pages/dashboard' )
);

const GetStartedPage = lazy( () =>
	import( /* webpackChunkName: "get-started-page" */ './pages/get-started' )
);

const Onboarding = lazy( () =>
	import( /* webpackChunkName: "onboarding" */ './pages/onboarding' )
);

const AdsOnboarding = lazy( () =>
	import( /* webpackChunkName: "ads-onboarding" */ './pages/ads-onboarding' )
);

const Reports = lazy( () =>
	import( /* webpackChunkName: "reports" */ './pages/reports' )
);

const ProductFeed = lazy( () =>
	import( /* webpackChunkName: "product-feed" */ './pages/product-feed' )
);

const AttributeMapping = lazy( () =>
	import(
		/* webpackChunkName: "attribute-mapping" */ './pages/attribute-mapping'
	)
);

const PriceBenchmark = lazy( () =>
	import(
		/* webpackChunkName: "price-benchmark" */ './pages/price-benchmark'
	)
);

const Settings = lazy( () =>
	import( /* webpackChunkName: "settings" */ './pages/settings' )
);

const Shipping = lazy( () =>
	import( /* webpackChunkName: "shipping" */ './pages/shipping' )
);

export const pagePaths = new Set();

const woocommerceTranslation =
	getSetting( 'admin' )?.woocommerceTranslation ||
	__( 'WooCommerce', 'google-listings-and-ads' );

// Refer to https://github.com/woocommerce/woocommerce/blob/9.7.1/plugins/woocommerce/client/admin/client/layout/controller.js#L82
const PAGES_FILTER = 'woocommerce_admin_pages_list';

let hasAddedPluginAdminPages = false;

const registerPluginAdminPages = () => {
	const namespace = 'woocommerce/google-listings-and-ads/add-page-routes';

	addFilter( PAGES_FILTER, namespace, ( pages ) => {
		const initialBreadcrumbs = [
			[ '', woocommerceTranslation ],
			[ '/marketing', __( 'Marketing', 'google-listings-and-ads' ) ],
			__( 'Google for WooCommerce', 'google-listings-and-ads' ),
		];

		const pluginAdminPages = [
			{
				breadcrumbs: [ ...initialBreadcrumbs ],
				container: GetStartedPage,
				path: '/google/start',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Setup Merchant Center', 'google-listings-and-ads' ),
				],
				container: Onboarding,
				path: '/google/setup-mc',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Setup Google Ads', 'google-listings-and-ads' ),
				],
				container: AdsOnboarding,
				path: '/google/setup-ads',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Dashboard', 'google-listings-and-ads' ),
				],
				container: Dashboard,
				path: '/google/dashboard',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Reports', 'google-listings-and-ads' ),
				],
				container: Reports,
				path: '/google/reports',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Product Feed', 'google-listings-and-ads' ),
				],
				container: ProductFeed,
				path: '/google/product-feed',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Price Benchmark', 'google-listings-and-ads' ),
				],
				container: PriceBenchmark,
				path: '/google/price-benchmark',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Attribute Mapping', 'google-listings-and-ads' ),
				],
				container: AttributeMapping,
				path: '/google/attribute-mapping',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Settings', 'google-listings-and-ads' ),
				],
				container: Settings,
				path: '/google/settings',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Shipping', 'google-listings-and-ads' ),
				],
				container: Shipping,
				path: '/google/shipping',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
		];

		pluginAdminPages.forEach( ( page ) => {
			page.container = withAdminPageShell( page.container );

			// Do the same thing as https://github.com/woocommerce/woocommerce/blob/6.9.0/plugins/woocommerce-admin/client/layout/index.js#L178
			const path = page.path.substring( 1 ).replace( /\//g, '_' );
			pagePaths.add( path );
		} );

		hasAddedPluginAdminPages = true;

		return pages.concat( pluginAdminPages );
	} );
};

const hasRunFilter = () => didFilter( PAGES_FILTER ) > 0;
const hasAddedFallback = () =>
	hasAction( 'hookAdded', `woocommerce/woocommerce/watch_${ PAGES_FILTER }` );

/* compatibility-code "WP >= 6.8 && WC >= 9.8" -- Ensure the registration of the plugin admin pages is applied to the filter
 *
 * Starting with WooCommerce >= 9.8, this script will run after the 'wc-admin-app'
 * script instead of before it. Originally, this was not a problem, because
 * WooCommerce Core has a fallback to handle this run order.
 * Ref: https://github.com/woocommerce/woocommerce/blob/9.7.1/plugins/woocommerce/client/admin/client/layout/controller.js#L358-L382
 *
 * The mechanism works when these hooks run in the following order:
 * 1. WC runs `applyFilters` to get pages
 * 2. WC runs `addAction` to set up a fallback to handle filters being added
 *    after the applied filter above
 * 3. This plugin runs `addFilter` to add its pages and the above fallback will
 *    take care of this lately added filter
 *
 * However, since WordPress >= 6.8, somehow there is a chance that these hooks
 * will run in the following order:
 * 1. WC runs `applyFilters`
 * 2. This plugin runs `addFilter`
 * 3. WC runs `addAction` to set up a fallback
 *
 * In this case, pages registration will be missed and won't be displayed.
 * So here's a compatibility process to ensure this plugin runs `addFilter`
 * after the fallback is set.
 */
if ( hasRunFilter() && ! hasAddedFallback() && ! hasAddedPluginAdminPages ) {
	const startTime = Date.now();
	const timerId = setInterval( () => {
		if ( hasAddedFallback() ) {
			clearInterval( timerId );
			registerPluginAdminPages();
			return;
		}

		// Stop trying after 3 seconds to avoid performance issues.
		if ( Date.now() - startTime > 3000 ) {
			clearInterval( timerId );
		}
	}, 10 );
} else {
	registerPluginAdminPages();
}

// Ref: https://github.com/woocommerce/woocommerce/blob/6.9.0/plugins/woocommerce/includes/tracks/class-wc-site-tracking.php#L92
addFilter(
	'woocommerce_tracks_client_event_properties',
	'woocommerce/google-listings-and-ads/add-base-event-properties-to-page-view',
	( eventProperties, eventName ) => {
		if (
			eventName === 'wcadmin_page_view' &&
			pagePaths.has( eventProperties.path )
		) {
			return addBaseEventProperties( eventProperties );
		}

		return eventProperties;
	}
);
