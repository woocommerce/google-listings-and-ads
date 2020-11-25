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
import Analytics from './analytics';
import ProductFeed from './product-feed';
import Settings from './settings';

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
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/google/setup-mc',
						__(
							'Setup Merchant Center',
							'google-listings-and-ads'
						),
					],
				],
				container: SetupMC,
				path: '/google/setup-mc',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[
						'/google/setup-ads',
						__( 'Setup Google Ads', 'google-listings-and-ads' ),
					],
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
				title: __( 'Google Listings & Ads', 'google-listings-and-ads' ),
				container: Dashboard,
				path: '/google/dashboard',
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
				title: __( 'Analytics', 'google-listings-and-ads' ),
				container: Analytics,
				path: '/google/analytics',
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
			},
		];
	}
);
