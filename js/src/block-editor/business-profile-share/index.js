/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import BusinessProfileSharePanel from './business-profile-share-panel';

registerPlugin( 'google-listings-and-ads-business-profile-share', {
	render: BusinessProfileSharePanel,
} );
