/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleBusinessProfileLocations from './useGoogleBusinessProfileLocations';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

const downtown = { id: '1111', title: "Jane's Bakery" };
const riverside = { id: '2222', title: "Jane's Bakery Riverside" };

/**
 * Mocks the store with the given locations and resolution state.
 *
 * @param {Object[]|null} locations The locations the selector returns.
 * @param {boolean} [resolved] Whether the resolver has finished.
 * @return {jest.Mock} The `hasFinishedResolution` mock.
 */
const mockStore = ( locations, resolved = true ) => {
	const hasFinishedResolution = jest.fn( () => {
		return resolved;
	} );

	useSelect.mockImplementation( ( callback ) => {
		return callback( () => {
			return {
				getGoogleBusinessProfileLocations: () => {
					return locations;
				},
				hasFinishedResolution,
			};
		} );
	} );

	return hasFinishedResolution;
};

const renderLocations = () => {
	return renderHook( () => useGoogleBusinessProfileLocations() ).result
		.current;
};

describe( 'useGoogleBusinessProfileLocations', () => {
	it( 'returns the locations and whether they have resolved', () => {
		const hasFinishedResolution = mockStore( [ downtown, riverside ] );

		expect( renderLocations() ).toEqual( {
			locations: [ downtown, riverside ],
			hasFinishedResolution: true,
			hasLocationsError: false,
			hasNoLocations: false,
			singleLocation: null,
		} );
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleBusinessProfileLocations',
			[]
		);
	} );

	it( 'reports no derived state while the locations are loading', () => {
		mockStore( null, false );

		expect( renderLocations() ).toEqual( {
			locations: null,
			hasFinishedResolution: false,
			hasLocationsError: false,
			hasNoLocations: false,
			singleLocation: null,
		} );
	} );

	it( 'reports an error when the request finished without a list', () => {
		mockStore( null );

		expect( renderLocations() ).toMatchObject( {
			hasLocationsError: true,
			hasNoLocations: false,
			singleLocation: null,
		} );
	} );

	it( 'reports no locations when the list is empty', () => {
		mockStore( [] );

		expect( renderLocations() ).toMatchObject( {
			hasLocationsError: false,
			hasNoLocations: true,
			singleLocation: null,
		} );
	} );

	it( 'returns the location when exactly one was found', () => {
		mockStore( [ downtown ] );

		expect( renderLocations() ).toMatchObject( {
			hasLocationsError: false,
			hasNoLocations: false,
			singleLocation: downtown,
		} );
	} );
} );
