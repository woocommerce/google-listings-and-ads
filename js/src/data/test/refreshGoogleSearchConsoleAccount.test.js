/**
 * Internal dependencies
 */
import { refreshGoogleSearchConsoleAccount } from '../actions';
import TYPES from '../action-types';
import { API_NAMESPACE } from '../constants';
import { logError } from '~/utils/console';

jest.mock( '~/utils/console', () => ( {
	logError: jest.fn().mockName( 'logError' ),
} ) );

describe( 'refreshGoogleSearchConsoleAccount', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

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

	it( 'should log the error and receive nothing when the request fails, so the stored account is kept', () => {
		const error = new Error( 'offline' );
		const generator = refreshGoogleSearchConsoleAccount();

		generator.next();

		expect( generator.throw( error ) ).toEqual( {
			done: true,
			value: undefined,
		} );
		expect( logError ).toHaveBeenCalledTimes( 1 );
		expect( logError ).toHaveBeenCalledWith( error );
	} );
} );
