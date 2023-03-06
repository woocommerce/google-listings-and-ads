/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * When table pagination is changed by entering page via "Go to page" input.
 *
 * @event gla_table_go_to_page
 * @property {string} context Name of the table
 * @property {string} page Page number (starting at 1)
 */

/**
 * When table pagination is clicked
 *
 * @event gla_table_page_click
 * @property {string} context Name of the table
 * @property {string} direction Direction of page to be changed. `("next" | "previous")`
 */

/**
 * Records table's page tracking event.
 * When the `direction` is 'goto', then the event name would be 'gla_table_go_to_page'.
 * Otherwise, the event name would be 'gla_table_page_click'.
 *
 * @param {string} context Name of the table.
 * @param {number} page Page number of the table. Start from 1.
 * @param {string} direction Direction of page to be changed. 'next', 'previous', or 'goto'.
 *
 * @fires gla_table_go_to_page with the given `{ context, page }`.
 * @fires gla_table_page_click with the given `{ context, direction }`.
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
 * @property {string} report Name of the report (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
 * @property {string} compare Value selected in datepicker.
 * @property {string} period Value selected in datepicker.
 * @property {string} before Value selected in datepicker.
 * @property {string} after Value selected in datepicker.
 */

/**
 * Triggered when changing products & variations filter,
 * with data that comes from
 * `FilterPicker`'s `onFilterSelect` callback.
 *
 * @event gla_filter
 * @property {string} report Name of the report (e.g. `"reports-products"`)
 * @property {string} filter Value of the filter (e.g. `"all" | "single-product" | "compare-products"`)
 * @property {string | undefined} variationFilter Value of the variation filter (e.g. `undefined | "single-variation" | "compare-variations"`)
 */

/**
 * Setup Merchant Center
 *
 * @event gla_setup_mc
 * @property {string} target Button ID
 * @property {string} trigger Action (e.g. `click`)
 * @property {string} context Indicates which CTA is clicked
 */

/**
 * Triggered when the "Launch paid campaign" button is clicked to add a new paid campaign in the Google Ads setup flow.
 *
 * @event gla_launch_paid_campaign_button_click
 * @property {string} audiences Country codes of the paid campaign audience countries, e.g. `'US,JP,AU'`. This means the campaign is created with the multi-country targeting feature. Before this feature support, it was implemented as 'audience'.
 * @property {string} budget Daily average cost of the paid campaign
 */

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
 * @property {string} context Indicates which page / module the link is in
 * @property {string} href Link's URL
 */

/**
 * A modal is closed.
 *
 * @event gla_modal_closed
 * @property {string} context Indicates which modal is closed
 * @property {string} action Indicates the modal is closed by what action (e.g. `maybe-later`|`dismiss` | `create-another-campaign`)
 *   - `maybe-later` is used when the "Maybe later" button on the modal is clicked
 *   - `dismiss` is used when the modal is dismissed by clicking on "X" icon, overlay, generic "Cancel" button, or pressing ESC
 *   - `create-another-campaign` is used when the button "Create another campaign" is clicked
 *   - `create-paid-campaign` is used when the button "Create paid campaign" is clicked
 *   - `confirm` is used when the button "Confirm", "Save"  or similar generic "Accept" button is clicked
 */

/**
 * A modal is open
 *
 * @event gla_modal_open
 * @property {string} context Indicates which modal is open
 */
