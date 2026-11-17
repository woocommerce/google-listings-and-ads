/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ERROR_SLOTS } from '~/data/constants';

/**
 * The Google Business Profile account card's description, shared by every connection state, so
 * merchants see consistent copy regardless of which state (location selection, connected, etc.)
 * is currently showing.
 */
export const GOOGLE_BUSINESS_PROFILE_DESCRIPTION = __(
	'Where your business appears on Google Search and Maps.',
	'google-listings-and-ads'
);

/**
 * The `context` sent with tracking events from the Google Business Profile settings.
 */
export const GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT =
	'settings-business-profile';

/**
 * The connection error slot the connect flow records a failure in, and clears on success.
 *
 * @type {Array<string>}
 */
export const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
];
