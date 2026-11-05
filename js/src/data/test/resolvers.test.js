/**
 * Internal dependencies
 */
import {
	getGoogleAdsAccount,
	getGoogleTagManagerAccount,
	getExistingGoogleTagManagerAccounts,
	getGoogleTagManagerContainers,
	getGoogleTagManagerSettings,
} from '~/data/resolvers';
import TYPES from '~/data/action-types';

describe( 'getGoogleAdsAccount.shouldInvalidate', () => {
	it( 'invalidates on a Google Ads disconnect with invalidateRelatedState', () => {
		expect(
			getGoogleAdsAccount.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS,
				invalidateRelatedState: true,
			} )
		).toBe( true );
	} );

	// Invalidating would reset its resolution and swap the Accounts page for a spinner; the
	// disconnect action fetches the Google Ads connection instead.
	it( 'does not invalidate on a Google Tag Manager disconnect', () => {
		expect(
			getGoogleAdsAccount.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
				invalidateRelatedState: true,
			} )
		).toBeFalsy();
	} );
} );

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
	it( 'delegates the disconnect check to getExistingGoogleTagManagerAccounts.shouldInvalidate, rather than duplicating it', () => {
		expect(
			getGoogleTagManagerContainers.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
				invalidateRelatedState: true,
			} )
		).toBe( true );

		expect(
			getGoogleTagManagerContainers.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
			} )
		).toBeFalsy();
	} );

	it( 'invalidates when a fresh account payload is received, since the container list belongs to whichever account is currently selected', () => {
		expect(
			getGoogleTagManagerContainers.shouldInvalidate( {
				type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_TAG_MANAGER,
			} )
		).toBe( true );
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

describe( 'getGoogleTagManagerSettings.shouldInvalidate', () => {
	it( 'invalidates on a Google Tag Manager disconnect, since that resets the stored settings', () => {
		expect(
			getGoogleTagManagerSettings.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER,
			} )
		).toBe( true );
	} );

	it( 'does not invalidate on an unrelated action', () => {
		expect(
			getGoogleTagManagerSettings.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
				invalidateRelatedState: true,
			} )
		).toBe( false );
	} );
} );
