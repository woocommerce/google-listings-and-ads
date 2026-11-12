/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath, getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useReportsAvailability from '~/hooks/useReportsAvailability';
import AppTabNav from '~/components/app-tab-nav';
import useMenuEffect from '~/hooks/useMenuEffect';
import GtinMigrationBanner from '~/components/gtin-migration-banner';

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

const MainTabNav = () => {
	useMenuEffect();

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

	const selectedKey = getSelectedTabKey( tabs );

	return (
		<>
			<GtinMigrationBanner />
			<AppTabNav tabs={ tabs } selectedKey={ selectedKey } />
		</>
	);
};
export default MainTabNav;
