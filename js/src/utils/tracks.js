/**
 * External dependencies
 */
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';
import { select } from '@wordpress/data';
import { createHooks } from '@wordpress/hooks';
import { getQuery } from '@woocommerce/navigation';
import { pick } from 'lodash';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { STORE_KEY } from '~/data';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

export const hooks = createHooks();

export const NAMESPACE = 'tracking';
export const FILTER_ONBOARDING = 'FILTER_ONBOARDING';
export const FILTER_BUDGET_RECOMMENDATIONS = 'FILTER_BUDGET_RECOMMENDATIONS';

export const filterPropertiesMap = new Map();

filterPropertiesMap.set( FILTER_ONBOARDING, [ 'context', 'step' ] );
filterPropertiesMap.set( FILTER_BUDGET_RECOMMENDATIONS, [
	'source',
	'recommended_budget',
] );

const REFERRER_QUERY_PROPERTIES = [ 'referrer_type', 'referrer_id' ];

/*
 * Please be aware of when to use these context values
 * - 'setup-mc': Extension onboarding (a.k.a Merchant Center Setup or MC Setup)
 * - 'setup-ads': Ads onboarding (a.k.a Google Ads Setup)
 *
 * Since Google Ads Setup has been added to the extension onboarding,
 * the 'setup-mc' is no longer an appropriate value to identify it.
 * The same is the case for 'setup-ads', which represents Google Ads Setup.
 *
 * However, as these values are heavily used in codebase and event tracking,
 * these values continue to be used at present for consistency.
 */
export const CONTEXT_EXTENSION_ONBOARDING = 'setup-mc';
export const CONTEXT_ADS_ONBOARDING = 'setup-ads';
export const CONTEXT_ADS_ONLY_ONBOARDING = 'setup-ads-only';
export const CONTEXT_MARKETING_OVERVIEW = 'marketing-overview';

/**
 * Referrer type indicating a flow was entered from a notification's CTA.
 */
export const REFERRER_TYPE_NOTIFICATION = 'notification';

/**
 * Referrer type indicating a flow was entered from an in-product placement's CTA.
 */
export const REFERRER_TYPE_IN_PRODUCT_PLACEMENTS = 'in_product_placements';

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
 * Returns an event properties with base properties.
 * - gla_version: Plugin version
 * - gla_mc_id: Google Merchant Center account ID if connected
 * - gla_ads_id: Google Ads account ID if connected
 * - referrer_type/referrer_id: Carried over from the current URL when the
 *   flow was entered from a referring surface (e.g. a notification CTA via
 *   `REFERRER_TYPE_NOTIFICATION`), so downstream events can be attributed
 *   back to it.
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	const { slug } = glaData;
	const { version, adsId, mcId } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...eventProperties,
		...pick( getQuery(), REFERRER_QUERY_PROPERTIES ),
		[ `${ slug }_version` ]: version,
	};

	if ( mcId ) {
		mixedProperties[ `${ slug }_mc_id` ] = mcId;
	}

	if ( adsId ) {
		mixedProperties[ `${ slug }_ads_id` ] = adsId;
	}

	return mixedProperties;
}

/**
 * Record a tracking event with base properties.
 *
 * @param {string} eventName The name of the event to record.
 * @param {Object} [eventProperties] The event properties to include in the event.
 */
export function recordGlaEvent( eventName, eventProperties ) {
	recordEvent( eventName, addBaseEventProperties( eventProperties ) );
}

/**
 * Queue a tracking event with base properties.
 *
 * This allows you to delay tracking events that would otherwise cause a race condition.
 *
 * @param {string} eventName The name of the event to record.
 * @param {Object} [eventProperties] The event properties to include in the event.
 */
export function queueRecordGlaEvent( eventName, eventProperties ) {
	queueRecordEvent( eventName, addBaseEventProperties( eventProperties ) );
}

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
	recordGlaEvent( eventName, properties );
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
 * @property {string} [filter_variation] Value of the variation filter (e.g. `"single-variation" | "compare-variations"`)
 */

/**
 * Setup Merchant Center
 *
 * @event gla_setup_mc
 * @property {string} triggered_by Indicates which button triggered this event
 * @property {string} action User's action or/and objective (e.g. `leave`, `go-to-step-2`)
 * @property {string | undefined} context Indicates which CTA is clicked
 */

/**
 * Triggered when the "Launch paid campaign" button is clicked to add a new paid campaign in the Google Ads setup flow.
 *
 * @event gla_launch_paid_campaign_button_click
 * @property {string} level The selected level of the budget recommendation, e.g. 'low', 'recommended', 'high', 'custom'.
 * @property {string} audiences Country codes of the paid campaign audience countries, e.g. `'US,JP,AU'`. This means the campaign is created with the multi-country targeting feature. Before this feature support, it was implemented as 'audience'.
 * @property {number} budget Daily average cost of the paid campaign
 * @property {string} source The data source of the budget recommendations, e.g. 'google-ads-api', 'fallback-database'.
 * @property {number} recommended_budget The recommended daily budget displayed to merchants regardless of the final amount they choose.
 * @property {boolean} has_confirmed_eu_political_content Whether the user has confirmed that the ads campaign contains EU political content.
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
 * Triggered on events during ads onboarding
 *
 * @event gla_setup_ads
 * @property {string} triggered_by Indicates which button triggered this event
 * @property {string} action User's action or/and objective (e.g. `leave`, `go-to-step-2`)
 */

/**
 * Triggered when moving to another step during creating/editing a campaign.
 *
 * @event gla_paid_campaign_step
 * @property {string} triggered_by Indicates which button triggered this event
 * @property {string} action User's action or/and objective (e.g. `go-to-step-2`)
 * @property {string | undefined} context Indicates where this event happened
 */

/**
 * Records the event tracking when calling back the "onClick" of <Stepper> to move to another step.
 *
 * @param {string} eventName The event name to record.
 * @param {string} to The next step to go to, e.g. '2'.
 * @param {string} [context] Indicates where this event happened.
 */
export function recordStepperChangeEvent( eventName, to, context ) {
	recordGlaEvent( eventName, {
		triggered_by: `stepper-step${ to }-button`,
		action: `go-to-step${ to }`,
		context,
	} );
}

/**
 * Records the event tracking when clicking on the "Continue" button within a step content to move to another step.
 *
 * @param {string} eventName The event name to record.
 * @param {string} from The current step to leave from, e.g. '1'.
 * @param {string} to The next step to go to, e.g. '2'.
 * @param {string} [context] Indicates where this event happened.
 */
export function recordStepContinueEvent( eventName, from, to, context ) {
	recordGlaEvent( eventName, {
		triggered_by: `step${ from }-continue-button`,
		action: `go-to-step${ to }`,
		context,
	} );
}

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
