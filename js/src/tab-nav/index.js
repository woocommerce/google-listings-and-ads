/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppTabNav from '.~/components/app-tab-nav';

let tabs = [
	{
		name: 'dashboard',
		title: __( 'Dashboard', 'google-listings-and-ads' ),
		path: '/google/dashboard',
	},
	{
		name: 'reports',
		title: __( 'Reports', 'google-listings-and-ads' ),
		path: '/google/reports/programs',
	},
	{
		name: 'product-feed',
		title: __( 'Product Feed', 'google-listings-and-ads' ),
		path: '/google/product-feed',
	},
	{
		name: 'settings',
		title: __( 'Settings', 'google-listings-and-ads' ),
		path: '/google/settings',
	},
];

// Hide reports tab.
if ( ! glaData.enableReports ) {
	tabs = tabs.filter( ( { name } ) => name !== 'reports' );
}

const TabNav = () => {
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

	const path = getPath();
	const selectedName = tabs.find( ( el ) => el.path === path )?.name;

	return <AppTabNav tabs={ tabs } initialName={ selectedName } />;
};

export default TabNav;
