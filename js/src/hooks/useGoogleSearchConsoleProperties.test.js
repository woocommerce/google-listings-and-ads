/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleSearchConsoleProperties from './useGoogleSearchConsoleProperties';

const mockGetGoogleSearchConsoleProperties = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

describe( 'useGoogleSearchConsoleProperties', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getGoogleSearchConsoleProperties:
					mockGetGoogleSearchConsoleProperties,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	it( 'returns the properties and resolution state from the store', () => {
		const properties = [ { siteUrl: 'https://example.com/' } ];
		mockGetGoogleSearchConsoleProperties.mockReturnValue( properties );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties,
			hasFinishedResolution: true,
		} );
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleProperties',
			[]
		);
	} );

	it( 'reports unfinished resolution while the properties are loading', () => {
		mockGetGoogleSearchConsoleProperties.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties: null,
			hasFinishedResolution: false,
		} );
	} );
} );
