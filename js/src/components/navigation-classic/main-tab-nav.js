/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getNewPath, getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppTabNav from '.~/components/app-tab-nav';

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
	useEffect( () => {
		// Highlight the wp-admin dashboard menu
		const marketingMenu = document.querySelector(
			'#toplevel_page_woocommerce-marketing'
		);

		if ( ! marketingMenu ) {
			return;
		}

		const dashboardLink = marketingMenu.querySelector(
			"a[href^='admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard']"
		);

		marketingMenu.classList.add( 'current', 'wp-has-current-submenu' );
		if ( dashboardLink ) {
			dashboardLink.parentElement.classList.add( 'current' );
		}
	}, [] );

	const selectedKey = getSelectedTabKey();

	return <AppTabNav tabs={ tabs } selectedKey={ selectedKey } />;
};

export default MainTabNav;
