/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import getSelectedReportKey from './getSelectedReportKey';

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: jest.fn(),
} ) );

describe( 'getSelectedReportKey', () => {
	it.each( [ 'programs', 'products' ] )(
		'should return the "%s" report key when it is in the URL',
		( reportKey ) => {
			getQuery.mockReturnValue( { reportKey } );

			expect( getSelectedReportKey() ).toBe( reportKey );
		}
	);

	it( 'should return the Organic search report key when that report is available', () => {
		getQuery.mockReturnValue( { reportKey: 'search-console' } );

		expect( getSelectedReportKey( true ) ).toBe( 'search-console' );
	} );

	it( 'should fall back to the Programs report for the Organic search report when it is not available', () => {
		getQuery.mockReturnValue( { reportKey: 'search-console' } );

		expect( getSelectedReportKey() ).toBe( 'programs' );
	} );

	it( 'should fall back to the Programs report when no report key is in the URL', () => {
		getQuery.mockReturnValue( {} );

		expect( getSelectedReportKey() ).toBe( 'programs' );
	} );

	it( 'should fall back to the Programs report for an unknown report key', () => {
		getQuery.mockReturnValue( { reportKey: 'unknown' } );

		expect( getSelectedReportKey() ).toBe( 'programs' );
	} );
} );
