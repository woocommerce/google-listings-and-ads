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
import useReportsAvailability from '~/hooks/useReportsAvailability';

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
	const { hasMC } = useReportsAvailability();

	// The Products report needs a product feed, which comes from Merchant Center.
	if ( ! hasMC ) {
		return (
			<AppSubNav
				tabs={ tabs.filter( ( { key } ) => key !== 'products' ) }
				selectedKey="programs"
			/>
		);
	}

	return <AppSubNav tabs={ tabs } selectedKey={ getSelectedReportKey() } />;
};

export default ReportsNavigation;
