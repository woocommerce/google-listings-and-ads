jest.mock( '.~/hooks/useAppSelectDispatch', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useAppSelectDispatch' )
		.mockImplementation( ( selector, args ) => {
			return {
				hasFinishedResolution: true,
				data: { ...args },
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
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';

describe( 'useMCIssuesTypeFilter', () => {
	test( 'Calls useAppSelectDispatch with `getMCIssues` and the right parameters', async () => {
		const { rerender } = renderHook(
			( { issueType, page, perPage } ) =>
				useMCIssuesTypeFilter( issueType, page, perPage ),
			{
				initialProps: {
					issueType: 'account',
				},
			}
		);

		expect( useAppSelectDispatch ).toHaveBeenCalledWith( 'getMCIssues', {
			issue_type: 'account',
			page: 1,
			per_page: 5,
		} );

		expect( useAppSelectDispatch ).toHaveBeenCalledTimes( 1 );

		rerender( { issueType: 'product', page: 3, perPage: 2 } );

		expect( useAppSelectDispatch ).toHaveBeenCalledWith( 'getMCIssues', {
			issue_type: 'product',
			page: 3,
			per_page: 2,
		} );

		expect( useAppSelectDispatch ).toHaveBeenCalledTimes( 2 );
	} );
} );
