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
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';

describe( 'useMCIssuesTotals', () => {
	test.each( [ ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT ] )(
		'Gets %s total data',
		( issueType ) => {
			const { result: resultType } = renderHook( () =>
				useMCIssuesTypeFilter( issueType )
			);
			const { result } = renderHook( () => useMCIssuesTotals() );
			expect( result.current[ issueType ] ).toStrictEqual(
				resultType.current.data.total
			);
		}
	);

	test( 'Total is equivalent to the sum of the issueType totals', () => {
		const { result: resultProducts } = renderHook( () =>
			useMCIssuesTypeFilter( ISSUE_TYPE_PRODUCT )
		);

		const { result: resultAccount } = renderHook( () =>
			useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT )
		);

		const { result } = renderHook( () => useMCIssuesTotals() );

		expect( result.current.total ).toEqual(
			resultProducts.current.data.total + resultAccount.current.data.total
		);
	} );
} );
