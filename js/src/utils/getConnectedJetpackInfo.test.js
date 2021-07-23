/**
 * Internal dependencies
 */
import getConnectedJetpackInfo from './getConnectedJetpackInfo';

describe( 'getConnectedJetpackInfo', () => {
	it( 'When jetpack in not connected, should return an empty string', () => {
		expect( getConnectedJetpackInfo( { active: 'no' } ) ).toBe( '' );
	} );

	describe( 'For connected jetpack', () => {
		it( 'When current user is the jetpack owner, should return email', () => {
			const jetpack = {
				active: 'yes',
				owner: 'yes',
				email: 'i_am_jetpack_owner@example.com',
			};

			expect( getConnectedJetpackInfo( jetpack ) ).toBe( jetpack.email );
		} );

		it( 'When current user is not the jetpack owner, should return a successfully connected info', () => {
			const jetpack = {
				active: 'yes',
				owner: 'no',
				email: 'i_am_another_admin_user@example.com',
			};

			const info = getConnectedJetpackInfo( jetpack );

			expect( info ).not.toBe( jetpack.email );
			expect( info ).toMatchSnapshot();
		} );
	} );
} );
