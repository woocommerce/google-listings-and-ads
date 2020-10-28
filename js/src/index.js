/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './index.scss';
import ConnectAccountPage from './connect-account-page';

addFilter(
	'woocommerce_admin_pages_list',
	'woocommerce-marketing',
	( pages ) => {
		return [
			...pages,
			{
				breadcrumbs: [
					[ '', wcSettings.woocommerceTranslation ],
					['/marketing', __( 'Marketing', 'google-for-woocommerce' ) ],
					__( 'Connect', 'google-for-woocommerce' ) ],
				title: __( 'Connect', 'google-for-woocommerce' ),
				container: ConnectAccountPage,
				path: '/google/connect',
				wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			},
		];
	},
);
