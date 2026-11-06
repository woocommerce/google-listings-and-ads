/**
 * Internal dependencies
 */
import { getNotifications } from '~/data/resolvers';
import TYPES from '~/data/action-types';

describe( 'getNotifications.shouldInvalidate', () => {
	it.each( [
		TYPES.DISMISS_NOTIFICATION,
		TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
		TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS,
	] )( 'returns true for %s', ( type ) => {
		expect( getNotifications.shouldInvalidate( { type } ) ).toBe( true );
	} );

	it( 'returns false for unrelated actions', () => {
		expect(
			getNotifications.shouldInvalidate( {
				type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
			} )
		).toBe( false );
	} );
} );
