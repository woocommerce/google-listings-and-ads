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
	it.each( [ 'programs', 'products', 'search-console' ] )(
		'should return the "%s" report key when it is in the URL',
		( reportKey ) => {
			getQuery.mockReturnValue( { reportKey } );

			expect( getSelectedReportKey() ).toBe( reportKey );
		}
	);

	it( 'should fall back to the Programs report when no report key is in the URL', () => {
		getQuery.mockReturnValue( {} );

		expect( getSelectedReportKey() ).toBe( 'programs' );
	} );

	it( 'should fall back to the Programs report for an unknown report key', () => {
		getQuery.mockReturnValue( { reportKey: 'unknown' } );

		expect( getSelectedReportKey() ).toBe( 'programs' );
	} );
} );
