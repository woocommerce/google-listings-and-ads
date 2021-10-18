/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import './public-path';
import './css/index.scss';
import GetStartedPage from './get-started-page';
import SetupMC from './setup-mc';
import SetupAds from './setup-ads';
import Dashboard from './dashboard';
import Reports from './pages/reports';
import ProductFeed from './product-feed';
import Settings from './settings';
import './data';
import isWCNavigationEnabled from './utils/isWCNavigationEnabled';

const woocommerceTranslation = getSetting( 'woocommerceTranslation' );

addFilter(
	'woocommerce_admin_pages_list',
	'woocommerce-marketing',
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
			__( 'Google Listings & Ads', 'google-listings-and-ads' )
		);

		return [
			...pages,
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
	}
);
