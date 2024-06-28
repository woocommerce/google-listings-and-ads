/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { lazy } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import './css/index.scss';
import withAdminPageShell from '.~/components/withAdminPageShell';
import './data';
import isWCNavigationEnabled from './utils/isWCNavigationEnabled';
import { addBaseEventProperties } from '.~/utils/tracks';

const Dashboard = lazy( () =>
	import( /* webpackChunkName: "dashboard" */ './dashboard' )
);

const GetStartedPage = lazy( () =>
	import( /* webpackChunkName: "get-started-page" */ './get-started-page' )
);

const SetupMC = lazy( () =>
	import( /* webpackChunkName: "setup-mc" */ './setup-mc' )
);

const SetupAds = lazy( () =>
	import( /* webpackChunkName: "setup-ads" */ './setup-ads' )
);

const Reports = lazy( () =>
	import( /* webpackChunkName: "reports" */ './pages/reports' )
);

const ProductFeed = lazy( () =>
	import( /* webpackChunkName: "product-feed" */ './product-feed' )
);

const AttributeMapping = lazy( () =>
	import( /* webpackChunkName: "attribute-mapping" */ './attribute-mapping' )
);

const Settings = lazy( () =>
	import( /* webpackChunkName: "settings" */ './settings' )
);

export const pagePaths = new Set();

const woocommerceTranslation =
	getSetting( 'admin' )?.woocommerceTranslation ||
	__( 'WooCommerce', 'google-listings-and-ads' );

addFilter(
	'woocommerce_admin_pages_list',
	'woocommerce/google-listings-and-ads/add-page-routes',
	( pages ) => {
		const navigationEnabled = isWCNavigationEnabled();
		const initialBreadcrumbs = [ [ '', woocommerceTranslation ] ];

		/**
		 * If the WooCommerce Navigation feature is not enabled,
		 * we want to display the plugin under WC Marketing;
		 * otherwise, display it under WC Navigation - Extensions.
		 */
		if ( ! navigationEnabled ) {
			initialBreadcrumbs.push( [
				'/marketing',
				__( 'Marketing', 'google-listings-and-ads' ),
			] );
		}

		initialBreadcrumbs.push(
			__( 'Google for WooCommerce', 'google-listings-and-ads' )
		);

		const pluginAdminPages = [
			{
				breadcrumbs: [ ...initialBreadcrumbs ],
				container: GetStartedPage,
				path: '/google/start',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				navArgs: {
					id: 'google-start',
				},
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Setup Merchant Center', 'google-listings-and-ads' ),
				],
				container: SetupMC,
				path: '/google/setup-mc',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Setup Google Ads', 'google-listings-and-ads' ),
				],
				container: SetupAds,
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
				navArgs: {
					id: 'google-dashboard',
				},
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Reports', 'google-listings-and-ads' ),
				],
				container: Reports,
				path: '/google/reports',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				navArgs: {
					id: 'google-reports',
				},
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Product Feed', 'google-listings-and-ads' ),
				],
				container: ProductFeed,
				path: '/google/product-feed',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				navArgs: {
					id: 'google-product-feed',
				},
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Attribute Mapping', 'google-listings-and-ads' ),
				],
				container: AttributeMapping,
				path: '/google/attribute-mapping',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				navArgs: {
					id: 'google-attribute-mapping',
				},
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Settings', 'google-listings-and-ads' ),
				],
				container: Settings,
				path: '/google/settings',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				navArgs: {
					id: 'google-settings',
				},
			},
		];

		pluginAdminPages.forEach( ( page ) => {
			page.container = withAdminPageShell( page.container );

			// Do the same thing as https://github.com/woocommerce/woocommerce/blob/6.9.0/plugins/woocommerce-admin/client/layout/index.js#L178
			const path = page.path.substring( 1 ).replace( /\//g, '_' );
			pagePaths.add( path );
		} );

		return pages.concat( pluginAdminPages );
	}
);

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
