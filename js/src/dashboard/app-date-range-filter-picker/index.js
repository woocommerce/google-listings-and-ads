/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import getDateQuery from './getDateQuery';

const isoDateFormat = 'YYYY-MM-DD';

/**
 * Renders `DateRangeFilterPicker`, handles range selection,
 * fires applicable track events.
 *
 * @param {Object} props
 * @param {string} [props.trackEventReportId] An id to be used as `report` propert in fired events.
 * 												If not given, no track event will be fired.
 *
 * @return {import('@woocommerce/components').DateRangeFilterPicker} Customized DateRangeFilterPicker.
 *
 * @fires gla_datepicker_update with `report: props.trackEventReportId` and `data` given by `DateRangeFilterPicker`'s `onRangeSelect` callback.
 */
const AppDateRangeFilterPicker = ( props ) => {
	const { trackEventReportId } = props;
	const dateQuery = getDateQuery();

	/**
	 * Handles `DateRangeFilterPicker` range changes.
	 *
	 * Fires `gla_datepicker_update` event, updates query string with selected data.
	 *
	 * @param {Object} data Data given by `DateRangeFilterPicker`'s `onRangeSelect` callback.
	 */
	const handleRangeSelect = ( data ) => {
		if ( trackEventReportId ) {
			recordEvent( 'gla_datepicker_update', {
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
