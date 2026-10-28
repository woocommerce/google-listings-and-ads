/**
 * Internal dependencies
 */
import { getNotifications } from '../resolvers';
import TYPES from '../action-types';

describe( 'getNotifications.shouldInvalidate', () => {
	it.each( [
		TYPES.DISMISS_NOTIFICATION,
		TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
		TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS,
		TYPES.DISCONNECT_ACCOUNTS_ALL,
		TYPES.RECEIVE_ADS_ENHANCED_CONVERSIONS,
	] )( 'invalidates on %s', ( type ) => {
		expect( getNotifications.shouldInvalidate( { type } ) ).toBe( true );
	} );

	it.each( [
		TYPES.RECEIVE_NOTIFICATIONS,
		TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
		TYPES.SAVE_SETTINGS,
		TYPES.UPDATE_ADS_CAMPAIGN,
	] )( 'does not invalidate on %s', ( type ) => {
		expect( getNotifications.shouldInvalidate( { type } ) ).toBe( false );
	} );
} );
