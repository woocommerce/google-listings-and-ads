jest.mock( '@woocommerce/navigation' );

/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import getActiveIssueType from '.~/product-feed/issues-table-card/getActiveIssueType';

describe( 'GetActiveIssueType helper', () => {
	it( 'Returns "account" Issue type by default', () => {
		getQuery.mockImplementation( () => {
			return {};
		} );

		expect( getActiveIssueType() ).toEqual( 'account' );
	} );

	it( 'Returns the current query property "issueType" if defined', () => {
		getQuery.mockImplementation( () => {
			return {
				issueType: 'product',
			};
		} );

		expect( getActiveIssueType() ).toEqual( 'product' );
	} );
} );
