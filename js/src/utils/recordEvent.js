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

export const recordDatepickerUpdateEvent = ( data ) => {
	recordEvent( 'gla_datepicker_update', data );
};

export const recordSetupMCEvent = ( target, trigger = 'click' ) => {
	recordEvent( 'gla_setup_mc', {
		target,
		trigger,
	} );
};
