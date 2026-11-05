/**
 * Internal dependencies
 */
import {
	createMarket,
	fetchGoogleTagManagerSettings,
	updateGoogleTagManagerSettings,
} from './actions';
import { API_NAMESPACE } from './constants';
import TYPES from './action-types';
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

describe( 'fetchGoogleTagManagerSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'requests the settings endpoint and receives the response', () => {
		const generator = fetchGoogleTagManagerSettings();
		const request = generator.next().value;
		const settings = { snippetInjectionEnabled: true };

		expect( request.request ).toEqual(
			expect.objectContaining( {
				path: `${ API_NAMESPACE }/tag-manager/settings`,
			} )
		);
		expect( generator.next( settings ).value ).toEqual( {
			type: TYPES.RECEIVE_GOOGLE_TAG_MANAGER_SETTINGS,
			settings,
		} );
	} );

	it( 'reports the error when the request fails', () => {
		const generator = fetchGoogleTagManagerSettings();
		const error = new Error( 'Request failed' );

		generator.next();
		generator.throw( error );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error loading the Google Tag Manager settings.'
		);
	} );
} );

describe( 'updateGoogleTagManagerSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'posts the given settings and receives the stored settings from the response', () => {
		const generator = updateGoogleTagManagerSettings( {
			snippet_injection_enabled: false,
		} );
		const request = generator.next().value;
		const settings = { snippetInjectionEnabled: false };

		expect( request.request ).toEqual(
			expect.objectContaining( {
				path: `${ API_NAMESPACE }/tag-manager/settings`,
				method: 'POST',
				data: { snippet_injection_enabled: false },
			} )
		);
		expect( generator.next( settings ).value ).toEqual( {
			type: TYPES.RECEIVE_GOOGLE_TAG_MANAGER_SETTINGS,
			settings,
		} );
	} );

	it( 'reports and rethrows the error when the request fails', () => {
		const generator = updateGoogleTagManagerSettings( {
			snippet_injection_enabled: true,
		} );
		const error = new Error( 'Request failed' );

		generator.next();

		expect( () => generator.throw( error ) ).toThrow( error );
		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'Unable to update the Google Tag Manager settings.'
		);
	} );
} );
