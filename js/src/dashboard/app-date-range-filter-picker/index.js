/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import getDateQuery from './getDateQuery';

const isoDateFormat = 'YYYY-MM-DD';

const AppDateRangeFilterPicker = () => {
	const dateQuery = getDateQuery();

	const handleRangeSelect = ( data ) => {
		updateQueryString( data );
	};

	return (
		<DateRangeFilterPicker
			dateQuery={ dateQuery }
			onRangeSelect={ handleRangeSelect }
			isoDateFormat={ isoDateFormat }
		/>
	);
};

export default AppDateRangeFilterPicker;
