/**
 * Internal dependencies
 */
import reducer from './reducer';
import TYPES from './action-types';

describe( 'reducer', () => {
	describe( 'REGISTER_NOTIFICATIONS', () => {
		it( 'replaces the existing state rather than appending to it', () => {
			const state = [ { id: 'stale-notification', triggered_at: 1 } ];
			const notifications = [
				{ id: 'fresh-notification', triggered_at: 2 },
			];

			const newState = reducer( state, {
				type: TYPES.REGISTER_NOTIFICATIONS,
				notifications,
			} );

			expect( newState ).toBe( notifications );
			expect( newState ).toEqual( [
				{ id: 'fresh-notification', triggered_at: 2 },
			] );
		} );

		it( 'clears previously registered notifications when given an empty list', () => {
			const state = [ { id: 'stale-notification', triggered_at: 1 } ];

			const newState = reducer( state, {
				type: TYPES.REGISTER_NOTIFICATIONS,
				notifications: [],
			} );

			expect( newState ).toEqual( [] );
		} );
	} );

	describe( 'DISMISS_NOTIFICATION', () => {
		it( 'removes only the notification matching the given id', () => {
			const state = [
				{ id: 'keep-me', triggered_at: 1 },
				{ id: 'dismiss-me', triggered_at: 2 },
			];

			const newState = reducer( state, {
				type: TYPES.DISMISS_NOTIFICATION,
				id: 'dismiss-me',
			} );

			expect( newState ).toEqual( [
				{ id: 'keep-me', triggered_at: 1 },
			] );
		} );
	} );
} );
