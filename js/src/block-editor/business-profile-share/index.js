/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import BusinessProfileSharePanel from './business-profile-share-panel';

/**
 * Webpack entry point for the `business-profile-share` build, enqueued by
 * `PostShareControl::enqueue_assets()` only on the block editor, and only when a Google Business
 * Profile location is connected. Registers the "Google Business Profile" post settings panel.
 */
registerPlugin( 'google-listings-and-ads-business-profile-share', {
	render: BusinessProfileSharePanel,
} );
