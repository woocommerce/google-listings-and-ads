/**
 * Internal dependencies
 */
import { refreshGoogleSearchConsoleAccount } from '../actions';
import TYPES from '../action-types';
import { API_NAMESPACE } from '../constants';

describe( 'refreshGoogleSearchConsoleAccount', () => {
	it( 'should receive the account returned by the connection endpoint', () => {
		const account = { status: 'action-needed' };
		const generator = refreshGoogleSearchConsoleAccount();

		const request = generator.next().value;
		expect( request.request.path ).toBe(
			`${ API_NAMESPACE }/search-console/connection`
		);

		expect( generator.next( account ) ).toEqual( {
			done: true,
			value: {
				type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_SEARCH_CONSOLE,
				account,
			},
		} );
	} );

	it( 'should receive nothing when the request fails, so the stored account is kept', () => {
		const generator = refreshGoogleSearchConsoleAccount();

		generator.next();

		expect( generator.throw( new Error( 'offline' ) ) ).toEqual( {
			done: true,
			value: undefined,
		} );
	} );
} );
