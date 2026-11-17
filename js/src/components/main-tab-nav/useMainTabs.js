/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath, getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useReportsAvailability from '~/hooks/useReportsAvailability';

export const ALL_TABS = [
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
		key: 'price-benchmark',
		title: __( 'Price Benchmark', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/price-benchmark', {} ),
	},
	{
		key: 'attribute-mapping',
		title: __( 'Attributes', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/attribute-mapping', {} ),
	},
	{
		key: 'markets',
		title: __( 'Markets', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/markets', {} ),
	},
	{
		key: 'settings',
		title: __( 'Settings', 'google-listings-and-ads' ),
		href: getNewPath( {}, '/google/settings', {} ),
	},
];

const NO_MC_TAB_KEYS = [ 'dashboard', 'reports', 'settings' ];

const getSelectedTabKey = ( allTabs ) => {
	const path = getPath();
	return allTabs.find( ( el ) => path.includes( el.key ) )?.key;
};

/**
 * @typedef {Object} MainTab
 * @property {string} key Tab key. It is also the last segment of the page path.
 * @property {string} title Translated tab label.
 * @property {string} href Link to the page.
 */

/**
 * Returns the main plugin tabs the merchant can see, and which one matches the current page.
 *
 * Tabs that need Merchant Center are hidden until it is connected, and Reports is
 * hidden when there is nothing to report.
 *
 * @return {{ tabs: Array<MainTab>, selectedKey: string|undefined }} Visible tabs and the selected tab key.
 */
const useMainTabs = () => {
	const { hasMC, canViewReports } = useReportsAvailability();

	const tabs = ALL_TABS.filter( ( { key } ) => {
		if ( ! canViewReports && key === 'reports' ) {
			return false;
		}

		if ( ! hasMC && ! NO_MC_TAB_KEYS.includes( key ) ) {
			return false;
		}

		return true;
	} );

	return { tabs, selectedKey: getSelectedTabKey( tabs ) };
};

export default useMainTabs;
