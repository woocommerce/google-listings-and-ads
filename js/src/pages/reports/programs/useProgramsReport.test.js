/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useProgramsReport from './useProgramsReport';
import useReportsAvailability from '~/hooks/useReportsAvailability';
import useUrlQuery from '~/hooks/useUrlQuery';
import { MISSING_FREE_LISTINGS_DATA } from '~/data/utils';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );
jest.mock( '~/hooks/useReportsAvailability' );
jest.mock( '~/hooks/useUrlQuery' );

const paidReport = {
	loaded: true,
	reportQuery: { fields: [ 'clicks', 'spend' ] },
	data: {
		campaigns: [ { id: 1, name: 'PMax', subtotals: { clicks: 4 } } ],
		intervals: null,
		totals: { clicks: 4, spend: 10 },
	},
};

const freeReport = {
	loaded: true,
	reportQuery: { fields: [ 'clicks' ] },
	data: {
		free_listings: [ { subtotals: { clicks: 2 } } ],
		intervals: null,
		totals: { clicks: 2 },
	},
};

describe( 'useProgramsReport', () => {
	let getReport;

	beforeEach( () => {
		window.glaData.adsSetupComplete = true;
		useUrlQuery.mockReturnValue( {} );

		getReport = jest.fn( ( category, type ) =>
			type === 'paid' ? paidReport : freeReport
		);
		useSelect.mockImplementation( ( cb ) => cb( () => ( { getReport } ) ) );
	} );

	it( 'fetches and sums the free and paid reports when Merchant Center is connected', () => {
		useReportsAvailability.mockReturnValue( { hasMC: true } );

		const { result } = renderHook( () => useProgramsReport() );

		expect( getReport ).toHaveBeenCalledWith(
			'programs',
			'free',
			{},
			'primary'
		);
		expect( result.current.data.freeListings ).toHaveLength( 1 );
		expect( result.current.data.totals.clicks.value ).toBe( 6 );
	} );

	it( 'fetches only the paid report when Merchant Center is not connected', () => {
		useReportsAvailability.mockReturnValue( { hasMC: false } );

		const { result } = renderHook( () => useProgramsReport() );

		expect( getReport ).not.toHaveBeenCalledWith(
			'programs',
			'free',
			expect.anything(),
			expect.anything()
		);
		expect( result.current.loaded ).toBe( true );
		expect( result.current.data.freeListings ).toEqual( [] );
		expect( result.current.data.campaigns ).toEqual(
			paidReport.data.campaigns
		);
		expect( result.current.data.totals.clicks ).toEqual(
			expect.objectContaining( {
				value: 4,
				missingFreeListingsData: MISSING_FREE_LISTINGS_DATA.NONE,
			} )
		);
		expect( result.current.data.totals.spend ).toEqual(
			expect.objectContaining( {
				value: 10,
				missingFreeListingsData: MISSING_FREE_LISTINGS_DATA.NONE,
			} )
		);
	} );
} );
