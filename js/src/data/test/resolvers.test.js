/**
 * Internal dependencies
 */
import {
	getGoogleTagManagerAccount,
	getExistingGoogleTagManagerAccounts,
	getGoogleTagManagerContainers,
} from '~/data/resolvers';
import TYPES from '~/data/action-types';

describe( 'getGoogleTagManagerAccount', () => {
	it( 'has no shouldInvalidate hook, so a disconnect never triggers a refetch that could clobber the local reset', () => {
		expect( getGoogleTagManagerAccount.shouldInvalidate ).toBeUndefined();
	} );
} );

describe( 'getExistingGoogleTagManagerAccounts.shouldInvalidate', () => {
	it( 'invalidates on a Google Tag Manager disconnect with invalidateRelatedState', () => {
		expect(
			getExistingGoogleTagManagerAccounts.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
				invalidateRelatedState: true,
			} )
		).toBe( true );
	} );

	it( 'does not invalidate a Google Tag Manager disconnect without invalidateRelatedState', () => {
		expect(
			getExistingGoogleTagManagerAccounts.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
			} )
		).toBeFalsy();
	} );

	it( 'does not invalidate on an unrelated action', () => {
		expect(
			getExistingGoogleTagManagerAccounts.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
				invalidateRelatedState: true,
			} )
		).toBeFalsy();
	} );
} );

describe( 'getGoogleTagManagerContainers.shouldInvalidate', () => {
	it( 'reuses getExistingGoogleTagManagerAccounts.shouldInvalidate rather than duplicating the check', () => {
		expect( getGoogleTagManagerContainers.shouldInvalidate ).toBe(
			getExistingGoogleTagManagerAccounts.shouldInvalidate
		);
	} );
} );
