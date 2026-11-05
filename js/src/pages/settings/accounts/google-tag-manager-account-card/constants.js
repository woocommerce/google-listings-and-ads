/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * The Google Tag Manager account card's description — the same across every connection state, so
 * merchants see consistent copy regardless of which state (account selection, container
 * selection, connected, etc.) is currently showing.
 */
export const GOOGLE_TAG_MANAGER_DESCRIPTION = __(
	'Where tracking and marketing tags are managed across your store.',
	'google-listings-and-ads'
);

/**
 * The `context` sent with tracking events and documentation links from the Google Tag Manager
 * settings.
 */
export const GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT = 'settings-tag-manager';

/**
 * The message shown when Tag Manager refused to create a container in the selected account.
 */
export const CREATE_CONTAINER_PERMISSION_DENIED_MESSAGE = __(
	"Your Google account doesn't have permission to create containers in this Google Tag Manager account.",
	'google-listings-and-ads'
);

/**
 * The message shown when the Google connection only has read-only access to Tag Manager.
 */
export const READ_ONLY_CONNECTION_MESSAGE = __(
	"The Google connection for this store has read-only access to Google Tag Manager, so containers can't be created from here.",
	'google-listings-and-ads'
);

/**
 * The message shown when a container was created but its connection couldn't be confirmed.
 */
export const CREATED_NOT_CONNECTED_MESSAGE = __(
	"The container was created, but its connection couldn't be confirmed. Please try again.",
	'google-listings-and-ads'
);
