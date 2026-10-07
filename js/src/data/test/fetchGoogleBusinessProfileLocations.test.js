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

const buildLocation = ( name, placeId ) => {
	return { name, metadata: placeId ? { placeId } : {} };
};

describe( 'fetchGoogleBusinessProfileLocations', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'requests the locations and keeps each place once, in order', async () => {
		apiFetch.mockResolvedValue( {
			locations: [
				buildLocation( 'locations/1111', 'place-a' ),
				buildLocation( 'locations/2222', 'place-b' ),
				buildLocation( 'locations/9999', 'place-a' ),
				buildLocation( 'locations/3333' ),
				buildLocation( 'locations/4444' ),
			],
		} );

		const { result } = renderHook( () => useAppDispatch() );
		const action =
			await result.current.fetchGoogleBusinessProfileLocations();

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/business-profile/locations`,
		} );
		expect( action ).toEqual( {
			type: TYPES.RECEIVE_GOOGLE_BUSINESS_PROFILE_LOCATIONS,
			locations: [
				buildLocation( 'locations/1111', 'place-a' ),
				buildLocation( 'locations/2222', 'place-b' ),
				buildLocation( 'locations/3333' ),
				buildLocation( 'locations/4444' ),
			],
			hasError: false,
		} );
	} );

	it( 'records a failed request instead of throwing', async () => {
		apiFetch.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () => useAppDispatch() );
		const action =
			await result.current.fetchGoogleBusinessProfileLocations();

		expect( action ).toEqual( {
			type: TYPES.RECEIVE_GOOGLE_BUSINESS_PROFILE_LOCATIONS,
			locations: null,
			hasError: true,
		} );
	} );
} );
