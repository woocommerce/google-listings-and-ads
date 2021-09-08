/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import DifferentCurrencyNotice from '.~/components/different-currency-notice';
import NavigationClassic from '.~/components/navigation-classic';
import AppDateRangeFilterPicker from './app-date-range-filter-picker';
import SummarySection from './summary-section';
import CampaignCreationSuccessGuide from './campaign-creation-success-guide';
import AllProgramsTableCard from './all-programs-table-card';
import { glaData } from '.~/constants';
import './index.scss';
import { subpaths } from '.~/utils/urls';
import EditFreeCampaign from '.~/edit-free-campaign';
import EditPaidAdsCampaign from '.~/pages/edit-paid-ads-campaign';
import CreatePaidAdsCampaign from '.~/pages/create-paid-ads-campaign';

const Dashboard = () => {
	const query = getQuery();
	switch ( query.subpath ) {
		case subpaths.editFreeListings:
			return <EditFreeCampaign />;
		case subpaths.editCampaign:
			return <EditPaidAdsCampaign />;
		case subpaths.createCampaign:
			return <CreatePaidAdsCampaign />;
	}

	const trackEventReportId = 'dashboard';
	const { enableReports } = glaData;

	const ReportsLink = () => {
		return (
			<Link href={ getNewPath( null, '/google/reports' ) }>
				<Button isPrimary>View Reports</Button>
			</Link>
		);
	};

	return (
		<div className="gla-dashboard">
			<DifferentCurrencyNotice context="dashboard" />
			<NavigationClassic />
			<div className="gla-dashboard__filter">
				<AppDateRangeFilterPicker
					trackEventReportId={ trackEventReportId }
				/>
				{ enableReports && <ReportsLink /> }
			</div>
			<div className="gla-dashboard__performance">
				<SummarySection />
			</div>
			<div className="gla-dashboard__programs">
				<CampaignCreationSuccessGuide />
				<AllProgramsTableCard
					trackEventReportId={ trackEventReportId }
				/>
			</div>
		</div>
	);
};

export default Dashboard;
