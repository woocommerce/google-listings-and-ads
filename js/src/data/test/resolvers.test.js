/**
 * Internal dependencies
 */
import {
	getGoogleTagManagerAccount,
	getGoogleTagManagerContainers,
} from '~/data/resolvers';
import TYPES from '~/data/action-types';

describe( 'getGoogleTagManagerAccount.shouldInvalidate', () => {
	it( 'invalidates on a Google Tag Manager disconnect with invalidateRelatedState', () => {
		expect(
			getGoogleTagManagerAccount.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
				invalidateRelatedState: true,
			} )
		).toBe( true );
	} );

	it( 'does not invalidate a Google Tag Manager disconnect without invalidateRelatedState', () => {
		expect(
			getGoogleTagManagerAccount.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
			} )
		).toBeFalsy();
	} );

	it( 'does not invalidate on an unrelated action', () => {
		expect(
			getGoogleTagManagerAccount.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
				invalidateRelatedState: true,
			} )
		).toBeFalsy();
	} );
} );

describe( 'getGoogleTagManagerContainers.shouldInvalidate', () => {
	it( 'invalidates on a Google Tag Manager disconnect with invalidateRelatedState', () => {
		expect(
			getGoogleTagManagerContainers.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
				invalidateRelatedState: true,
			} )
		).toBe( true );
	} );

	it( 'does not invalidate a Google Tag Manager disconnect without invalidateRelatedState', () => {
		expect(
			getGoogleTagManagerContainers.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
			} )
		).toBeFalsy();
	} );

	it( 'does not invalidate on an unrelated action', () => {
		expect(
			getGoogleTagManagerContainers.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
				invalidateRelatedState: true,
			} )
		).toBeFalsy();
	} );
} );
