/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

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
/**
 * Records `gla_filter` tracking event, with data that comes from
 * `FilterPicker`'s `onFilterSelect` callback.
 *
 * @param {Object} data
 * @param {string} data.report Name of the report.
 * @param {string} data.filter Picked value.
 * @param {string} [data.filterVariation] Picked variation value if applicable.
 */
export const recordFilterEvent = ( data ) => {
	recordEvent( 'gla_filter', data );
};

/**
 * Records `gla_chart_tab_click` tracking event.
 *
 * @param {Object} data
 * @param {string} data.report Name of the report.
 * @param {string} data.context Metric key of the clicked tab.
 */
export const recordChartTabClickEvent = ( data ) => {
	recordEvent( 'gla_chart_tab_click', data );
};

export const recordSetupMCEvent = ( target, trigger = 'click' ) => {
	recordEvent( 'gla_setup_mc', {
		target,
		trigger,
	} );
};

export const recordPreLaunchChecklistCompleteEvent = () => {
	recordEvent( 'gla_pre_launch_checklist_complete' );
};

export const recordSetupAdsEvent = ( target, trigger = 'click' ) => {
	recordEvent( 'gla_setup_ads', {
		target,
		trigger,
	} );
};

/**
 * Records `gla_launch_paid_campaign_button_click` tracking event.
 *
 * @param {number} budget Daily average cost of the paid campaign.
 * @param {CountryCode} audience Country code of the paid campaign audience country.
 */
export const recordLaunchPaidCampaignClickEvent = ( budget, audience ) => {
	recordEvent( 'gla_launch_paid_campaign_button_click', {
		audience,
		budget,
	} );
};

export default recordEvent;
