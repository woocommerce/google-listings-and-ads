/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './css/index.scss';
import GetStartedPage from './get-started-page';
import SetupMC from './setup-mc';
import SetupAds from './setup-ads';
import Dashboard from './dashboard';
import EditFreeCampaign from './edit-free-campaign';
import EditPaidAdsCampaign from './pages/edit-paid-ads-campaign';
import CreatePaidAdsCampaign from './pages/create-paid-ads-campaign';
import { ProgramsReport, ProductsReport } from './reports';
import ProductFeed from './product-feed';
import Settings from './settings';
import './data';

addFilter(
	'woocommerce_admin_pages_list',
	'woocommerce-marketing',
	( pages ) => {
		const initialBreadcrumbs = [
			[ '', wcSettings.woocommerceTranslation ],
		];

		/**
		 * If the WooCommerce Navigation feature is not enabled,
		 * we want to display the plugin under WC Marketing;
		 * otherwise, display it under WC Navigation - Extensions.
		 */
		if ( window.wcAdminFeatures?.navigation !== true ) {
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
				title: __( 'Google Listings & Ads', 'google-listings-and-ads' ),
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
				title: __( 'Dashboard', 'google-listings-and-ads' ),
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
					__( 'Edit Free Listings', 'google-listings-and-ads' ),
				],
				title: __( 'Edit Free Listings', 'google-listings-and-ads' ),
				container: EditFreeCampaign,
				path: '/google/edit-free-campaign',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Edit Paid Ads Campaign', 'google-listings-and-ads' ),
				],
				title: __(
					'Edit Paid Ads Campaign',
					'google-listings-and-ads'
				),
				container: EditPaidAdsCampaign,
				path: '/google/campaigns/edit',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__(
						'Create your free campaign',
						'google-listings-and-ads'
					),
				],
				title: __(
					'Create your free campaign',
					'google-listings-and-ads'
				),
				container: CreatePaidAdsCampaign,
				path: '/google/campaigns/create',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Programs Report', 'google-listings-and-ads' ),
				],
				title: __( 'Programs Report', 'google-listings-and-ads' ),
				container: ProgramsReport,
				path: '/google/reports/programs',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Products Report', 'google-listings-and-ads' ),
				],
				title: __( 'Products Report', 'google-listings-and-ads' ),
				container: ProductsReport,
				path: '/google/reports/products',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Product Feed', 'google-listings-and-ads' ),
				],
				title: __( 'Product Feed', 'google-listings-and-ads' ),
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
				title: __( 'Settings', 'google-listings-and-ads' ),
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
