/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useTopStorePages from './useTopStorePages';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '~/hooks/useUrlQuery', () => ( {
	__esModule: true,
	default: () => ( {} ),
} ) );

jest.mock( '@woocommerce/date', () => ( {
	getCurrentDates: () => ( {
		primary: {
			after: jest.requireActual( 'moment' )( '2026-06-01' ),
			before: jest.requireActual( 'moment' )( '2026-06-28' ),
		},
	} ),
} ) );

const mockGetSearchConsoleTopStorePages = jest.fn();
const mockHasFinishedResolution = jest.fn();

const report = {
	pages: [
		{
			post_id: 12,
			name: 'About us',
			url: 'https://example.com/about/',
			clicks: 9,
			impressions: 90,
			ctr: 0.1,
			position: 1.2,
		},
	],
	limit: 100,
};

describe( 'useTopStorePages', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getSearchConsoleTopStorePages:
					mockGetSearchConsoleTopStorePages,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	it( 'requests the report of the selected date range once connected', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				status: 'connected',
				site_url: 'https://example.com/',
			},
			isConnected: true,
			hasFinishedResolution: true,
		} );
		mockGetSearchConsoleTopStorePages.mockReturnValue( report );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useTopStorePages() );

		const reportQuery = { after: '2026-06-01', before: '2026-06-28' };
		expect( mockGetSearchConsoleTopStorePages ).toHaveBeenCalledWith(
			reportQuery
		);
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getSearchConsoleTopStorePages',
			[ reportQuery ]
		);
		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			property: 'https://example.com/',
			pages: report.pages,
			limit: 100,
			days: 28,
		} );
	} );

	it( 'is still loading while the report resolves', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: 'connected', site_url: 'https://example.com/' },
			isConnected: true,
			hasFinishedResolution: true,
		} );
		mockGetSearchConsoleTopStorePages.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () => useTopStorePages() );

		expect( result.current.hasFinishedResolution ).toBe( false );
		expect( result.current.pages ).toEqual( [] );
	} );

	it.each( [ 'disconnected', 'incomplete', 'action-needed', 'reconnect' ] )(
		'does not request the report when the status is %s',
		( status ) => {
			useGoogleSearchConsoleAccount.mockReturnValue( {
				account: { status },
				isConnected: false,
				hasFinishedResolution: true,
			} );

			const { result } = renderHook( () => useTopStorePages() );

			expect( mockGetSearchConsoleTopStorePages ).not.toHaveBeenCalled();
			expect( result.current.hasFinishedResolution ).toBe( true );
			expect( result.current.pages ).toEqual( [] );
			expect( result.current.property ).toBeNull();
			expect( result.current.limit ).toBeNull();
		}
	);

	it( 'waits for the account before reporting as finished', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: null,
			isConnected: false,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useTopStorePages() );

		expect( mockGetSearchConsoleTopStorePages ).not.toHaveBeenCalled();
		expect( result.current.hasFinishedResolution ).toBe( false );
	} );
} );
