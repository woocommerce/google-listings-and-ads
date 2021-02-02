/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

export const recordTableHeaderToggleEvent = ( report, column, status ) => {
	recordEvent( 'gla_table_header_toggle', {
		report,
		column,
		status,
	} );
};

export const recordTableSortEvent = ( report, column, direction ) => {
	recordEvent( 'gla_table_sort', { report, column, direction } );
};
/**
 * Records `gla_datepicker_update` tracking event, with data that comes from
 * `DateRangeFilterPicker`'s `onRangeSelect` callback.
 *
 * @param {Object} data Report name plus the data given by `DateRangeFilterPicker`'s `onRangeSelect` callback.
 * @param {string} data.report Name of the report.
 * @param {string} data.compare
 * @param {string} data.period
 * @param {string} data.after
 * @param {string} data.before
 */
export const recordDatepickerUpdateEvent = ( data ) => {
	recordEvent( 'gla_datepicker_update', data );
};

export const recordSetupMCEvent = ( target, trigger = 'click' ) => {
	recordEvent( 'gla_setup_mc', {
		target,
		trigger,
	} );
};

export const recordSetupAdsEvent = ( target, trigger = 'click' ) => {
	recordEvent( 'gla_setup_ads', {
		target,
		trigger,
	} );
};

export default recordEvent;
