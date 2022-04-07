/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Toggling display of table columns
 *
 * @event gla_table_header_toggle
 * @property {string} report Name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
 * @property {string} column Name of the column
 * @property {'on' | 'off'} status Indicates if the column was toggled on or off.
 */

/**
 * @param {string} report The report's name
 * @param {*} shown
 * @param {string} column Column that was toggled
 * @fires gla_table_header_toggle with given `report: trackEventReportId, column: toggled`
 */
const recordColumnToggleEvent = ( report, shown, column ) => {
	const status = shown.includes( column ) ? 'on' : 'off';
	recordEvent( 'gla_table_header_toggle', {
		report,
		column,
		status,
	} );
};

export default recordColumnToggleEvent;
