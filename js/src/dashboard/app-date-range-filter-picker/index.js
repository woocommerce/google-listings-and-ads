/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import getDateQuery from './getDateQuery';
import { recordDatepickerUpdateEvent } from '.~/utils/recordEvent';

const isoDateFormat = 'YYYY-MM-DD';

/**
 * Renders DateRangeFilterPicker, handles range selection,
 * fires applicable track events.
 *
 * @param {Object} props
 * @param {string} [props.trackEventReportId] An id to be used as `report` propert in fired events.
 * 												If not given, no track event will be fired.
 *
 * @return {import('@woocommerce/components').DateRangeFilterPicker} Customized DateRangeFilterPicker.
 *
 * @fires gla_datepicker_update
 */
const AppDateRangeFilterPicker = ( props ) => {
	const { trackEventReportId } = props;
	const dateQuery = getDateQuery();

	/**
	 * Handles DateRangeFilterPicker range changes.
	 *
	 * Reports `recordDatepickerUpdateEvent` with props.trackEventReportId
	 * and `data` given by `onRangeSelect` callback.
	 * Updates query string with selected data.
	 *
	 * @param {Object} data Data given by `DateRangeFilterPicker`'s `onRangeSelect` callback.
	 * @param {string} data.compare
	 * @param {string} data.period
	 * @param {string} data.after
	 * @param {string} data.before
	 */
	const handleRangeSelect = ( data ) => {
		if ( trackEventReportId ) {
			recordDatepickerUpdateEvent( {
				report: trackEventReportId,
				...data,
			} );
		}
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
