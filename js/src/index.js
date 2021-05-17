/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { WooNavigationItem, getNewPath } from '@woocommerce/navigation';
import { registerPlugin } from '@wordpress/plugins';
import { Link } from '@woocommerce/components';

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
		return [
			...pages,
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Google Listings & Ads', 'google-listings-and-ads' ),
				container: GetStartedPage,
				path: '/google/start',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				// navArgs: {
				// 	id: 'google-start',
				// },
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					__( 'Setup Merchant Center', 'google-listings-and-ads' ),
				],
				container: SetupMC,
				path: '/google/setup-mc',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					__( 'Setup Google Ads', 'google-listings-and-ads' ),
				],
				container: SetupAds,
				path: '/google/setup-ads',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Dashboard', 'google-listings-and-ads' ),
				container: Dashboard,
				path: '/google/dashboard',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				// navArgs: {
				// 	id: 'google-dashboard',
				// },
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Edit Free Listings', 'google-listings-and-ads' ),
				container: EditFreeCampaign,
				path: '/google/edit-free-campaign',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
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
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
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
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Programs Report', 'google-listings-and-ads' ),
				container: ProgramsReport,
				path: '/google/reports/programs',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Products Report', 'google-listings-and-ads' ),
				container: ProductsReport,
				path: '/google/reports/products',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Product Feed', 'google-listings-and-ads' ),
				container: ProductFeed,
				path: '/google/product-feed',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				// navArgs: {
				// 	id: 'google-product-feed',
				// },
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/marketing',
						__( 'Marketing', 'google-listings-and-ads' ),
					],
					__( 'Google Listings & Ads', 'google-listings-and-ads' ),
				],
				title: __( 'Settings', 'google-listings-and-ads' ),
				container: Settings,
				path: '/google/settings',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
				// navArgs: {
				// 	id: 'google-settings',
				// },
			},
		];
	}
);

const MyExtenstionNavItem = () => {
	return (
		<>
			<WooNavigationItem item="google-start">
				<Link
					className="components-button"
					href={ getNewPath( {}, '/google/start', {} ) }
					type="wc-admin"
				>
					{ __( 'Google Listings & Ads', 'google-listings-and-ads' ) }
				</Link>
			</WooNavigationItem>
			<WooNavigationItem item="google-dashboard">
				<Link
					className="components-button"
					href={ getNewPath( {}, '/google/dashboard', {} ) }
					type="wc-admin"
				>
					{ __( 'Dashboard', 'google-listings-and-ads' ) }
				</Link>
			</WooNavigationItem>
			<WooNavigationItem item="google-product-feed">
				<Link
					className="components-button"
					href={ getNewPath( {}, '/google/product-feed', {} ) }
					type="wc-admin"
				>
					{ __( 'Product Feed', 'google-listings-and-ads' ) }
				</Link>
			</WooNavigationItem>
			<WooNavigationItem item="google-settings">
				<Link
					className="components-button"
					href={ getNewPath( {}, '/google/settings', {} ) }
					type="wc-admin"
				>
					{ __( 'Settings', 'google-listings-and-ads' ) }
				</Link>
			</WooNavigationItem>
		</>
	);
};

registerPlugin( 'google-listings-and-ads', {
	render: MyExtenstionNavItem,
} );
