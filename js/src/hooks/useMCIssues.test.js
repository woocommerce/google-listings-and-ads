jest.mock( '.~/hooks/useMCIssuesTypeFilter', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMCIssuesTypeFilter' )
		.mockImplementation( ( issueType ) => {
			return {
				data: issueType,
			};
		} ),
} ) );

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useMCIssues from '.~/hooks/useMCIssues';
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';

describe( 'useMcIssues', () => {
	test( 'Gets both account and product issues by default', () => {
		const { result } = renderHook( () => useMCIssues() );

		expect( result.current ).toHaveProperty( ISSUE_TYPE_ACCOUNT );
		expect( result.current ).toHaveProperty( ISSUE_TYPE_PRODUCT );
	} );

	test.each( [ ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT ] )(
		'Gets %s data when using it as a filter',
		( issueType ) => {
			const { result } = renderHook( () => useMCIssues( issueType ) );

			expect( result.current.data ).toEqual( issueType );
		}
	);
} );
