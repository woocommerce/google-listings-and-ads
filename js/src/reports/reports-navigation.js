/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
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
	const reportKey = getSelectedReportKey();

	return <AppSubNav tabs={ tabs } selectedKey={ reportKey } />;
};

export default ReportsNavigation;
