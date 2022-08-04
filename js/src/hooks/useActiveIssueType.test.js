jest.mock( '@woocommerce/navigation' );
jest.mock( '.~/hooks/useMCIssuesTotals' );

/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import useActiveIssueType from '.~/hooks/useActiveIssueType';

describe( 'useActiveIssueType', () => {
	it( 'Returns "account" Issue type by default if it has issues', () => {
		getQuery.mockReturnValue( {} );
		useMCIssuesTotals.mockReturnValue( { account: 3 } );
		const { result } = renderHook( () => useActiveIssueType() );
		expect( result.current ).toEqual( 'account' );
	} );

	it( 'If there is no account issues, then return "product" issue type by default', () => {
		getQuery.mockReturnValue( {} );
		useMCIssuesTotals.mockReturnValue( { account: 0 } );
		const { result } = renderHook( () => useActiveIssueType() );
		expect( result.current ).toEqual( 'product' );
	} );

	it( 'Returns the current query property "issueType" if defined', () => {
		getQuery.mockReturnValue( {
			issueType: 'product',
		} );
		const { result } = renderHook( () => useActiveIssueType() );
		expect( result.current ).toEqual( 'product' );
	} );
} );
