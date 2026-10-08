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

describe( 'useGoogleBusinessProfileLocations', () => {
	it( 'returns the locations and whether they have resolved', () => {
		const locations = [ { id: '1111', title: "Jane's Bakery" } ];
		const getGoogleBusinessProfileLocations = jest.fn( () => {
			return locations;
		} );
		const hasFinishedResolution = jest.fn( () => {
			return true;
		} );

		useSelect.mockImplementation( ( callback ) => {
			return callback( () => {
				return {
					getGoogleBusinessProfileLocations,
					hasFinishedResolution,
				};
			} );
		} );

		const { result } = renderHook( () =>
			useGoogleBusinessProfileLocations()
		);

		expect( result.current ).toEqual( {
			locations,
			hasFinishedResolution: true,
		} );
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleBusinessProfileLocations',
			[]
		);
	} );
} );
