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
import useGoogleSearchConsoleAccount from './useGoogleSearchConsoleAccount';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';

const { INCOMPLETE, ACTION_NEEDED, CONNECTED } =
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

const mockGetGoogleSearchConsoleProperties = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( './useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

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

	it( 'returns the properties and resolution state from the store when the account is incomplete', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		const properties = [ { siteUrl: 'https://example.com/' } ];
		mockGetGoogleSearchConsoleProperties.mockReturnValue( properties );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties,
			hasUsableProperty: false,
			hasExactMatch: false,
			hasFinishedResolution: true,
		} );
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleProperties',
			[]
		);
	} );

	it( 'reports hasUsableProperty as true when at least one candidate is usable', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		const properties = [
			{ siteUrl: 'https://example.com/elsewhere/', usable: false },
			{ siteUrl: 'https://example.com/', usable: true },
		];
		mockGetGoogleSearchConsoleProperties.mockReturnValue( properties );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties,
			hasUsableProperty: true,
			hasExactMatch: false,
			hasFinishedResolution: true,
		} );
	} );

	it( 'reports hasExactMatch as true when a usable candidate is also an exact match', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: ACTION_NEEDED },
			hasFinishedResolution: true,
		} );
		const properties = [
			{
				siteUrl: 'https://example.com/elsewhere/',
				usable: false,
				exact_match: false,
			},
			{
				siteUrl: 'https://example.com/',
				usable: true,
				exact_match: true,
			},
		];
		mockGetGoogleSearchConsoleProperties.mockReturnValue( properties );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties,
			hasUsableProperty: true,
			hasExactMatch: true,
			hasFinishedResolution: true,
		} );
	} );

	it( 'reports hasExactMatch as false when a candidate is an exact match but not usable', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: ACTION_NEEDED },
			hasFinishedResolution: true,
		} );
		const properties = [
			{
				siteUrl: 'https://example.com/',
				usable: false,
				exact_match: true,
			},
		];
		mockGetGoogleSearchConsoleProperties.mockReturnValue( properties );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties,
			hasUsableProperty: false,
			hasExactMatch: false,
			hasFinishedResolution: true,
		} );
	} );

	it( 'reports unfinished resolution while the properties are loading', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		mockGetGoogleSearchConsoleProperties.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties: null,
			hasUsableProperty: false,
			hasExactMatch: false,
			hasFinishedResolution: false,
		} );
	} );

	it( 'returns the properties and resolution state from the store when the account needs action', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: ACTION_NEEDED },
			hasFinishedResolution: true,
		} );
		const properties = [ { siteUrl: 'https://example.com/' } ];
		mockGetGoogleSearchConsoleProperties.mockReturnValue( properties );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties,
			hasUsableProperty: false,
			hasExactMatch: false,
			hasFinishedResolution: true,
		} );
	} );

	it( 'never calls the store selector when the account status needs no candidate list, so no fetch is triggered', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: CONNECTED },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties: undefined,
			hasUsableProperty: false,
			hasExactMatch: false,
			hasFinishedResolution: true,
		} );
		expect( mockGetGoogleSearchConsoleProperties ).not.toHaveBeenCalled();
		expect( mockHasFinishedResolution ).not.toHaveBeenCalled();
	} );

	it( 'reports the account resolution state while there is no account yet', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: undefined,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleProperties()
		);

		expect( result.current ).toEqual( {
			properties: undefined,
			hasUsableProperty: false,
			hasExactMatch: false,
			hasFinishedResolution: false,
		} );
	} );
} );
