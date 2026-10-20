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
 * The message shown when a container was created but its connection couldn't be confirmed.
 */
export const CREATED_NOT_CONNECTED_MESSAGE = __(
	"The container was created, but its connection couldn't be confirmed. Please try again.",
	'google-listings-and-ads'
);
