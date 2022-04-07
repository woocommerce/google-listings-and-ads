/**
 * Internal dependencies
 */
import { recordTableHeaderToggleEvent } from '.~/utils/recordEvent';

/**
 * @param {*} trackEventReportId
 * @param {*} shown
 * @param {*} toggled
 * @fires gla_table_header_toggle with given `report: trackEventReportId, column: toggled`
 */
const recordColumnToggleEvent = ( trackEventReportId, shown, toggled ) => {
	const status = shown.includes( toggled ) ? 'on' : 'off';
	recordTableHeaderToggleEvent( trackEventReportId, toggled, status );
};

export default recordColumnToggleEvent;
