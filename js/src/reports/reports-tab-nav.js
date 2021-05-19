/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTabNav from '.~/components/app-tab-nav';

const tabs = [
	{
		key: 'programs',
		title: __( 'Programs', 'google-listings-and-ads' ),
		path: '/google/reports/programs',
	},
	{
		key: 'products',
		title: __( 'Products', 'google-listings-and-ads' ),
		path: '/google/reports/products',
	},
];

const ReportsTabNav = () => {
	const path = getPath();
	const selectedKey = tabs.find( ( el ) => el.path === path )?.key;

	return <AppTabNav tabs={ tabs } selectedKey={ selectedKey } />;
};

export default ReportsTabNav;
