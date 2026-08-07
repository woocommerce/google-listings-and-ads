/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useExistingSearchConsoleProperties from './useExistingSearchConsoleProperties';
import useSearchConsoleAccount from './useSearchConsoleAccount';
import { useAppDispatch } from '~/data';

jest.mock( './useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'useExistingSearchConsoleProperties', () => {
	let invalidateResolution;

	beforeEach( () => {
		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );
	} );

	it( 'returns the candidate properties from the Search Console account payload', () => {
		const properties = [
			{ url: 'https://a.example.com/', selectable: true },
			{ url: 'https://b.example.com/', selectable: true },
		];
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: { status: 'incomplete', properties },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useExistingSearchConsoleProperties()
		);

		expect( result.current.data ).toBe( properties );
		expect( result.current.isResolving ).toBe( false );
		expect( result.current.hasFinishedResolution ).toBe( true );
	} );

	it( 'returns an empty list when the account has no candidate properties yet', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: null,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useExistingSearchConsoleProperties()
		);

		expect( result.current.data ).toEqual( [] );
		expect( result.current.isResolving ).toBe( true );
	} );

	it( 'invalidates the Search Console account resolution when requested', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: { status: 'incomplete', properties: [] },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useExistingSearchConsoleProperties()
		);

		result.current.invalidateResolution();

		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );
} );
