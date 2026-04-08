/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';

const mockGetReportByApiQuery = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

describe( 'useHasRecentAdSpend', () => {
	beforeAll( () => {
		jest.useFakeTimers();
		jest.setSystemTime( new Date( '2025-02-18' ) );
	} );

	afterAll( () => {
		jest.useRealTimers();
	} );

	beforeEach( () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: true,
		} );
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getReportByApiQuery: mockGetReportByApiQuery,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	test( 'indicates pending state and no ad spend while the report is still loading', () => {
		mockHasFinishedResolution.mockReturnValue( false );
		mockGetReportByApiQuery.mockReturnValue( null );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current.hasFinishedResolution ).toBe( false );
		expect( result.current.hasAdSpend ).toBeFalsy();
	} );

	test( 'makes the API call with correct args and indicates pending state when account is connected but report is still loading', () => {
		mockHasFinishedResolution.mockReturnValue( false );
		mockGetReportByApiQuery.mockReturnValue( null );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( mockGetReportByApiQuery ).toHaveBeenCalledWith(
			'programs',
			'paid',
			{
				after: '2025-02-04',
				before: '2025-02-18',
				fields: [ 'spend' ],
			}
		);
		expect( result.current ).toEqual( {
			hasFinishedResolution: false,
			hasAdSpend: false,
		} );
	} );

	test( 'returns true when the reported spend is greater than zero', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( { totals: { spend: 42.5 } } );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			hasAdSpend: true,
		} );
	} );

	test( 'returns false spend when the reported spend amount is zero', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( { totals: { spend: 0 } } );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			hasAdSpend: false,
		} );
	} );

	test( 'skips the API call and reports no ad spend when Google Ads account is not ready', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( mockGetReportByApiQuery ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			hasAdSpend: false,
		} );
	} );

	test( 'uses 14-day lookback by default', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( { totals: { spend: 0 } } );

		renderHook( () => useHasRecentAdSpend() );

		expect( mockGetReportByApiQuery ).toHaveBeenCalledWith(
			'programs',
			'paid',
			{
				after: '2025-02-04',
				before: '2025-02-18',
				fields: [ 'spend' ],
			}
		);
	} );

	test( 'uses the provided days parameter for the lookback window', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( { totals: { spend: 0 } } );

		renderHook( () => useHasRecentAdSpend( 30 ) );

		expect( mockGetReportByApiQuery ).toHaveBeenCalledWith(
			'programs',
			'paid',
			{
				after: '2025-01-19',
				before: '2025-02-18',
				fields: [ 'spend' ],
			}
		);
	} );
} );
