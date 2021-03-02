/**
 * Internal dependencies
 */
import { recordTableHeaderToggleEvent } from '.~/utils/recordEvent';

const recordColumnToggleEvent = ( trackEventReportId, shown, toggled ) => {
	const status = shown.includes( toggled ) ? 'on' : 'off';
	recordTableHeaderToggleEvent( trackEventReportId, toggled, status );
};

export default recordColumnToggleEvent;
