/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';

const mockGetReportByApiQuery = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

describe( 'useHasRecentAdSpend', () => {
	beforeAll( () => {
		jest.useFakeTimers();
		jest.setSystemTime( new Date( '2025-02-18' ) );
	} );

	afterAll( () => {
		jest.useRealTimers();
	} );

	beforeEach( () => {
		glaData.adsSetupComplete = true;
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getReportByApiQuery: mockGetReportByApiQuery,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	test( 'returns loading:true, hasFinishedResolution:false and hasAdSpend:false while resolution is pending', () => {
		mockHasFinishedResolution.mockReturnValue( false );
		mockGetReportByApiQuery.mockReturnValue( null );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current ).toEqual( {
			loading: true,
			hasFinishedResolution: false,
			hasAdSpend: false,
		} );
	} );

	test( 'returns loading:false, hasFinishedResolution:true and hasAdSpend:true when spend > 0', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( { totals: { spend: 42.5 } } );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current ).toEqual( {
			loading: false,
			hasFinishedResolution: true,
			hasAdSpend: true,
		} );
	} );

	test( 'returns loading:false, hasFinishedResolution:true and hasAdSpend:false when spend === 0', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( { totals: { spend: 0 } } );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current ).toEqual( {
			loading: false,
			hasFinishedResolution: true,
			hasAdSpend: false,
		} );
	} );

	test( 'returns loading:false, hasFinishedResolution:true and hasAdSpend:false when report is null', () => {
		mockHasFinishedResolution.mockReturnValue( true );
		mockGetReportByApiQuery.mockReturnValue( null );

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( result.current ).toEqual( {
			loading: false,
			hasFinishedResolution: true,
			hasAdSpend: false,
		} );
	} );

	test( 'returns loading:false, hasFinishedResolution:true and hasAdSpend:false without API call when adsSetupComplete is false', () => {
		glaData.adsSetupComplete = false;

		const { result } = renderHook( () => useHasRecentAdSpend() );

		expect( mockGetReportByApiQuery ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			loading: false,
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
