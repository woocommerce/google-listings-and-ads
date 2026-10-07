/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleBusinessProfileLocations from './useGoogleBusinessProfileLocations';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );
jest.mock( '~/hooks/useAppSelectDispatch', () =>
	jest.fn().mockName( 'useAppSelectDispatch' )
);

describe( 'useGoogleBusinessProfileLocations', () => {
	let invalidateResolution;

	beforeEach( () => {
		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useSelect.mockReturnValue( false );
	} );

	const mockSelectDispatch = ( overrides ) => {
		useAppSelectDispatch.mockReturnValue( {
			data: null,
			isResolving: false,
			hasFinishedResolution: false,
			invalidateResolution,
			...overrides,
		} );
	};

	it( 'reads from the locations selector', () => {
		mockSelectDispatch();

		renderHook( () => useGoogleBusinessProfileLocations() );

		expect( useAppSelectDispatch ).toHaveBeenCalledWith(
			'getGoogleBusinessProfileLocations'
		);
	} );

	it( 'is loading until resolution finishes, and while a refetch is in flight', () => {
		mockSelectDispatch( { hasFinishedResolution: false } );
		const { result, rerender } = renderHook( () =>
			useGoogleBusinessProfileLocations()
		);
		expect( result.current.isLoading ).toBe( true );

		mockSelectDispatch( {
			hasFinishedResolution: true,
			isResolving: true,
		} );
		rerender();
		expect( result.current.isLoading ).toBe( true );

		mockSelectDispatch( { hasFinishedResolution: true, data: [] } );
		rerender();
		expect( result.current.isLoading ).toBe( false );
		expect( result.current.locations ).toEqual( [] );
	} );

	it( 'exposes the failure flag and a refetch callback', () => {
		mockSelectDispatch( { hasFinishedResolution: true } );
		useSelect.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleBusinessProfileLocations()
		);

		expect( result.current.hasError ).toBe( true );
		expect( result.current.refetch ).toBe( invalidateResolution );
	} );
} );
