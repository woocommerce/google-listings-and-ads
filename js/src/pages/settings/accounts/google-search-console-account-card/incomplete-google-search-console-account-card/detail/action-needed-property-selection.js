/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PropertySelection from './property-selection';

const ACTION_NEEDED_NOTICE = {
	status: 'warning',
	title: __(
		'Your Search Console property needs attention',
		'google-listings-and-ads'
	),
	body: __(
		"We couldn't confirm the previously connected property — it may have been deleted, or the connected account may no longer have verified access to it. Select another property below, or create a new one.",
		'google-listings-and-ads'
	),
};

/**
 * Renders the action-needed step's detail: {@see ./property-selection.js}'s selector and
 * create-new action, with copy explaining that the previously connected property is no longer
 * usable rather than the initial multi-match copy.
 *
 * @return {JSX.Element|null} The detail, or `null` while still loading.
 */
export default function ActionNeededPropertySelection() {
	return <PropertySelection notice={ ACTION_NEEDED_NOTICE } />;
}
