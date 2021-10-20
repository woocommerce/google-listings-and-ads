/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath, getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppTabNav from '.~/components/app-tab-nav';
import useLegacyMenuEffect from '.~/hooks/useLegacyMenuEffect';

let tabs = [
	{
		key: 'dashboard',
		title: __( 'Dashboard', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/dashboard', {} ),
	},
	{
		key: 'reports',
		title: __( 'Reports', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/reports', {} ),
	},
	{
		key: 'product-feed',
		title: __( 'Product Feed', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/product-feed', {} ),
	},
	{
		key: 'settings',
		title: __( 'Settings', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/settings', {} ),
	},
];

// Hide reports tab.
if ( ! glaData.enableReports ) {
	tabs = tabs.filter( ( { key } ) => key !== 'reports' );
}

const getSelectedTabKey = () => {
	const path = getPath();

	return tabs.find( ( el ) => path.includes( el.key ) )?.key;
};

const MainTabNav = () => {
	useLegacyMenuEffect();

	const selectedKey = getSelectedTabKey();

	return <AppTabNav tabs={ tabs } selectedKey={ selectedKey } />;
};

export default MainTabNav;
