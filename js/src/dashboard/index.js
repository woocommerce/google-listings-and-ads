/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import AppDateRangeFilterPicker from './app-date-range-filter-picker';

const Dashboard = () => {
	return (
		<div>
			<TabNav initialName="dashboard" />
			<AppDateRangeFilterPicker />
		</div>
	);
};

export default Dashboard;
