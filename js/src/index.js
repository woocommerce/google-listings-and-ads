/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './index.scss';
import GetStartedPage from './get-started-page';
import SetupMC from './setup-mc';
import SetupAds from './setup-ads';

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
					[ '/setup-mc', __( 'Setup Merchant Center', 'google-listings-and-ads' ) ],
				],
				container: SetupMC,
				path: '/setup-mc',
			},
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					[ '/setup-ads', __( 'Setup Google Ads', 'google-listings-and-ads' ) ],
				],
				container: SetupAds,
				path: '/setup-ads',
			},
		];
	}
);
