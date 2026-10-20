/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSubNav from '~/components/app-sub-nav';
import getSelectedReportKey from '~/utils/getSelectedReportKey';
import useIsOrganicSearchReportAvailable from './search-console/hooks/useIsOrganicSearchReportAvailable';

const tabs = [
	{
		key: 'programs',
		title: __( 'Programs', 'google-listings-and-ads' ),
		href: getNewPath( { reportKey: 'programs' }, '/google/reports', {} ),
	},
	{
		key: 'products',
		title: __( 'Products', 'google-listings-and-ads' ),
		href: getNewPath( { reportKey: 'products' }, '/google/reports', {} ),
	},
];

const searchConsoleTab = {
	key: 'search-console',
	title: __( 'Organic search', 'google-listings-and-ads' ),
	href: getNewPath( { reportKey: 'search-console' }, '/google/reports', {} ),
};

const ReportsNavigation = () => {
	const isOrganicSearchAvailable = useIsOrganicSearchReportAvailable();
	const reportKey = getSelectedReportKey( isOrganicSearchAvailable );
	const availableTabs = isOrganicSearchAvailable
		? [ ...tabs, searchConsoleTab ]
		: tabs;

	return <AppSubNav tabs={ availableTabs } selectedKey={ reportKey } />;
};

export default ReportsNavigation;
