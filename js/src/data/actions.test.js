/**
 * Internal dependencies
 */
import {
	createMarket,
	fetchGoogleBusinessProfileAccount,
	fetchGoogleBusinessProfileLocations,
} from './actions';
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';
import { handleApiError } from '~/utils/handleError';

jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

describe( 'createMarket', () => {
	/**
	 * Drives the generator to completion, feeding the given body back as the POST result.
	 *
	 * @param {Object} args Market data.
	 * @param {Object} body Response body the POST resolves to.
	 * @return {{ request: Object, returned: any }} The yielded request and the returned value.
	 */
	const run = ( args, body ) => {
		const generator = createMarket( args );
		const request = generator.next().value;

		// The POST resolves to `body`; the next yield refetches the markets.
		generator.next( body );

		return { request, returned: generator.next().value };
	};

	it( 'posts the market data to the markets endpoint', () => {
		const { request } = run( { country: 'GB' }, {} );

		expect( request.request ).toEqual(
			expect.objectContaining( {
				path: `${ API_NAMESPACE }/mc/markets`,
				method: 'POST',
				data: { country: 'GB' },
			} )
		);
	} );

	it( 'returns the response body so the caller can tell a fold from a creation', () => {
		const body = { id: 'primary', merged_into_primary: true };

		expect( run( { country: 'GB' }, body ).returned ).toEqual( body );
	} );

	it( 'returns the created market when nothing was folded', () => {
		const body = { id: 'gb', country: 'GB' };

		expect( run( { country: 'GB' }, body ).returned ).toEqual( body );
	} );

	it( 'refetches the markets before returning', () => {
		const generator = createMarket( { country: 'GB' } );

		generator.next();

		const refetch = generator.next( {} ).value;

		// fetchMarkets is itself a generator, not the plain response body.
		expect( typeof refetch.next ).toBe( 'function' );
		expect( generator.next().done ).toBe( true );
	} );

	it( 'rethrows so the caller can keep the form open', () => {
		const generator = createMarket( { country: 'GB' } );

		generator.next();

		expect( () => generator.throw( new Error( 'boom' ) ) ).toThrow(
			'boom'
		);
	} );
} );

describe( 'fetchGoogleBusinessProfileAccount', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'requests the connection endpoint and receives the response', () => {
		const generator = fetchGoogleBusinessProfileAccount();
		const request = generator.next().value;
		const account = {
			status: 'connected',
			id: '1111',
			accountId: '106234255840114990952',
			title: "Jane's Bakery",
			address: '2423 1st Ave, Seattle, WA, 98121',
			placeId: 'place-1111',
			mapsUri: 'https://maps.google.com/?cid=1111',
		};

		expect( request.request ).toEqual(
			expect.objectContaining( {
				path: `${ API_NAMESPACE }/business-profile/connection`,
			} )
		);
		expect( generator.next( account ).value ).toEqual( {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_BUSINESS_PROFILE,
			account,
		} );
	} );

	it( 'reports the error when the request fails', () => {
		const generator = fetchGoogleBusinessProfileAccount();
		const error = new Error( 'Request failed' );

		generator.next();
		generator.throw( error );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error loading Google Business Profile account info.'
		);
	} );
} );

describe( 'fetchGoogleBusinessProfileLocations', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'requests the locations endpoint and receives the response', () => {
		const generator = fetchGoogleBusinessProfileLocations();
		const request = generator.next().value;
		const locations = [
			{
				id: '1111',
				accountId: '106234255840114990952',
				title: "Jane's Bakery",
				address: '2423 1st Ave, Seattle, WA, 98121',
				placeId: 'place-1111',
				mapsUri: 'https://maps.google.com/?cid=1111',
			},
		];

		expect( request.request ).toEqual(
			expect.objectContaining( {
				path: `${ API_NAMESPACE }/business-profile/locations`,
			} )
		);
		expect( generator.next( locations ).value ).toEqual( {
			type: TYPES.RECEIVE_GOOGLE_BUSINESS_PROFILE_LOCATIONS,
			locations,
		} );
	} );

	it( 'reports the error when the request fails', () => {
		const generator = fetchGoogleBusinessProfileLocations();
		const error = new Error( 'Request failed' );

		generator.next();
		generator.throw( error );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error getting your Google Business Profile locations.'
		);
	} );
} );
