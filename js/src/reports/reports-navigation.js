/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTabNav from '.~/components/app-tab-nav';
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';
import AppSubNav from '.~/components/app-sub-nav';
import getSelectedReportKey from '.~/utils/getSelectedReportKey';

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

const ReportsNavigation = () => {
	const navigationEnabled = isWCNavigationEnabled();
	const reportKey = getSelectedReportKey();

	return navigationEnabled ? (
		<AppTabNav tabs={ tabs } selectedKey={ reportKey } />
	) : (
		<AppSubNav tabs={ tabs } selectedKey={ reportKey } />
	);
};

export default ReportsNavigation;
