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
 * Records table's page tracking event.
 * When the `direction` is 'goto', then the event name would be 'gla_table_go_to_page'.
 * Otherwise, the event name would be 'gla_table_page_click'.
 *
 * @param {string} context Name of the table.
 * @param {number} page Page number of the table. Start from 1.
 * @param {string} direction Direction of page to be changed. 'next', 'previous', or 'goto'.
 */
export const recordTablePageEvent = ( context, page, direction ) => {
	const properties = { context };
	let eventName;

	if ( direction === 'goto' ) {
		eventName = 'gla_table_go_to_page';
		properties.page = page;
	} else {
		eventName = 'gla_table_page_click';
		properties.direction = direction;
	}
	recordEvent( eventName, properties );
};

/**
 * Triggered when datepicker (date ranger picker) is updated,
 * with report name and data that comes from `DateRangeFilterPicker`'s `onRangeSelect` callback
 *
 * @event gla_datepicker_update
 * @property {string} report name of the report (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
 * @property {string} compare Value selected in datepicker.
 * @property {string} period Value selected in datepicker.
 * @property {string} before Value selected in datepicker.
 * @property {string} after Value selected in datepicker.
 */

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
 *
 * @fires gla_datepicker_update
 */
export const recordDatepickerUpdateEvent = ( data ) => {
	recordEvent( 'gla_datepicker_update', data );
};

/**
 * Triggered when changing products & variations filter.
 *
 * @event gla_filter
 * @property {string} report name of the report (e.g. `"reports-products"`)
 * @property {string} filter value of the filter (e.g. `"all" | "single-product" | "compare-products"`)
 * @property {string | undefined} variationFilter value of the variation filter (e.g. `undefined | "single-variation" | "compare-variations"`)
 */

/**
 * Records `gla_filter` tracking event, with data that comes from
 * `FilterPicker`'s `onFilterSelect` callback.
 *
 * @param {Object} data
 * @param {string} data.report Name of the report.
 * @param {string} data.filter Picked value.
 * @param {string} [data.filterVariation] Picked variation value if applicable.
 *
 * @fires gla_filter
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
 * Triggered when the "Launch paid campaign" button is clicked to add a new paid campaign
 *
 * @event gla_launch_paid_campaign_button_click
 * @property {string} audiences country codes of the paid campaign audience countries, e.g. `'US,JP,AU'`. This means the campaign is created with the multi-country targeting feature. Before this feature support, it was implemented as 'audience'.
 * @property {string} budget daily average cost of the paid campaign
 */

/**
 * Records `gla_launch_paid_campaign_button_click` tracking event.
 *
 * @fires gla_launch_paid_campaign_button_click
 *
 * @param {number} budget Daily average cost of the paid campaign.
 * @param {Array<CountryCode>} audiences Country code array of the paid campaign audience country.
 */
export const recordLaunchPaidCampaignClickEvent = ( budget, audiences ) => {
	recordEvent( 'gla_launch_paid_campaign_button_click', {
		audiences: audiences.join( ',' ),
		budget,
	} );
};

export default recordEvent;

/**
 * Clicking on the button to connect Google account.
 *
 * @event gla_google_account_connect_button_click
 * @property {string} context (`setup-mc`|`setup-ads`|`reconnect`) - indicate the button is clicked from which page.
 * @property {string} action (`authorization`|`scope`)
 *	- `authorization` is used when the plugin has not been authorized yet and requests Google account access and permission scopes from users.
 *  - `scope` is used when requesting required permission scopes from users in order to proceed with more plugin functions. Added with the Partial OAuth feature (aka Incremental Authorization).
 */

/**
 * Clicking on a Google Merchant Center link.
 *
 * @event gla_google_mc_link_click
 * @property {string} context indicate which page / module the link is in
 * @property {string} href link's URL
 */

/**
 * A modal is closed.
 *
 * @event gla_modal_closed
 * @property {string} context indicate which modal is closed
 * @property {string} action indicate the modal is closed by what action (e.g. `maybe-later`|`dismiss` | `create-another-campaign`)
 *   - `maybe-later` is used when the "Maybe later" button on the modal is clicked
 *   - `dismiss` is used when the modal is dismissed by clicking on "X" icon, overlay, or pressing ESC
 *   - `create-another-campaign` is used when the button "Create another campaign" is clicked
 *   - `create-paid-campaign` is used when the button "Create paid campaign" is clicked
 */
