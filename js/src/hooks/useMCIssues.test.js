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
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';

describe( 'useMcIssues', () => {
	test.each( [ ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT ] )(
		'Gets %s data when using it as a filter',
		( issueType ) => {
			const { result: resultType } = renderHook( () =>
				useMCIssuesTypeFilter( issueType )
			);
			const { result } = renderHook( () => useMCIssues() );
			expect( result.current[ issueType ] ).toStrictEqual(
				resultType.current
			);
		}
	);
} );
