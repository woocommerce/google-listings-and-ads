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

const originalUseAppSelectDispatch = jest.requireActual(
	'.~/hooks/useAppSelectDispatch'
);

/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFIlter';

afterEach( () => {
	jest.clearAllMocks();
} );

describe( 'useMCIssuesTypeFilter', () => {
	test( 'Calls useAppSelectDispatch with `getMCIssues` selector on each page update', async () => {
		const { result } = renderHook( () => useMCIssuesTypeFilter() );

		act( () => {
			result.current.setPage( 2 );
		} );

		expect( useAppSelectDispatch ).toHaveBeenCalledWith(
			'getMCIssues',
			expect.any( Object )
		);

		expect( useAppSelectDispatch ).toHaveBeenCalledTimes( 2 );
	} );

	test( 'Returns the correct page and data after calling setPage', async () => {
		const { result } = renderHook( () => useMCIssuesTypeFilter() );
		expect( result.current.page ).toBe( 1 );
		expect( result.current.data.page ).toBe( 1 );

		act( () => {
			result.current.setPage( 2 );
		} );

		expect( result.current.page ).toBe( 2 );
		expect( result.current.data.page ).toBe( 2 );
	} );

	test( 'Supports Product Issue Type', async () => {
		const { result } = renderHook( () =>
			useMCIssuesTypeFilter( 'product' )
		);
		expect( result.current.data.issue_type ).toBe( 'product' );
	} );

	test( 'Returns the default values correctly', () => {
		useAppSelectDispatch.mockImplementation(
			originalUseAppSelectDispatch.default
		);

		const { result } = renderHook( () => useMCIssuesTypeFilter() );

		expect( result.current.data ).toBe( null );
		expect( result.current.hasFinishedResolution ).toBe( false );
		expect( result.current.page ).toBe( 1 );
		expect( typeof result.current.setPage ).toBe( 'function' );
	} );
} );
