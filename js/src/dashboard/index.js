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
import './index.scss';

const Dashboard = () => {
	const trackEventReportId = 'dashboard';

	return (
		<div className="gla-dashboard">
			<TabNav initialName="dashboard" />
			<div className="gla-dashboard__filter">
				<AppDateRangeFilterPicker
					trackEventReportId={ trackEventReportId }
				/>
				<Link href={ getNewPath( null, '/google/reports/programs' ) }>
					<Button isPrimary>View Reports</Button>
				</Link>
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
