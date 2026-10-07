/**
 * Internal dependencies
 */
import {
	getGoogleBusinessProfileConnection,
	getGoogleBusinessProfileLocations,
} from '../resolvers';
import TYPES from '../action-types';

describe( 'Google Business Profile resolvers', () => {
	const disconnectAction = { type: TYPES.DISCONNECT_GOOGLE_BUSINESS_PROFILE };

	it( 'never invalidates the connection resolver, so a disconnect is not overwritten by a refetch', () => {
		expect(
			getGoogleBusinessProfileConnection.shouldInvalidate
		).toBeUndefined();
	} );

	it( 'invalidates the locations resolver on disconnect', () => {
		expect(
			getGoogleBusinessProfileLocations.shouldInvalidate(
				disconnectAction
			)
		).toBe( true );
	} );

	it( 'does not invalidate the locations resolver on unrelated actions', () => {
		expect(
			getGoogleBusinessProfileLocations.shouldInvalidate( {
				type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
			} )
		).toBe( false );
	} );
} );
