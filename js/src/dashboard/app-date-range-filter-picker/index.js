/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import getDateQuery from './getDateQuery';
import { recordDatepickerUpdateEvent } from '../../utils/recordEvent';

const isoDateFormat = 'YYYY-MM-DD';

/**
 * Renders DateRangeFilterPicker, handles range selection,
 * fires applicable track events.
 *
 * @param {Object} props
 * @param {String} [props.trackEventReportId] An id to be used as `report` propert in fired events.
 * 												If not given, no track event will be fired.
 *
 * @returns {module:@woocommerce/components#DateRangeFilterPicker}
 */
const AppDateRangeFilterPicker = ( props ) => {
	const { trackEventReportId } = props;
	const dateQuery = getDateQuery();

	const handleRangeSelect = ( data ) => {
		if ( trackEventReportId ) {
			recordDatepickerUpdateEvent( {
				report: trackEventReportId,
				...data
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
