/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AppTableCardDiv from '.~/components/app-table-card-div';

/**
 * Toggling display of table columns
 *
 * @event gla_table_header_toggle
 * @property {string} report Name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
 * @property {string} column Name of the column
 * @property {'on' | 'off'} status Indicates if the column was toggled on or off.
 */

/**
 * Maps `TableCard`'s `onColumnsChange` arguments to `gla_table_header_toggle` event.
 *
 * @param {string} report The report's name
 * @param {Array<string>} shown List of shown columns
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

/**
 * Sorting table
 *
 * @event gla_table_sort
 * @property {string} report Name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
 * @property {string} column Name of the column
 * @property {string} direction (`asc`|`desc`)
 */

/**
 * Maps `TableCard`'s `onSort` arguments to `gla_table_sort` event.
 *
 * @param {string} report The report's name
 * @param {string} column Column that was sorted
 * @param {'asc' | 'desc'} direction Indicates if it was sorted in ascending or descending order
 * @fires gla_table_sort with given props.
 */
const recordTableSortEvent = ( report, column, direction ) => {
	recordEvent( 'gla_table_sort', { report, column, direction } );
};

/**
 * Renders a TableCard component with additional styling,
 * and records [track event](../../../../src/Tracking) when `trackEventReportId` is supplied via props.
 *
 * ## Usage
 * Same as [TableCard](https://woocommerce.github.io/woocommerce-admin/#/components/packages/table/README?id=tablecard).
 *
 * @fires gla_table_header_toggle upon toggling column visibility
 * @fires gla_table_sort upon sorting table by column
 * @see module:@woocommerce/components#TableCard
 *
 * @param {Object} props Props to be forwarded to the TableCard plus trackEventReportId.
 * @param {string} [props.trackEventReportId] Report ID to be used in track events.
 * 											If this is not supplied, the track event will not be called.
 */
const AppTableCard = ( props ) => {
	const { trackEventReportId, ...rest } = props;
	/**
	 * Returns a function that records a track event before executing an original handler.
	 *
	 * @param {Function} recordSpecificEvent The function to record the event.
	 * @param {Function} [originalHandler] The original event handler.
	 *
	 * @return {decoratedHandler} Decorated handler.
	 */
	function decorateHandlerWithTrackEvent(
		recordSpecificEvent,
		originalHandler
	) {
		/**
		 * Records track event with specified `trackEventReportId` and any args given,
		 * then calls original handler if any.
		 *
		 * @function decoratedHandler
		 * @param {...*} args Arguments to be forwarded to the original handler.
		 */
		return function decoratedHandler( ...args ) {
			if ( trackEventReportId ) {
				recordSpecificEvent( trackEventReportId, ...args );
			}

			// Call the original handler if given.
			if ( originalHandler ) {
				originalHandler( ...args );
			}
		};
	}

	return (
		<AppTableCardDiv>
			<TableCard
				{ ...rest }
				onColumnsChange={ decorateHandlerWithTrackEvent(
					recordColumnToggleEvent,
					props.onColumnsChange
				) }
				onSort={ decorateHandlerWithTrackEvent(
					recordTableSortEvent,
					props.onSort
				) }
			/>
		</AppTableCardDiv>
	);
};

export default AppTableCard;
