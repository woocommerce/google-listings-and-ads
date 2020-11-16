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
		];
	}
);
