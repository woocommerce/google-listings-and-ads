jest.mock( '.~/hooks/useMCIssuesTypeFilter', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMCIssuesTypeFilter' )
		.mockImplementation( ( issueType ) => {
			return {
				data: {
					issues: issueType,
					total: 1,
				},
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

	test( 'Total is the sum of Account total and Product total', () => {
		const { result } = renderHook( () => useMCIssues() );
		const { result: accountResult } = renderHook( () =>
			useMCIssues( ISSUE_TYPE_ACCOUNT )
		);
		const { result: productResult } = renderHook( () =>
			useMCIssues( ISSUE_TYPE_PRODUCT )
		);

		expect( result.current.total ).toEqual(
			accountResult.current.data.total + productResult.current.data.total
		);
	} );

	test.each( [ ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT ] )(
		'Gets %s data when using it as a filter',
		( issueType ) => {
			const { result } = renderHook( () => useMCIssues( issueType ) );

			expect( result.current.data.issues ).toEqual( issueType );
		}
	);
} );
