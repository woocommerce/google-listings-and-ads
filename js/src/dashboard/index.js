/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import AppDateRangeFilterPicker from './app-date-range-filter-picker';
import SummarySection from './summary-section';
import CampaignCreationSuccessGuide from './campaign-creation-success-guide';
import AllProgramsTableCard from './all-programs-table-card';
import { glaData } from '.~/constants';
import './index.scss';

const Dashboard = () => {
	const trackEventReportId = 'dashboard';
	const { enableReports } = glaData;
	const ReportsLink = () => {
		return (
			<Link href={ getNewPath( null, '/google/reports/programs' ) }>
				<Button isPrimary>View Reports</Button>
			</Link>
		);
	};

	return (
		<div className="gla-dashboard">
			<TabNav initialName="dashboard" />
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
