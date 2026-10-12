/**
 * Internal dependencies
 */
import {
	getGoogleBusinessProfileAccount,
	getGoogleBusinessProfileLocations,
} from '~/data/resolvers';
import TYPES from '~/data/action-types';

describe( 'getGoogleBusinessProfileAccount', () => {
	it( 'has no shouldInvalidate hook, so a disconnect never triggers a refetch that could clobber the local reset', () => {
		expect(
			getGoogleBusinessProfileAccount.shouldInvalidate
		).toBeUndefined();
	} );
} );

describe( 'getGoogleBusinessProfileLocations.shouldInvalidate', () => {
	it( 'invalidates on a Google Business Profile disconnect with invalidateRelatedState', () => {
		expect(
			getGoogleBusinessProfileLocations.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_BUSINESS_PROFILE,
				invalidateRelatedState: true,
			} )
		).toBe( true );
	} );

	it( 'does not invalidate a Google Business Profile disconnect without invalidateRelatedState', () => {
		expect(
			getGoogleBusinessProfileLocations.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_BUSINESS_PROFILE,
			} )
		).toBeFalsy();
	} );

	it( 'does not invalidate on an unrelated action', () => {
		expect(
			getGoogleBusinessProfileLocations.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
				invalidateRelatedState: true,
			} )
		).toBeFalsy();
	} );
} );
