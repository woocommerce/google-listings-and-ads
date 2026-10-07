/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import TYPES from '~/data/action-types';
import { handleApiError } from '~/utils/handleError';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

jest.mock( '~/utils/handleError', () => {
	return {
		handleApiError: jest.fn().mockName( 'handleApiError' ),
	};
} );

const location = {
	id: '1111',
	accountId: '106234255840114990952',
	title: "Jane's Bakery",
	address: '2423 1st Ave, Seattle, WA, 98121',
	placeId: 'place-1111',
	mapsUri: 'https://maps.google.com/?cid=1111',
};

describe( 'connectGoogleBusinessProfileLocation', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'connects the location by its ID and receives its fields alongside the connected status', async () => {
		apiFetch.mockResolvedValue( {
			status: 'success',
			message: 'Successfully connected Google Business Profile location.',
		} );

		const { result } = renderHook( () => useAppDispatch() );
		const action =
			await result.current.connectGoogleBusinessProfileLocation(
				location
			);

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/business-profile/locations`,
			method: 'POST',
			data: { id: '1111' },
		} );
		expect( action ).toEqual( {
			type: TYPES.RECEIVE_GOOGLE_BUSINESS_PROFILE_CONNECTION,
			connection: { status: 'connected', ...location },
		} );
	} );

	it( 'reports a failed request and rethrows it', async () => {
		const error = new Error( 'failed' );
		apiFetch.mockRejectedValue( error );

		const { result } = renderHook( () => useAppDispatch() );

		await expect(
			result.current.connectGoogleBusinessProfileLocation( location )
		).rejects.toBe( error );
		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'Unable to connect your Google Business Profile location.'
		);
	} );
} );
