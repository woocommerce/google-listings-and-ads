/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import getDateQuery from './getDateQuery';

const isoDateFormat = 'YYYY-MM-DD';

const Dashboard = () => {
	const dateQuery = getDateQuery();

	const handleRangeSelect = ( data ) => {
		updateQueryString( data );
	};

	return (
		<div>
			<TabNav initialName="dashboard" />
			<DateRangeFilterPicker
				dateQuery={ dateQuery }
				onRangeSelect={ handleRangeSelect }
				isoDateFormat={ isoDateFormat }
			/>
		</div>
	);
};

export default Dashboard;
