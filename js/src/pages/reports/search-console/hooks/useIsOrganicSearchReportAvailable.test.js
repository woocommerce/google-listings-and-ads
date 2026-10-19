/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useIsOrganicSearchReportAvailable from './useIsOrganicSearchReportAvailable';

const mockGetLoadedGoogleSearchConsoleStatus = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

describe( 'useIsOrganicSearchReportAvailable', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( callback ) => {
			return callback( () => ( {
				getLoadedGoogleSearchConsoleStatus:
					mockGetLoadedGoogleSearchConsoleStatus,
			} ) );
		} );
	} );

	afterEach( () => {
		global.glaData.searchConsoleConnected = false;
	} );

	it( 'should be available when the status in the store is connected, even if the page loaded before connecting', () => {
		global.glaData.searchConsoleConnected = false;
		mockGetLoadedGoogleSearchConsoleStatus.mockReturnValue( 'connected' );

		const { result } = renderHook( () =>
			useIsOrganicSearchReportAvailable()
		);

		expect( result.current ).toBe( true );
	} );

	it.each( [ 'disconnected', 'action-needed', 'reconnect', 'incomplete' ] )(
		'should not be available when the status in the store is "%s", even if the page loaded while connected',
		( status ) => {
			global.glaData.searchConsoleConnected = true;
			mockGetLoadedGoogleSearchConsoleStatus.mockReturnValue( status );

			const { result } = renderHook( () =>
				useIsOrganicSearchReportAvailable()
			);

			expect( result.current ).toBe( false );
		}
	);

	it.each( [ true, false ] )(
		'should fall back to the stored connection sent with the page (%s) when the store has no status yet',
		( searchConsoleConnected ) => {
			global.glaData.searchConsoleConnected = searchConsoleConnected;
			mockGetLoadedGoogleSearchConsoleStatus.mockReturnValue( null );

			const { result } = renderHook( () =>
				useIsOrganicSearchReportAvailable()
			);

			expect( result.current ).toBe( searchConsoleConnected );
		}
	);
} );
