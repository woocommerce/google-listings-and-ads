/**
 * Internal dependencies
 */
import { getNotifications, hasAppliedCYOIncentive } from '../selectors';

describe( 'getNotifications', () => {
	it( 'returns state.notifications when present', () => {
		const notifications = [
			{ id: 'notif-1', triggered_at: 1000 },
			{ id: 'notif-2', triggered_at: 2000 },
		];

		expect( getNotifications( { notifications } ) ).toEqual(
			notifications
		);
	} );

	it( 'returns empty array when state.notifications is undefined', () => {
		expect( getNotifications( {} ) ).toEqual( [] );
	} );

	it( 'returns empty array when state.notifications is null', () => {
		expect( getNotifications( { notifications: null } ) ).toEqual( [] );
	} );
} );

describe( 'hasAppliedCYOIncentive', () => {
	it( 'returns the flag from the incentives response', () => {
		expect(
			hasAppliedCYOIncentive( {
				ads: { cyo_incentives: { has_applied_incentive: true } },
			} )
		).toBe( true );
	} );

	it( 'returns false when the incentives are not fetched yet', () => {
		expect(
			hasAppliedCYOIncentive( { ads: { cyo_incentives: {} } } )
		).toBe( false );
	} );
} );
